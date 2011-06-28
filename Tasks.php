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

interface IStorage
{
	const semaphore_life_time = 300;

	/**
	 * @return ITask
	 */
	public function get_next_task();

	/**
	 * @return boolean
	 */
	public function semaphore_exists();

	/**
	 * @return boolean
	 */
	public function semaphore_create();

	/**
	 * @return boolean
	 */
	public function semaphore_delete();

	/**
	 * @param string $handler_class_name
	 * @param mixed $data
	 * @param boolean $uniq
	 * @param int $priority
	 * @return boolean
	 */
	public function store($handler_class_name, $data, $uniq = false, $priority = 1);

	public function get_tasks_list();
}

class StorageMemcache implements IStorage
{
	protected $mem;
	protected $key_semaphore = 'semaphore';
	protected $content_field_handler = 'handler';
	protected $content_field_data = 'data';

	public function __construct(\Jamm\Memory\IMemoryStorage $storage = null)
	{
		if (!empty($storage)) $this->mem = $storage;
		else $this->mem = new \Jamm\Memory\MemcacheObject('Tasks');
	}

	public function get_next_task()
	{
		$keys = $this->get_tasks_list();
		if (empty($keys)) return false;
		sort($keys);
		$key = (string)$keys[0];
		$content = unserialize($this->mem->read($key));
		$del = $this->mem->del($key);
		if (!$del) return false;
		if (empty($content)) return false;
		$task_class_name = '\\'.$content[$this->content_field_handler];
		if (empty($task_class_name) || !class_exists($task_class_name)) return false;
		/** @var ITask $task */
		$task = new $task_class_name;
		$task->restore($content[$this->content_field_data]);
		return $task;
	}

	public function semaphore_create()
	{
		return $this->mem->add($this->key_semaphore, 1, self::semaphore_life_time);
	}

	public function semaphore_delete()
	{
		$sem = $this->mem->read($this->key_semaphore);
		if (empty($sem)) return true;
		return $this->mem->del($this->key_semaphore);
	}

	public function semaphore_exists()
	{
		$sem = $this->mem->read($this->key_semaphore);
		if (empty($sem)) return false;
		return true;
	}

	public function store($handler_class_name, $data, $uniq = false, $priority = 1)
	{
		$content = serialize(array(
								  $this->content_field_handler => $handler_class_name,
								  $this->content_field_data => $data));
		$priority = '['.intval($priority).']';
		if ($uniq)
		{
			$hash = $priority.md5($content);
			return $this->mem->add($hash, $content);
		}
		else
		{
			$key = $t = $priority.(round(microtime(true)*1000));
			$i = 0;
			while ($this->mem->add($key, $content)===false)
			{
				$i++;
				if ($i > 1000) return false;
				$key = $t.$i;
			}
			return true;
		}
	}

	public function get_tasks_list()
	{
		$keys = $this->mem->get_keys();
		if (empty($keys)) return false;
		$semaphore_key = array_search($this->key_semaphore, $keys);
		if ($semaphore_key!==false) unset($keys[$semaphore_key]);
		if (empty($keys)) return false;
		return $keys;
	}
}

class StorageRedis extends StorageMemcache
{
	public function __construct(\Jamm\Memory\IMemoryStorage $storage = null)
	{
		if (!empty($storage)) $this->mem = $storage;
		else $this->mem = new \Jamm\Memory\RedisObject('Tasks');
	}
}

class StorageFiles implements IStorage
{
	protected $semaphore_file;
	protected $tasks_dir;
	protected $content_field_handler = 'handler';
	protected $content_field_data = 'data';

	public function __construct($tasks_dir = '')
	{
		if (!empty($tasks_dir)) $this->tasks_dir = realpath($tasks_dir);
		else $this->tasks_dir = TASKS_DIR;
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
				unlink($filepath);
				return $task;
			}
		}
		return false;
	}

	public function semaphore_create()
	{
		if (file_exists($this->semaphore_file)) return false;
		$fsem = fopen($this->semaphore_file, 'w');
		if (!$fsem) return false;
		fclose($fsem);
		return true;
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
			if (filemtime($this->semaphore_file) < (time()-self::semaphore_life_time))
			{
				unlink($this->semaphore_file);
				return false;
			}
			else return true;
		}
		else return false;
	}

	/**
	 * @param string $filepath
	 * @return bool|ITask
	 */
	private function read_task($filepath)
	{
		$content = file_get_contents($filepath);
		if (empty($content)) return false;
		$content = unserialize($content);
		if (empty($content)) return false;
		$task_class_name = '\\'.$content[$this->content_field_handler];
		if (empty($task_class_name) || !class_exists($task_class_name)) return false;
		/** @var ITask $task */
		$task = new $task_class_name;
		$data = unserialize(base64_decode($content[$this->content_field_data]));
		$task->restore($data);
		return $task;
	}

	public function store($handler_class_name, $data, $uniq = false, $priority = 1)
	{
		$data = base64_encode(serialize($data));
		$content = serialize(array(
								  $this->content_field_handler => $handler_class_name,
								  $this->content_field_data => $data));
		$priority = '['.intval($priority).']';
		if ($uniq)
		{
			$filename = $priority.md5($content);
			if (file_exists($filename)) return true;
		}
		else
		{
			$filename = $priority.$this->get_new_filename($this->tasks_dir.'/'.$priority);
		}

		$f = fopen($this->tasks_dir.'/'.$filename, 'w');
		if (!$f) return false;
		fwrite($f, $content);
		fclose($f);
		return true;
	}

	private function get_new_filename($dir)
	{
		$name = $t = round(microtime(1)*100);
		$i = 0;
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
}

class StorageManager
{
	/** @var IStorage */
	protected static $storage;

	/**
	 * @return IStorage
	 */
	public static function GetStorage()
	{
		if (empty(self::$storage))
		{
			if (class_exists('Jamm\\Memory\\RedisObject')) self::$storage = new StorageRedis();
			elseif (class_exists('Memcache')) self::$storage = new StorageMemcache();
			else self::$storage = new StorageFiles();
		}
		return self::$storage;
	}

	public static function setStorage(IStorage $storage)
	{
		self::$storage = $storage;
	}
}

interface ITask
{
	public function execute();

	public function restore($data);

	public function setStorage(IStorage $storage);
}

abstract class Task implements ITask
{
	protected $storage;

	final public function __construct()
	{ }

	/**
	 * @return IStorage
	 */
	public function getStorage()
	{
		if (empty($this->storage)) $this->storage = StorageManager::GetStorage();
		return $this->storage;
	}

	public function setStorage(IStorage $storage)
	{
		$this->storage = $storage;
	}
}
