#Tasks
Simple way to manage delayed execution in PHP scripts

##Where can it be used
It can be used to execute delayed tasks - tasks, which takes long time to execute and result of their execution isn't necessary to generate output of requested script.
For example, it can be sending mail, or long database queries, or checking some info (IP whois data), delayed collaborating with remote API.

##How to use
**Tasks** it's base to build your task-handlers.
Task-handlers it's classes, which will store tasks (when called from script) and execute restored tasks (when called from cron-script).
See example of task-handler in MailDelayed.inc

To execute tasks, \Tasks\Executor should be called, it's usual work for cron - see [tasks_cron.php](https://github.com/jamm/Tasks/blob/master/tasks_cron.php) for example.

##Examples
See [demo.php](https://github.com/jamm/Tasks/blob/master/demo.php) (how to use in scripts) and [MailDelayed.inc](https://github.com/jamm/Tasks/blob/master/MailDelayed.inc) (how to create Task-handler)

##Requrements
###PHP version: 5.3+
###Memcache storage
[MemcacheObject](https://github.com/jamm/memory/blob/master/memcache.inc) used as storage

###Files storage
To use files storage for tasks, define constant TASKS_DIR - path to the folder for task-files

###Default storage
If Memcache class exists, Storage_Memcache will be used as default, else - Storage_Files
