<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Annotation;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class ApiPermanentFilter
{
    public function __construct(public string $filterClassName, public array $options)
    {
    }
}
