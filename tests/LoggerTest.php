<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Logger;

final class LoggerTest extends TestCase
{
    public function testCloudOutput(): void
    {
        $this->expectOutputString('id: message');
        $logger = new Logger('id');
        $logger->error('message');
    }

    public function testCloudOutputException(): void
    {
        // phpcs:disable
        $expected = <<<OUT
id: messageid: exid: #0 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/Framework/TestCase.php(1413): Oliver\LoggerTest->testCloudOutputException()
#1 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/Framework/TestCase.php(1030): PHPUnit\Framework\TestCase->runTest()
#2 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/Framework/TestResult.php(692): PHPUnit\Framework\TestCase->runBare()
#3 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/Framework/TestCase.php(771): PHPUnit\Framework\TestResult->run()
#4 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/Framework/TestSuite.php(638): PHPUnit\Framework\TestCase->run()
#5 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/Framework/TestSuite.php(638): PHPUnit\Framework\TestSuite->run()
#6 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/Framework/TestSuite.php(638): PHPUnit\Framework\TestSuite->run()
#7 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/TextUI/TestRunner.php(651): PHPUnit\Framework\TestSuite->run()
#8 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/TextUI/Command.php(108): PHPUnit\TextUI\TestRunner->run()
#9 /home/denis/Documents/oliver/vendor/phpunit/phpunit/src/TextUI/Command.php(68): PHPUnit\TextUI\Command->run()
#10 /home/denis/Documents/oliver/vendor/phpunit/phpunit/phpunit(61): PHPUnit\TextUI\Command::main()
#11 {main}
OUT;
        // phpcs:enable
        $this->expectOutputString($expected);
        $logger = new Logger('id');
        $logger->error('message', ['exception' => new \Exception('ex')]);
    }

    public function testNoOutput(): void
    {
        $this->expectOutputString('');
        $logger = new Logger('');
        $logger->error('message');
    }
}
