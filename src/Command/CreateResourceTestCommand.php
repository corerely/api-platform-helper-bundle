<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Command;

use ApiPlatform\Util\Inflector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'corerely:create-resource-test',
    description: 'Create a API Platform Resource test case',
)]
class CreateResourceTestCommand extends Command
{

    public function __construct(private string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entityClassName', InputArgument::REQUIRED, 'ApiPlatform Resource/Entity class name to test')
            ->addArgument('targetDir', InputArgument::OPTIONAL, 'Target folder in tests folder', 'Resource');
    }

    protected function normalizeEntityName(string $entityClassName): string
    {
        if (!str_contains($entityClassName, '\\')) {
            return 'App\\Entity\\'.$entityClassName;
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
        $targetDir = trim($input->getArgument('targetDir'), '/');

        if (!class_exists($entityClassName)) {
            throw new \Exception(sprintf('class %s does not exist', $entityClassName));
        }

        $io = new SymfonyStyle($input, $output);
        $message = sprintf('Generated test for "%s"', $entityClassName);
        $io->success($message);

        $this->generateTest($entityClassName, $targetDir);

        return Command::SUCCESS;
    }

    private function generateTest(string $entityClassName, string $targetDir): void
    {
        $reflectionClass = new \ReflectionClass($entityClassName);
        $shortClassName = $reflectionClass->getShortName();

        $var = '$'.lcfirst($shortClassName);
        $factory = $shortClassName.'Factory';
        $url = Inflector::tableize(Inflector::pluralize($shortClassName));

        $namespace = str_replace('App\\', 'App\\Tests\\', $reflectionClass->getNamespaceName());
        $namespace = str_replace('\\Entity', '\\Resource', $namespace);

        $hasUuid = property_exists($entityClassName, 'uuid');
        $idGetter = $hasUuid ? 'getUuid()' : 'getId()';

        $file = fopen(sprintf('%s/tests/%s/%sTest.php', $this->projectDir, $targetDir, $shortClassName), 'w');
        $content = '
<?php
declare(strict_types=1);

namespace App\Tests\%namespace%;

use %entityClassName%;
use App\Factory\%factory%;
use App\Tests\AbstractApiTestCase;'.($hasUuid ? (PHP_EOL.'use Symfony\Component\Uid\Uuid;') : '').'

class %shortClassName%Test extends ApiTestCase
{
    private string $url = \'/api/%url%\';

    public function testGetCollection(): void
    {
        %factory%::createMany(5);

        $this->getClient()->get($this->url);

        $this->assertResponseIsSuccessful();
    }

    public function testGetItem(): void
    {
        %var% = %factory%::createOne();

        $this->getClient()->get($this->url . \'/\' . %var%->%idGetter%);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains($this->serializeEntity(%var%, [
            /* @TODO add fields */ 
        ]));
    }

    public function testCreate(): void
    {
        $data = [
            \'uuid\' => (string)Uuid::v4(),
            // @TODO Add data
        ];
        
        %factory%::assert()->empty();

        $this->getClient()->asAdmin()->post($this->url, $data);

        $this->assertResponseIsCreated();
        %factory%::assert()->count(1);

        $this->assertJsonContains($data);
    }

    public function testEditItem(): void
    {
        %var% = %factory%::createOne();

        $data = [
            // Edit data
        ];
        $this->getClient()->asAdmin()->put($this->url . \'/\' . %var%->%idGetter%, $data);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains($data);
    }

    public function testDelete(): void
    {
        %var% = %factory%::createOne();

        %factory%::assert()->count(1);

        $this->getClient()->asAdmin()->delete($this->url . \'/\' . %var%->%idGetter%);

        $this->assertResponseIsNoContent();
        %factory%::assert()->empty();
    }

    /**
     * @dataProvider collectionMethodDataProvider
     */
    public function testCollectionEndpointsAsUser(string $method): void
    {
        %factory%::createMany(2);

        $this->getClient()->{$method}($this->url);

        $this->assertResponseIsForbidden();
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

        $this->getClient()->{$method}($this->url . \'/\' . %var%->%idGetter%);

        $this->assertResponseIsForbidden();
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
            '%url%' => $url,
            '%namespace%' => $namespace,
            '%entityClassName%' => $entityClassName,
            '%idGetter%' => $idGetter,
        ];

        fwrite($file, str_replace(array_keys($arguments), array_values($arguments), ltrim($content)));
        fclose($file);
    }
}
