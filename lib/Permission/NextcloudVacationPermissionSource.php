<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Permission;

use OCA\FlzUrlaub\Service\OrganizationDefinitionGateway;
use OCA\FlzUrlaub\Service\VacationOrganizationDefinition;
use OCA\FlzUrlaub\Service\VacationSettingsService;
use OCP\IGroupManager;

final class NextcloudVacationPermissionSource implements VacationPermissionSourceInterface
{
    public function __construct(
        private IGroupManager $groups,
        private OrganizationDefinitionGateway $organization,
        private VacationSettingsService $settings,
    )
    {
    }

    public function definition(): ?VacationOrganizationDefinition
    {
        return $this->organization->definition();
    }

    public function teamGroupIds(): array
    {
        $definition = $this->definition();
        if ($definition === null)
        {
            return [];
        }
        $prefix = $definition->teamGroupPrefix();
        $ids = [];
        foreach ($this->groups->search($prefix, 10000, 0) as $group)
        {
            $id = (string)$group->getGID();
            if ($id !== $prefix && str_starts_with($id, $prefix))
            {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NATURAL | SORT_FLAG_CASE);
        return $ids;
    }

    public function enabledPeerEditGroups(): array
    {
        return $this->settings->enabledPeerEditGroups();
    }

    public function enabledPeerApprovalGroups(): array
    {
        return $this->settings->enabledPeerApprovalGroups();
    }

    public function asnPeerGroup(): string
    {
        $prefix = $this->definition()?->teamGroupPrefix();
        return $prefix === null ? '' : $prefix . '*';
    }

    public function authorizationStatus(): string
    {
        return $this->settings->status();
    }
}
