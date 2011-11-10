<?php
namespace Jamm\Tasks\Tests;

class TestTask extends \Jamm\Tasks\Task
{
	protected $title;
	protected $descr;

	public function Add($title, $descr = '', $unique = false, $priority = 1)
	{
		$this->title = $title;
		$this->descr = $descr;
		return $this->store($unique, $priority);
	}

	public function execute()
	{
		print $this->title.$this->descr;
	}
}
