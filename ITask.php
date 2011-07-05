<?php
namespace Jamm\Tasks;

interface ITask
{
	public function execute();

	public function setStorage(IStorage $storage);

	/** @return IStorage */
	public function getStorage();
}
