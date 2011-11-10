<?php
namespace Jamm\Tasks;

/**
 * Example of a simple Task-class
 */
class MailDelayed extends Task
{
	protected $to;
	protected $subject;
	protected $message;

	public function Send($to, $subject, $message, $priority = 1)
	{
		$this->to = $to;
		$this->subject = $subject;
		$this->message = $message;

		//save task in storage
		return $this->store(true, $priority);
	}

	public function execute()
	{
		//execute this simple task
		mail($this->to, $this->subject, $this->message);
	}
}
