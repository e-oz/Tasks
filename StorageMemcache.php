<?php
namespace Jamm\Tasks;

class StorageMemcache extends MemStorage
{
	protected function getDefaultStorage()
	{
		return new \Jamm\Memory\MemcacheObject('Tasks');
	}
}
