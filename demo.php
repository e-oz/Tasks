<?php

//See code MailDelayed class in MailDelayed.inc file

//To use in site's scripts:
$mailer = new \Tasks\MailDelayed();
$mailer->Send('to@example.com', 'Re: Hello', 'Hi, To!', 3);
//3 - priority (higher digit means lower priority, 1 is default).

//That's all :)
