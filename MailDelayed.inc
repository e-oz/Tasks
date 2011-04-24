<?php
namespace Tasks;

//example of simple Task-class
class MailDelayed extends Task
{
	protected $to;
	protected $subject;
	protected $message;

	public function Send($to, $subject, $message, $priority = 1)
	{
		//save params in storage
		$this->getStorage()->store(__CLASS__, array($to, $subject, $message), true, $priority);
	}

	public function execute()
	{
		//execute this simple task
		mail($this->to, $this->subject, $this->message);
	}

	public function restore($data)
	{
		//read params from storage
		list($this->to, $this->subject, $this->message) = $data;
	}
}

