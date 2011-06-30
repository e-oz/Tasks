<?php
namespace Jamm\Tasks;

interface ITask
{
	public function execute();

	public function restore($data);

	public function setStorage(IStorage $storage);
}
