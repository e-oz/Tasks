<?php
namespace Jamm\Tasks;
//This 2 lines will be enough, all another - just examples
$executor = new Executor();
$executor->Start(300);

//300 seconds executor will be wait for new tasks in loop,
//so this script can be executed by cron each 5 minutes,
//to create permanent execution of tasks.

//Example, how can be choosed another storage in cron:
$files_executor = new Executor(new StorageFiles());
$files_executor->Start(5);

//or via StorageManager:
StorageManager::setStorage(new StorageMemcache());
$executor = new Executor();
$executor->Start();
