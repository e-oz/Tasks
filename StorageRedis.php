<?php
namespace Jamm\Tasks;

class StorageRedis extends MemStorage
{
	protected function getDefaultStorage()
	{
		return new \Jamm\Memory\RedisObject('Tasks');
	}
}
