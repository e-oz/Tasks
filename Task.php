<?php
namespace Jamm\Tasks;
abstract class Task implements ITask
{
	private $_storage;

	public function __construct(IStorage $storage)
	{
		$this->_storage = $storage;
	}

	/**
	 * @return IStorage
	 */
	public function getStorage()
	{
		return $this->_storage;
	}

	public function setStorage(IStorage $storage)
	{
		$this->_storage = $storage;
	}

	public function __sleep()
	{
		//to not save 'storage' object
		$my = get_class_vars(__CLASS__);
		$c  = get_object_vars($this);
		foreach ($my as $k => $v) unset($c[$k]);
		return array_keys($c);
	}

	/**
	 * Store this Task (all properties)
	 * @param bool $unique
	 * @param int $priority
	 * @return bool
	 */
	public function store($unique = false, $priority = 1)
	{
		return $this->getStorage()->store($this, $unique, $priority);
	}
}
