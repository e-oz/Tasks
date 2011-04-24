<?php

//This 2 lines will be enough, all another - just examples
$executor = new \Tasks\Executor();
$executor->Start(300);

//300 seconds executor will be wait for new tasks in loop,
//so this script can be executed by cron each 5 minutes,
//to create permanent execution of tasks.

//Example, how can be choosed another storage in cron:
$files_executor = new \Tasks\Executor(new \Tasks\Storage_Files());
$files_executor->Start(5);

//or via StorageManager:
\Tasks\StorageManager::setStorage(new \Tasks\Storage_Memcache());
$executor = new \Tasks\Executor();
$executor->Start();
