<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Command;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateResourceTestCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('corerely:create-resource-test');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'entityClassName' => Dummy::class,
            'targetDir' => '../Fixtures',
        ]);

        $commandTester->assertCommandIsSuccessful();
        self::assertFileExists(__DIR__.'/../Fixtures/DummyTest.php');
    }

    protected function tearDown(): void
    {
        @unlink(__DIR__.'/../Fixtures/DummyTest.php');
    }
}
