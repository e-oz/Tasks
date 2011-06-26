<?php

//See code MailDelayed class in MailDelayed.php file

//To use in site's scripts:
$mailer = new \Jamm\Tasks\MailDelayed();
$mailer->Send('to@example.com', 'Re: Hello', 'Hi, To!', 3);
//3 - priority (higher digit means lower priority, 1 is default).

//That's all :)
