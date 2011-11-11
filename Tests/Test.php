<?php
namespace Jamm\Tasks\Tests;

class Test extends \Jamm\Tester\ClassTest
{
	protected $storage;
	protected $task_mock_class;

	public function __construct(\Jamm\Tasks\IStorage $storage, \Jamm\Tasks\ITask $TaskMock = null)
	{
		$this->storage = $storage;
		if (!empty($TaskMock)) $this->task_mock_class = get_class($TaskMock);
	}

	protected function setUp()
	{
		ob_start();
	}

	protected function tearDown()
	{
		ob_end_clean();
	}

	protected function getNewTaskMockObject()
	{
		if (empty($this->task_mock_class)) return new TestTask($this->storage);
		$mock_class_name = $this->task_mock_class;
		return new $mock_class_name($this->storage);
	}

	protected function getNewExecutorObject()
	{
		return new \Jamm\Tasks\Executor($this->storage);
	}

	public function test_semaphore_create()
	{
		$this->storage->semaphore_delete();
		$this->assertTrue($this->storage->semaphore_create());
		$this->assertTrue(!$this->storage->semaphore_create())->addCommentary("Semaphore should not be created when already exists");
	}

	public function test_semaphore_exists()
	{
		$this->storage->semaphore_delete();
		$this->storage->semaphore_create();
		$this->assertTrue($this->storage->semaphore_exists());
	}

	public function test_semaphore_delete()
	{
		$this->storage->semaphore_delete();
		$this->storage->semaphore_create();
		$this->assertTrue($this->storage->semaphore_exists());
		$this->assertTrue($this->storage->semaphore_delete());
		$this->assertTrue(!$this->storage->semaphore_exists());
	}

	public function test_get_tasks_list()
	{
		$task = $this->getNewTaskMockObject();
		$r = $this->storage->get_tasks_list();
		if (!empty($r)) throw new \Exception('List is not empty');
		$task->Add('zz', 'zzz', true, 5);
		$task->Add('zz', 'zzz', false, 1);
		$r = $this->storage->get_tasks_list();
		$this->assertIsArray($r);
		$this->assertEquals(count($r), 2);
	}

	public function test_get_next_task()
	{
		$task = $this->getNewTaskMockObject();
		$this->assertTrue($task->Add('zz', __METHOD__, true, 5))->addCommentary('Not stored');
		$before = count($this->storage->get_tasks_list());
		$next_task = $this->storage->get_next_task();
		$after = count($this->storage->get_tasks_list());
		$this->assertEquals(($before-$after), 1)->addCommentary('Count before: '.$before.', count after: '.$after);
		$this->assertEquals(get_class($next_task), get_class($task));
	}

	public function test_executor()
	{
		$executor = $this->getNewExecutorObject();
		while (($old_tasks = $this->storage->get_next_task()))
		{
			$old_tasks->execute();
		}
		$task = $this->getNewTaskMockObject();
		$task->Add('test_title');
		ob_start();
		$executor->Start(1);
		$result = ob_get_contents();
		ob_end_clean();
		$this->assertEquals($result, 'test_title');

		$task = $this->getNewTaskMockObject();
		$task->Add('test_title1', 'test_descr');
		ob_start();
		$executor->Start(1);
		$result = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals($result, 'test_title1test_descr');

		$task = $this->getNewTaskMockObject();
		$task->Add('test_title2', 'test_descr2', true, 5);
		$task->Add('test_title2', 'test_descr2', true, 1);
		ob_start();
		$executor->Start(1);
		$result = trim(ob_get_contents());
		ob_end_clean();
		$this->assertEquals($result, 'test_title2test_descr2');
	}
}
