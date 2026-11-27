<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Tests\Support;

use OCA\FlzUrlaub\Service\OrganizationDefinitionGateway;
use OCA\FlzUrlaub\Service\VacationOrganizationDefinition;
use OCA\FlzOrganization\Organization\OrganizationDefinition;

final class FixedOrganizationGateway extends OrganizationDefinitionGateway
{
    public function __construct(private ?VacationOrganizationDefinition $value = null)
    {
    }

    public static function defaults(): self
    {
        return new self(VacationOrganizationDefinition::fromArray(
            OrganizationDefinition::defaults()->toArray(),
        ));
    }

    public function status(): string
    {
        return $this->value === null ? self::INVALID : self::VALID;
    }

    public function definition(): ?VacationOrganizationDefinition
    {
        return $this->value;
    }

    public function canManage(
        string $actorUid,
        array $actorGroups,
        string $targetUid,
        array $targetGroups,
        string $scope,
        bool $allowSelf = true,
    ): bool
    {
        if ($this->value === null || (!$allowSelf && $actorUid === $targetUid))
        {
            return false;
        }
        $definition = $this->value;
        if ($allowSelf && $actorUid === $targetUid)
        {
            return true;
        }
        $actors = $definition->roleKeysForGroups($actorGroups);
        $targets = $definition->roleKeysForGroups($targetGroups);
        foreach ($actors as $actor)
        {
            if (array_intersect($definition->hierarchy()[$actor] ?? [], $targets) === [])
            {
                continue;
            }
            if (!$definition->roleManagementIsAreaScoped($actor)
                || array_intersect($definition->areaGroupIds(), $actorGroups, $targetGroups) !== [])
            {
                return true;
            }
        }
        return false;
    }
}
