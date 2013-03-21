#Tasks
Simple way to manage delayed execution in PHP scripts  
[![Build Status](https://travis-ci.org/jamm/Tasks.png)](https://travis-ci.org/jamm/Tasks)    

##Where can it be used
It can be used to execute delayed tasks - tasks, which takes long time to execute and result of their execution isn't necessary to generate output of requested script.  
For example, it can be sending mail, or long database queries, or checking some info (IP whois data), delayed collaborating with remote API.

##How to use
**Tasks** it's a base to build your task-handlers.  
Task-handlers it's classes, which will store tasks (when called from script) and execute restored tasks (when called from cron-script).  
See example of task-handler in MailDelayed.php  

To execute tasks, \Tasks\Executor should be called, it's usual work for cron - see [tasks_cron.php](https://github.com/jamm/Tasks/blob/master/tasks_cron.php) for example.

##Examples
###How to create Task-class
See [MailDelayed.php](https://github.com/jamm/Tasks/blob/master/MailDelayed.php)

###How to use Task-class

	$taskStorage = new MemStorage(new \Jamm\Memory\RedisObject('tasks'));
	
	$mailer = new MailDelayed($taskStorage);
	$mailer->Send('to@example.com', 'Re: Hello', 'Hi, To!', 3);

##Requirements
###PHP version: 5.3+

###To store tasks in memory:
Any object, implements Jamm\\Memory\\IMemoryStorage.
For example, Jamm\\Memory\\RedisObject

###Files storage
No any external classes are required 

TODO:
=====
* Track results of tasks execution
* Auto-repeated tasks
