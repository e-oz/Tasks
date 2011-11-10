<?php
namespace Jamm\Tasks;

class MemStorage implements IStorage
{
	/** @var \Jamm\Memory\IMemoryStorage */
	protected $mem;
	protected $key_semaphore = 'semaphore';

	public function __construct(\Jamm\Memory\IMemoryStorage $storage)
	{
		$this->mem = $storage;
	}

	public function get_next_task()
	{
		$keys = $this->get_tasks_list();
		if (empty($keys)) return false;
		sort($keys);
		$key = (string)$keys[0];
		$task = $this->read_task($key);
		$del = $this->mem->del($key);
		if (!$del)
		{
			trigger_error('Can not delete task!', E_USER_WARNING);
			return false;
		}
		return $task;
	}

	public function read_task($id)
	{
		$content = $this->mem->read($id);
		if (empty($content)) return false;
		return unserialize($content);
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

	public function store($task_object, $unique = false, $priority = 1)
	{
		$content = serialize($task_object);
		$priority = '['.intval($priority).']';
		if ($unique)
		{
			$hash = '1'.md5($content);
			return $this->mem->add($hash, $content);
		}
		else
		{
			$key = $t = $priority.(round(microtime(true)*1000));
			$i = 0;
			while ($this->mem->add($key, $content)===false)
			{
				$i++;
				if ($i > 1000)
				{
					trigger_error('Can not add key more than 1000 times', E_USER_NOTICE);
					return false;
				}
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
