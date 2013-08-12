<?php
namespace Jamm\Tasks;

class MemStorage implements IStorage
{
	/** @var \Jamm\Memory\IMemoryStorage */
	private $mem;
	private $key_semaphore = 'semaphore';
	private $semaphore_life_time = 180;

	public function __construct(\Jamm\Memory\IMemoryStorage $storage)
	{
		$this->mem = $storage;
	}

	public function get_next_task()
	{
		$keys = $this->get_tasks_list();
		if (empty($keys)) {
			return false;
		}
		sort($keys);
		$key = (string)current($keys);
		if (empty($key)) {
			return false;
		}
		$task = $this->read_task($key);
		$del  = $this->mem->del($key);
		if (!$del) {
			trigger_error("Can not delete task $key!", E_USER_WARNING);
			return false;
		}
		if (empty($task)) {
			trigger_error("Can't unpack task from key $key", E_USER_WARNING);
			return false;
		}
		return $task;
	}

	public function read_task($id)
	{
		$content = $this->mem->read($id);
		if (empty($content)) {
			return false;
		}
		return unserialize($content);
	}

	public function semaphore_create()
	{
		return $this->mem->add($this->key_semaphore, $this->mem->get_ID(), $this->semaphore_life_time);
	}

	public function semaphore_delete()
	{
		$sem = $this->mem->read($this->key_semaphore);
		if (empty($sem)) {
			return true;
		}
		return $this->mem->del($this->key_semaphore);
	}

	public function semaphore_exists()
	{
		$sem = $this->mem->read($this->key_semaphore);
		if (empty($sem)) {
			return false;
		}
		return true;
	}

	public function store($task_object, $unique = false, $priority = 1)
	{
		$content  = serialize($task_object);
		$priority = '['.intval($priority).']';
		if ($unique) {
			$hash = '1'.md5($content);
			return $this->mem->add($hash, $content);
		}
		else {
			$key = $t = $priority.(round(microtime(true)*1000));
			$i   = 0;
			while ($this->mem->add($key, $content) === false) {
				$i++;
				if ($i > 1000) {
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
		if (empty($keys)) {
			return false;
		}
		$semaphore_key = array_search($this->key_semaphore, $keys);
		if ($semaphore_key !== false) {
			unset($keys[$semaphore_key]);
		}
		if (empty($keys)) {
			return false;
		}
		return $keys;
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
