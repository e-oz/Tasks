<?php
namespace Jamm\Tasks;

//See code MailDelayed class in MailDelayed.php file
$taskStorage = new MemStorage(new \Jamm\Memory\RedisObject('tasks'));
//To use in site's scripts:
$mailer = new MailDelayed($taskStorage);
$mailer->Send('to@example.com', 'Re: Hello', 'Hi, To!', 3);
//3 - priority (higher digit means lower priority, 1 is default).
//That's all :)
