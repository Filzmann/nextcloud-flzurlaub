<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

/** Read-only projection of vacation authorization settings from flz_organization V1. */
class VacationSettingsService
{
    public const VALID = OrganizationDefinitionGateway::VALID;
    public const MISSING = OrganizationDefinitionGateway::MISSING;
    public const DISABLED = OrganizationDefinitionGateway::DISABLED;
    public const INCOMPATIBLE = OrganizationDefinitionGateway::INCOMPATIBLE;
    public const INVALID = OrganizationDefinitionGateway::INVALID;
    public const UNAVAILABLE = OrganizationDefinitionGateway::UNAVAILABLE;

    public function __construct(private OrganizationDefinitionGateway $organization)
    {
    }

    public function status(): string
    {
        return $this->organization->status();
    }

    /** @return list<string> */
    public function enabledPeerEditGroups(): array
    {
        return $this->status() === self::VALID
            ? $this->organization->peerGroups('vacation.edit')
            : [];
    }

    /** @return list<string> */
    public function enabledPeerApprovalGroups(): array
    {
        return $this->status() === self::VALID
            ? $this->organization->peerGroups('vacation.approve')
            : [];
    }

    /** @return list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}> */
    public function ebAreaViews(): array
    {
        if ($this->status() !== self::VALID)
        {
            return [];
        }
        $definition = $this->organization->definition();
        $eb = $definition?->roles()['eb'] ?? null;
        if (!is_array($eb)
            || !is_string($eb['groupId'] ?? null)
            || ($eb['areaScoped'] ?? false) !== true)
        {
            return [];
        }

        $areas = $definition->areas();
        uasort(
            $areas,
            static fn(array $left, array $right): int => ($left['sortOrder'] ?? 0) <=> ($right['sortOrder'] ?? 0),
        );
        $views = [];
        foreach ($areas as $areaKey => $area)
        {
            if (!is_string($area['groupId'] ?? null) || !is_string($area['label'] ?? null))
            {
                return [];
            }
            $views[] = [
                'id' => 'eb-' . $areaKey,
                'label' => 'EB – ' . $area['label'],
                'roleKey' => 'eb',
                'roleGroupId' => $eb['groupId'],
                'areaKey' => (string)$areaKey,
                'areaGroupId' => $area['groupId'],
            ];
        }
        return $views;
    }
}
