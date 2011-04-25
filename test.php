<?php
namespace Tasks;

class TestResult
{
	protected $name;
	protected $type;
	protected $expected;
	protected $result;
	protected $expected_setted = false;
	protected $types_compare = false;
	protected $description;

	const type_success = 'success';
	const type_fail = 'fail';

	public function __construct($name)
	{
		$this->name = $name;
		$this->Success();
		return $this;
	}

	public function Expected($expected)
	{
		$this->expected = $expected;
		$this->expected_setted = true;
		return $this;
	}

	public function Result($result)
	{
		$this->result = $result;
		if ($this->expected_setted)
		{
			if ($this->types_compare)
			{
				if ($result===$this->expected) $this->Success();
				else $this->Fail();
			}
			else
			{
				if ($result==$this->expected) $this->Success();
				else $this->Fail();
			}
		}
		return $this;
	}

	public function getExpected()
	{ return $this->expected; }

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function getName()
	{ return $this->name; }

	public function getResult()
	{ return $this->result; }

	public function Success()
	{
		$this->type = self::type_success;
		return $this;
	}

	public function Fail()
	{
		$this->type = self::type_fail;
		return $this;
	}

	public function getType()
	{ return $this->type; }

	public function setTypesCompare($types_compare = true)
	{
		if (is_bool($types_compare)) $this->types_compare = $types_compare;
		return $this;
	}

	public function getTypesCompare()
	{ return $this->types_compare; }

	public function addDescription($description)
	{
		if (empty($description)) return false;
		if (!empty($this->description)) $this->description .= PHP_EOL.$description;
		else $this->description = $description;
		return $this;
	}

	public function getDescription()
	{ return $this->description; }

}

class Test
{
	const execution_globals_key = 'tasks_testing';
	protected $results = array();
	/** @var IStorage */
	protected $storage;

	public function __construct(IStorage $storage)
	{
		$this->storage = $storage;
		StorageManager::setStorage($storage);
	}

	/**
	 * @return array
	 */
	public function RunTests()
	{
		$this->test_storage_manager();
		$this->test_semaphore_create();
		$this->test_semaphore_exists();
		$this->test_semaphore_delete();
		$this->test_get_tasks_list();
		$this->test_get_next_task();
		$this->test_executor();

		return $this->results;
	}

	public function test_storage_manager()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$storage = StorageManager::GetStorage();
		$result->Expected(true)->Result(is_a($storage, 'Tasks\IStorage'))->addDescription(gettype($storage));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		StorageManager::setStorage(new Storage_Files());
		$storage = StorageManager::GetStorage();
		$result->Expected(true)->Result(is_a($storage, 'Tasks\Storage_Files'))->addDescription(gettype($storage));

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		StorageManager::setStorage(new Storage_Memcache());
		$storage = StorageManager::GetStorage();
		$result->Expected(true)->Result(is_a($storage, 'Tasks\Storage_Memcache'))->addDescription(gettype($storage));

		StorageManager::setStorage($this->storage);
	}

	public function test_semaphore_create()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$this->storage->semaphore_delete();
		$r = $this->storage->semaphore_create();
		$c = $this->storage->semaphore_create();
		$result->Expected(array(true, false))->Result(array($r, $c));
	}

	public function test_semaphore_exists()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$this->storage->semaphore_delete();
		$this->storage->semaphore_create();
		$r = $this->storage->semaphore_exists();
		$result->Expected(true)->Result($r);
	}

	public function test_semaphore_delete()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$this->storage->semaphore_delete();
		$this->storage->semaphore_create();
		$r = $this->storage->semaphore_exists();
		$d = $this->storage->semaphore_delete();
		$c = $this->storage->semaphore_exists();
		$result->Expected(array(true, true, false))->Result(array($r, $d, $c));
	}

	public function test_get_tasks_list()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$task = new TestTask();
		$task->Add('zz', 'zzz', true, 5);
		$task->Add('zz', 'zzz', false, 1);
		$r = $this->storage->get_tasks_list();
		$result->Expected(array(true, 2))->Result(array(is_array($r), count($r)));
	}

	public function test_get_next_task()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$task = new TestTask();
		$task->Add('zz', 'zzz', true, 5);
		$before = count($this->storage->get_tasks_list());
		$next_task = $this->storage->get_next_task();
		$after = count($this->storage->get_tasks_list());
		$result->Expected(array(true, 1))->Result(array(is_a($next_task, 'Tasks\TestTask'), ($before-$after)))->addDescription(print_r($next_task, true))->addDescription('count after: '.$after);
	}

	public function test_executor()
	{
		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$executor = new Executor();
		while ($old_tasks = $this->storage->get_next_task()) $old_tasks->execute();
		$GLOBALS[Test::execution_globals_key] = array('title' => 'pre_title', 'descr' => 'pre_descr');
		$task = new TestTask();
		$task->Add('test_title');
		$executor->Start(1);
		$result->Expected('test_title')->Result($GLOBALS[Test::execution_globals_key]['title']);

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$task = new TestTask();
		$task->Add('test_title1', 'test_descr');
		$executor->Start(1);
		$result->Expected(array('title' => 'test_title1', 'descr' => 'test_descr'))->Result($GLOBALS[Test::execution_globals_key]);

		$this->results[] = $result = new TestResult(__METHOD__.__LINE__);
		$task = new TestTask();
		$task->Add('test_title2', 'test_descr2', true, 5);
		$task->Add('test_title2', 'test_descr2', true, 1);
		$executor->Start(1);
		$result->Expected(array('title' => 'test_title2', 'descr' => 'test_descr2'))->Result($GLOBALS[Test::execution_globals_key]);
	}

}

class TestTask extends Task
{
	protected $title;
	protected $descr;

	public function Add($title, $descr = '', $uniq = false, $priority = 1)
	{
		if (!empty($descr)) $data = array($title, $descr);
		else $data = $title;
		$this->getStorage()->store(__CLASS__, $data, $uniq, $priority);
	}

	public function execute()
	{
		$GLOBALS[Test::execution_globals_key]['title'] = $this->title;
		$GLOBALS[Test::execution_globals_key]['descr'] = $this->descr;
	}

	public function restore($data)
	{
		if (is_array($data)) list($this->title, $this->descr) = $data;
		else $this->title = $data;
	}
}

class Testing
{
	public static function PrintResults($results, $newline = PHP_EOL)
	{
		/** @var TestResult $result */
		foreach ($results as $result)
		{
			print $newline.$result->getName();
			print $newline.$result->getType();
			if ($result->getDescription()!='') print $newline.$result->getDescription();
			print $newline.'Expected: ';
			var_dump($result->getExpected());
			print 'Result: ';
			var_dump($result->getResult());
			print $newline.$newline.$newline;
		}
	}

	public static function MakeTest(IStorage $storage)
	{
		$start_time = microtime(true);
		$test = new Test($storage);
		self::PrintResults($test->RunTests());

		print PHP_EOL.round(microtime(true)-$start_time, 6).PHP_EOL;
	}
}
