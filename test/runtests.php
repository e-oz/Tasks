<?php
include __DIR__.'/../vendors/Autoload/lib/Jamm/Autoload/Autoloader.php';
$Autoloader = new Jamm\Autoload\Autoloader(false);
$Autoloader->set_modules_dir(__DIR__.'/../vendors');
$Autoloader->register_namespace_dir('Jamm\\Tasks', __DIR__.'/../');
$Autoloader->start();

$RedisServer = new \Jamm\Memory\RedisServer();
$RedisServer->FlushAll();
$Storage = new \Jamm\Tasks\MemStorage(new \Jamm\Memory\RedisObject('Travis', $RedisServer));
$Test    = new \Jamm\Tasks\Tests\Test($Storage);
$Printer = new \Jamm\Tester\ResultsPrinter();
$Test->RunTests();
/** @var \Jamm\Tester\Test[] $tests */
$tests = $Test->getTests();
$Printer->addTests($tests);
$Printer->printResultsLine();

foreach ($Test->getTests() as $test_result)
{
	if (!$test_result->isSuccessful())
	{
		$Printer->printFailedTests();
		exit(1);
	}
}
exit(0);
