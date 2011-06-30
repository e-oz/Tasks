<?php
namespace Jamm\Tasks;

class Executor
{
	/** @var IStorage */
	private $storage;

	public function __construct(IStorage $Tasks_Storage = null)
	{
		if (!empty($Tasks_Storage)) $this->storage = $Tasks_Storage;
		else $this->storage = StorageManager::GetStorage();
	}

	public function Start($loop_time = 10800)
	{
		if ($this->storage->semaphore_exists()) return false;

		$finish_time = time()+$loop_time;
		while (time() < $finish_time)
		{
			while ($task = $this->storage->get_next_task())
			{
				//Unlink semaphore before executing task - if this task will take too long time,
				//other process will be able to execute other tasks
				if (!$this->storage->semaphore_delete()) return false;

				$task->execute();

				//if semaphore exists, it's mean that task's execution time was too long
				//and other process already executes other tasks, so this process should exit
				if ($this->storage->semaphore_exists()) return false;

				if (time() > $finish_time) break;

				//if not exists, it's mean this process still is main tasks executor, so let's
				//create semaphore, to prevent multiple processes going to loop
				if (!$this->storage->semaphore_create()) return false;
			}
			sleep(5);
		}

		$this->storage->semaphore_delete();
		return true;
	}
}
