# Api Platform Helpers Bundler

This bundle provides extensions and filters that are common needed in Api Platform projects.

## Installation

Run composer install

```shell
composer req corerely/api-platform-helper-bundle
```

Add basic params to `services.yaml`

```yaml

parameters:
    corerely.api_platform_helper.order_by_param_name: 'order'
    corerely.api_platform_helper.order_by_fields: ['createdAt']
```

## Filters

1. `corerely.api_platform_helper.doctrine.text_search_filter` - text search filter, do "like" query for configured properties. Default search query param is `q`. `/api/dummies?q=searchText` 
2. `corerely.api_platform_helper.doctrine.uuid_filter` - use filter if you need to filter by `uuid` property or association that has `uuid` identifier.

## Extensions

1. `corerely.api_platform_helper.doctrine.identifier_collection_filter_extension` - enables filter by `id` for all resources.
2. `corerely.api_platform_helper.doctrine.order_by_fields_extension` - enables ordering filter for all resources by defined fields in configuration.

Order extension require basic configuration.
```yaml
parameters:
    # Example of query. Like "?order[createdAt]=asc"
    corerely.api_platform_helper.order_by_param_name: 'order'
    # Array of order fields that are enabled for all resources
    corerely.api_platform_helper.order_by_fields: ['createdAt']
```

3. `corerely.api_platform_helper.doctrine.permanent_filter_extension` - helper extension that can enable permanent filter.

Permanent filter is always applied to collection and item query builder unlike regular Api Platform does.
This might be useful if you need for example to show records only for owner.

- Create permanent filter that implements `Corerely\ApiPlatformHelperBundle\Doctrine\PermanentFilter\PermanentFilterInterface`

```php
<?php
declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class OwnerPermanentFilter implements PermanentFilterInterface
{
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = [], array $options = [], array $identifiers = null): void
    {
        $rootAlis = $queryBuilder->getRootAliases()[0];
        $param = $queryNameGenerator->generateParameterName('owner');
        $user = null; // Logic to get owner
        
        $queryBuilder->andWhere(sprintf('%s.owner = :%s', $rootAlis, $param));
        $queryBuilder->setParameter($param, $user);
    }
}
```
- Enable filter for entity

```php
<?php
declare(strict_types=1);

namespace App\Entity;

use Corerely\ApiPlatformHelperBundle\Annotation\ApiPermanentFilter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiPermanentFilter(OwnerPermanentFilter::class)]
class Dummy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    public  function getId(): ?int
    {
        return $this->id;
    }
}
```

## Commands

### Create resource test command

Run command to create new resource test

```shell
# Use entity short name if namespace is App\Entity or full name otherwise
php ./bin/console corerely:create-resource-test EntityName
```

This will create test for entity resource `App\Entity\EntityName` in `corerely.api_platform_helper.resources_test_folder` folder.

**Create abstract class for test**

```php
<?php
declare(strict_types=1);

namespace App\Tests;

use App\Factory\UserFactory;
use Corerely\ApiPlatformHelperBundle\Test\UserManagerInterface;

abstract class AbstractApiTestCase extends \Corerely\ApiPlatformHelperBundle\Test\ApiTestCase
{
    protected function getUserManager(): UserManagerInterface
    {
        return new class implements UserManagerInterface {

            public function getRegularUser(): object
            {
                return UserFactory::findOrCreate(['email' => UserFactory::DEFAULT_USER]);
            }

            public function getAdminUser(): object
            {
                return UserFactory::repository()->findOneBy(['email' => UserFactory::ADMIN]) ?? UserFactory::new()->withAdmin()->create();
            }
        };
    }
}
```
