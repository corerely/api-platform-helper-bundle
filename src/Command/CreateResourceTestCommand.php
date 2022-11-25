<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Command;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Util\Inflector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'corerely:create-resource-test',
    description: 'Create a API Platform Resource test case',
)]
class CreateResourceTestCommand extends Command
{

    public function __construct(
        private readonly string                                 $projectDir,
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Resource Test Generator',
            '=======================',
            '',
        ]);
        $resource = $this->askResource($input, $output);

        $this->generateTest($resource);

        $io = new SymfonyStyle($input, $output);
        $io->success(sprintf('Generated test for "%s"', $resource));

        return Command::SUCCESS;
    }

    private function askResource(InputInterface $input, OutputInterface $output): string
    {
        $resources = iterator_to_array($this->resourceNameCollectionFactory->create());

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select resource',
            $resources,
        );
        $question->setErrorMessage('Resource %s is invalid.');

        return $helper->ask($input, $output, $question);
    }

    private function generateTest(string $resourceClassName): void
    {
        $reflectionClass = new \ReflectionClass($resourceClassName);
        $shortClassName = $reflectionClass->getShortName();
        // Remove "App\" from namespace
        $innerNamespace = substr($reflectionClass->getNamespaceName(), 4);

        // Create target file path from namespance
        $fileAbsPath = sprintf('%s/tests/%s/%sTest.php', $this->projectDir, $innerNamespace, $shortClassName);
        // If this is doctrine entity move to Resource folder
        $fileAbsPath = str_replace('/Entity/', '/Resource/', $fileAbsPath);

        $var = '$'.lcfirst($shortClassName);
        $factory = $shortClassName.'Factory';
        $url = Inflector::tableize(Inflector::pluralize($shortClassName));

        $namespace = sprintf('App\\Tests\\%s', $innerNamespace);
        // Change namespace to Resource of doctrine entity too
        $namespace = str_replace('\\Entity', '\\Resource', $namespace);

        $hasUuid = property_exists($resourceClassName, 'uuid');
        $idGetter = $hasUuid ? 'getUuid()' : 'getId()';

        $file = fopen($fileAbsPath, 'w');
        $content = '
<?php
declare(strict_types=1);

namespace %namespace%;

use %resourceClassName%;
use App\Factory\%factory%;
use App\Tests\AbstractApiTestCase;'.($hasUuid ? (PHP_EOL.'use Symfony\Component\Uid\Uuid;') : '').'

class %shortClassName%Test extends AbstractApiTestCase
{
    private string $url = \'/api/%url%\';

    public function testGetCollection(): void
    {
        %factory%::createMany(5);

        $this->getClient()->get($this->url);

        self::assertResponseIsSuccessful();
    }

    public function testGetItem(): void
    {
        %var% = %factory%::createOne();

        $this->getClient()->get($this->url.\'/\'.%var%->%idGetter%);

        self::assertResponseIsSuccessful();
        self::assertJsonContains($this->serializeEntity(%var%, [
            /* @TODO add fields */ 
        ]));
    }

    public function testCreate(): void
    {
        $data = [
            // @TODO Add data
           '.($hasUuid ? (PHP_EOL.'\'uuid\' => (string)Uuid::v4(),') : '').'
        ];
        
        %factory%::assert()->empty();

        $this->getClient()->asAdmin()->post($this->url, $data);

        self::assertResponseIsCreated();
        %factory%::assert()->count(1);

        self::assertJsonContains($data);
    }

    public function testEditItem(): void
    {
        %var% = %factory%::createOne();

        $data = [
            // Edit data
        ];
        $this->getClient()->asAdmin()->put($this->url.\'/\'.%var%->%idGetter%, $data);

        self::assertResponseIsSuccessful();
        self::assertJsonContains($data);
    }

    public function testDelete(): void
    {
        %var% = %factory%::createOne();

        %factory%::assert()->count(1);

        $this->getClient()->asAdmin()->delete($this->url.\'/\'.%var%->%idGetter%);

        self::assertResponseIsNoContent();
        %factory%::assert()->empty();
    }

    /**
     * @dataProvider collectionMethodDataProvider
     */
    public function testCollectionEndpointsAsUser(string $method): void
    {
        %factory%::createMany(2);

        $this->getClient()->{$method}($this->url);

        self::assertResponseIsForbidden();
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

        $this->getClient()->{$method}($this->url.\'/\'.%var%->%idGetter%);

        self::assertResponseIsForbidden();
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
            '%resourceClassName%' => $resourceClassName,
            '%idGetter%' => $idGetter,
        ];

        fwrite($file, str_replace(array_keys($arguments), array_values($arguments), ltrim($content)));
        fclose($file);
    }
}
