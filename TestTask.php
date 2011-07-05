<?php
namespace Jamm\Tasks;

class TestTask extends Task
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
		$GLOBALS[Test::execution_globals_key]['title'] = $this->title;
		$GLOBALS[Test::execution_globals_key]['descr'] = $this->descr;
	}
}
