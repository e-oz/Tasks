<?php
namespace Jamm\Tasks;
class StorageFiles implements IStorage
{
	private $semaphore_file;
	private $tasks_dir;
	private $semaphore_life_time = 180;

	/**
	 * @param string $tasks_dir - temporary directory, when files of tasks will be stored
	 */
	public function __construct($tasks_dir)
	{
		$this->tasks_dir      = realpath($tasks_dir);
		$this->semaphore_file = $this->tasks_dir.'/.semaphore';
	}

	/**
	 * @return ITask
	 */
	public function get_next_task()
	{
		$dir = $this->get_tasks_list();
		if (empty($dir)) return false;
		sort($dir);
		foreach ($dir as $filepath)
		{
			$task = $this->read_task($filepath);
			if (is_object($task))
			{
				if (!unlink($filepath))
				{
					if (file_exists($filepath))
					{
						trigger_error('Can not delete task!', E_USER_WARNING);
						return false;
					}
				}
				return $task;
			}
		}
		return false;
	}

	public function semaphore_create()
	{
		if (file_exists($this->semaphore_file)) return false;
		return file_put_contents($this->semaphore_file, 1, LOCK_EX);
	}

	public function semaphore_delete()
	{
		if (file_exists($this->semaphore_file)) return unlink($this->semaphore_file);
		return true;
	}

	public function semaphore_exists()
	{
		if (file_exists($this->semaphore_file))
		{
			if (filemtime($this->semaphore_file) < (time()-$this->semaphore_life_time))
			{
				unlink($this->semaphore_file);
				return false;
			}
			else return true;
		}
		else return false;
	}

	/**
	 * @param string $id
	 * @return bool|ITask
	 */
	public function read_task($id)
	{
		$content = file_get_contents($id);
		if (empty($content)) return false;
		return unserialize($content);
	}

	public function store($task_object, $unique = false, $priority = 1)
	{
		$content  = serialize($task_object);
		$priority = '['.intval($priority).']';
		if ($unique)
		{
			$filename = '1'.md5($content);
			if (file_exists($filename)) return true;
		}
		else
		{
			$filename = $priority.$this->get_new_filename($this->tasks_dir.'/'.$priority);
		}
		return file_put_contents($this->tasks_dir.'/'.$filename, $content, LOCK_EX);
	}

	private function get_new_filename($dir)
	{
		$name = $t = round(microtime(true)*100);
		$i    = 0;
		while (file_exists($dir.$name.'.task'))
		{
			$i++;
			$name = $t.'_'.$i;
		}
		return $name.'.task';
	}

	public function get_tasks_list()
	{
		$dir = scandir($this->tasks_dir);
		if (empty($dir)) return false;
		$tasks = array();
		foreach ($dir as $file)
		{
			$filepath = $this->tasks_dir.'/'.$file;
			if ($file=='.' || $file=='..' || $filepath==$this->semaphore_file) continue;
			if (is_file($filepath)) $tasks[] = $filepath;
		}
		return $tasks;
	}

	public function getSemaphoreLifeTime()
	{
		return $this->semaphore_life_time;
	}

	public function setSemaphoreLifeTime($semaphore_life_time)
	{
		$this->semaphore_life_time = $semaphore_life_time;
	}
}
