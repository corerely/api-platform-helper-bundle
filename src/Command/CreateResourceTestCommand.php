<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Command;

use ApiPlatform\Core\Util\Inflector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateResourceTestCommand extends Command
{
    // @codingStandardsIgnoreStart
    protected static $defaultName = 'corerely:create-resource-test';
    protected static $defaultDescription = 'Create a API Platform Resource test case';

    // @codingStandardsIgnoreEnd

    public function __construct(private string $targetDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entityClassName', InputArgument::REQUIRED, 'ApiPlatform Resource/Entity class name to test');
    }

    protected function normalizeEntityName(string $entityClassName): string
    {
        if (!str_contains($entityClassName, '\\')) {
            return 'App\\Entity\\' . $entityClassName;
        }

        return $entityClassName;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Resource Test Generator',
            '=======================',
            '',
        ]);
        $entityClassName = $this->normalizeEntityName($input->getArgument('entityClassName'));
        if (!class_exists($entityClassName)) {
            throw new \Exception(sprintf('class %s does not exist', $entityClassName));
        }

        $io = new SymfonyStyle($input, $output);
        $message = sprintf('Generated test for "%s"', $entityClassName);
        $io->success($message);

        $this->generateTest($entityClassName);

        return Command::SUCCESS;
    }

    private function generateTest(string $entityClassName): void
    {
        $shortClassName = (new \ReflectionClass($entityClassName))->getShortName();
        $var = '$' . lcfirst($shortClassName);
        $factory = $shortClassName . 'Factory';
        $pluralize = strtolower(Inflector::pluralize($shortClassName));
        $folder = strrev(explode('/', strrev($this->targetDir))[0]);

        $file = fopen(sprintf('%s/%sTest.php', $this->targetDir, $shortClassName), 'w');
        $content = '
<?php
declare(strict_types=1);

namespace App\Tests\%folder%;

use %entityClassName%;
use App\Factory\%factory%;
use App\Tests\AbstractApiTestCase;
use Symfony\Component\Uid\Uuid;

class %shortClassName%Test extends AbstractApiTestCase
{
    private string $url = \'/api/%pluralize%\';

    public function testGetCollection(): void
    {
        %factory%::createMany(5);

        $this->getClient()->get($this->url);

        $this->assertResponseIsSuccessful();
    }

    public function testGetItem(): void
    {
        %var% = %factory%::createOne();

        $this->getClient()->get($this->url . \'/\' . %var%->getUuid());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains($this->serializeEntity(%var%, [
            /* @TODO add fields */ 
        ]));
    }

    public function testCreate(): void
    {
        %factory%::assert()->empty();

        $data = [
            \'uuid\' => (string)Uuid::v4(),
            // @TODO Add data
        ];
        $this->getClient()->asAdmin()->post($this->url, $data);

        $this->assertResponseStatusCodeSame(201);
        %factory%::assert()->count(1);

        $this->assertJsonContains($data);
    }

    public function testEditItem(): void
    {
        %var% = %factory%::createOne();

        $data = [
            // Edit data
        ];
        $this->getClient()->asAdmin()->put($this->url . \'/\' . %var%->getUuid(), $data);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains($data);
    }

    public function testDelete(): void
    {
        %var% = %factory%::createOne();

        %factory%::assert()->count(1);

        $this->getClient()->asAdmin()->delete($this->url . \'/\' . %var%->getUuid());

        $this->assertResponseStatusCodeSame(204);
        %factory%::assert()->empty();
    }

    /**
     * @dataProvider collectionMethodDataProvider
     */
    public function testCollectionEndpointsAsUser(string $method): void
    {
        %factory%::createMany(2);

        $this->getClient()->{$method}($this->url);

        $this->assertResponseStatusCodeSame(403);
    }

    public function collectionMethodDataProvider(): iterable
    {
        yield \'get_collection\' => [\'get\'];
        yield \'create_collection\' => [\'post\'];
    }

    /**
     * @dataProvider itemMethodDataProvider
     */
    public function testItemEndpointsAsUser(string $method): void
    {
        %var% = %factory%::createOne();

        $this->getClient()->{$method}($this->url . \'/\' . %var%->getUuid());

        $this->assertResponseStatusCodeSame(403);
    }

    public function itemMethodDataProvider(): iterable
    {
        yield \'get_item\' => [\'get\'];
        yield \'edit_item\' => [\'put\'];
        yield \'delete_item\' => [\'delete\'];
    }
}
';

        $arguments = [
            '%shortClassName%' => $shortClassName,
            '%var%' => $var,
            '%factory%' => $factory,
            '%pluralize%' => $pluralize,
            '%folder%' => $folder,
            '%entityClassName%' => $entityClassName,
        ];

        fwrite($file, str_replace(array_keys($arguments), array_values($arguments), ltrim($content)));
        fclose($file);
    }
}
