<?php
namespace Jamm\Tasks;

class StorageFiles implements IStorage
{
	protected $semaphore_file;
	protected $tasks_dir;
	protected $content_field_handler = 'handler';
	protected $content_field_data = 'data';

	public function __construct($tasks_dir = '')
	{
		if (!empty($tasks_dir)) $this->tasks_dir = realpath($tasks_dir);
		else $this->tasks_dir = FILES_DIR;
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
