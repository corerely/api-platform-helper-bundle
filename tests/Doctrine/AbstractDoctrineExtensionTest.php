<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Tests\Doctrine;

use Corerely\ApiPlatformHelperBundle\Tests\Fixtures\Entity\Dummy;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class AbstractDoctrineExtensionTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected string $entityClassName = Dummy::class;

    protected Registry $managerRegistry;
    protected ObjectRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->managerRegistry = self::getContainer()->get('doctrine');
        $this->repository = $this->managerRegistry->getManagerForClass($this->entityClassName)->getRepository($this->entityClassName);
    }

    protected function createQueryBuilder(string $alias = 'o'): QueryBuilder
    {
        return $this->repository->createQueryBuilder($alias);
    }
}
