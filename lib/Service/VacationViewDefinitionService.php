<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;

/**
 * Projiziert die kanonischen Organigrammfilter für die Urlaubsansicht.
 * EB-Bürofilter stammen ausschließlich aus dem öffentlichen LocalBase-V1-Snapshot.
 */
final class VacationViewDefinitionService {
    public function __construct(
        private VacationSettingsService $settings,
        private ?FlzOrganizationSettingsService $organization = null,
    ) {}

    /** @return list<array{id:string,code:string,displayName:string,category:string,sortOrder:int,areas:list<string>,roles:list<string>}> */
    public function all(): array {
        $definition = $this->definition();
        $result = [];
        foreach ($definition->organizationTeams() as $team) {
            if ($team['id'] === 'eb') continue;
            $result[] = [
                'id' => $team['id'],
                'code' => $team['id'],
                'displayName' => $team['label'],
                'category' => 'organization',
                'sortOrder' => $team['sortOrder'],
                'areas' => array_values(array_filter(array_map($definition->areaGroupId(...), $team['areas']))),
                'roles' => array_values(array_filter(array_map($definition->roleGroupId(...), $team['roles']))),
            ];
        }

        foreach ($this->settings->ebAreaViews() as $index => $view) {
            $result[] = [
                'id' => $view['id'],
                'code' => $view['id'],
                'displayName' => $view['label'],
                'category' => 'organization',
                'sortOrder' => 40 + $index,
                'areas' => [$view['areaGroupId']],
                'roles' => [$view['roleGroupId']],
            ];
        }

        return $result;
    }

    private function definition(): FlzOrganizationDefinition {
        return $this->organization?->definition() ?? FlzOrganizationDefinition::defaults();
    }
}
