<?php
namespace Jamm\Tasks;

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
