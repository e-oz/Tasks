#Tasks
Simple way to manage delayed execution in PHP scripts

##Where can it be used
It can be used to execute delayed tasks - tasks, which takes long time to execute and result of their execution isn't necessary to generate output of requested script.  
For example, it can be sending mail, or long database queries, or checking some info (IP whois data), delayed collaborating with remote API.

##How to use
**Tasks** it's a base to build your task-handlers.  
Task-handlers it's classes, which will store tasks (when called from script) and execute restored tasks (when called from cron-script).  
See example of task-handler in MailDelayed.php  

To execute tasks, \Tasks\Executor should be called, it's usual work for cron - see [tasks_cron.php](https://github.com/jamm/Tasks/blob/master/tasks_cron.php) for example.

##Examples
See [demo.php](https://github.com/jamm/Tasks/blob/master/demo.php) (how to use in scripts) and [MailDelayed.php](https://github.com/jamm/Tasks/blob/master/MailDelayed.php) (how to create Task-handler)

##Requirements
###PHP version: 5.3+

###For Redis storage
[Redis](http://redis.io) server should be installed (in debian/ubuntu: "apt-get install redis-server").
[RedisObject](https://github.com/jamm/memory/blob/master/RedisObject.php) is used as a storage.

###For Memcache storage
[Memcache](http://pecl.php.net/package/memcache) or [Memcached](http://pecl.php.net/package/memcached) PHP extension should be installed.  
[MemcacheObject](https://github.com/jamm/memory/blob/master/memcache.php) is used as a storage.

###Files storage
To use files for the tasks, define constant TASKS_DIR - path to the folder for task-files.

###Default storage
First possible:
1. Redis    
2. Memcache    
3. Files  

TODO:
=====
* Track results of tasks execution
* Auto-repeated tasks
* Rewrite tests to phpunit
