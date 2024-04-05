<?php
declare(strict_types=1);

namespace Corerely\ApiPlatformHelperBundle\Doctrine;

enum IdentifierMode: string
{
    case ID = 'id';
    case UUID = 'uuid';

    public function identifierColumnName(): string
    {
        return $this->value;
    }

    public function isUuid(): bool
    {
        return $this === self::UUID;
    }
}
