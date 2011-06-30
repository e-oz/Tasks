<?php
namespace Jamm\Tasks;

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
