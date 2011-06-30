<?php
namespace Jamm\Tasks;

abstract class MemStorage implements IStorage
{
	/** @var \Jamm\Memory\IMemoryStorage */
	protected $mem;
	protected $key_semaphore = 'semaphore';
	protected $content_field_handler = 'handler';
	protected $content_field_data = 'data';

	public function __construct(\Jamm\Memory\IMemoryStorage $storage = null)
	{
		if (!empty($storage)) $this->mem = $storage;
		else $this->mem = $this->getDefaultStorage();
	}

	abstract protected function getDefaultStorage();

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
