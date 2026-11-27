<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Permission;

use OCA\FlzUrlaub\Service\VacationOrganizationDefinition;

interface VacationPermissionSourceInterface
{
    public function definition(): ?VacationOrganizationDefinition;
    public function teamGroupIds(): array;
    public function enabledPeerEditGroups(): array;
    public function enabledPeerApprovalGroups(): array;
    public function asnPeerGroup(): string;
    public function authorizationStatus(): string;
}
