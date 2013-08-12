<?php
namespace Jamm\Tasks;

class Executor
{
	/** @var IStorage */
	private $storage;

	public function __construct(IStorage $Tasks_Storage)
	{
		$this->storage = $Tasks_Storage;
	}

	public function Start($loop_time = 10800, $iterations_before_sleep = 100, $sleep_idle_seconds = 1)
	{
		$this->storage->setSemaphoreLifeTime($loop_time);
		if (!$this->storage->semaphore_create()) {
			return false;
		}
		$finish_time      = time() + $loop_time;
		$empty_iterations = 0;
		while (time() < $finish_time) {
			if (!$this->storage->semaphore_exists()) {
				return false;
			}
			$empty_iterations++;
			while (($task = $this->storage->get_next_task())) {
				$empty_iterations = 0;
				//Unlink semaphore before executing task - if this task will take too long time,
				//other process will be able to execute other tasks
				if (!$this->storage->semaphore_delete()) {
					return false;
				}
				$this->executeTask($task);
				if (time() > $finish_time) {
					return false;
				}
				//if semaphore exists, it's mean that task's execution time was too long
				//and other process already executes other tasks, so this process should exit
				//if not exists, it's mean this process still is main tasks executor, so let's
				//create semaphore, to prevent multiple processes going to loop
				if (!$this->storage->semaphore_create()) {
					return false;
				}
			}
			if ($empty_iterations > $iterations_before_sleep) {
				sleep($sleep_idle_seconds);
				$empty_iterations = 0;
			}
		}
		$this->storage->semaphore_delete();
		return true;
	}

	protected function executeTask(ITask $Task)
	{
		$result = $Task->execute();
		if ($result === false) {
			trigger_error('Task execution result is false!', E_USER_NOTICE);
		}
	}
}
