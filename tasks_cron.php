<?php
namespace Jamm\Tasks;

//This 2 lines will be enough, all another - just examples
$executor = new Executor(new MemStorage(new \Jamm\Memory\RedisObject('tasks')));
$executor->Start(300);

//300 seconds executor will be wait for new tasks in loop,
//so this script can be executed by cron each 5 minutes,
//to create permanent execution of tasks.
