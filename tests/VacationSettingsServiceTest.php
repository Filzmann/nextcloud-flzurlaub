<?php

declare(strict_types=1);

use OCA\FlzUrlaub\Service\OrganizationDefinitionGateway;
use OCA\FlzUrlaub\Service\VacationOrganizationDefinition;
use OCA\FlzUrlaub\Service\VacationSettingsService;
use OCA\LocalBase\Organization\FlzOrganizationDefinition;

final class FixedVacationSettingsGateway extends OrganizationDefinitionGateway
{
    public function __construct(
        private string $fixedStatus,
        private ?VacationOrganizationDefinition $fixedDefinition,
        private array $editGroups = [],
        private array $approveGroups = [],
    )
    {
    }

    public function status(): string
    {
        return $this->fixedStatus;
    }

    public function definition(): ?VacationOrganizationDefinition
    {
        return $this->fixedDefinition;
    }

    public function peerGroups(string $scope): array
    {
        return match ($scope)
        {
            'vacation.edit' => $this->editGroups,
            'vacation.approve' => $this->approveGroups,
            default => [],
        };
    }
}

$assert = static function (bool $condition, string $message): void
{
    if (!$condition)
    {
        throw new RuntimeException($message);
    }
};
$definition = VacationOrganizationDefinition::fromArray(FlzOrganizationDefinition::defaults()->toArray());
$service = new VacationSettingsService(new FixedVacationSettingsGateway(
    VacationSettingsService::VALID,
    $definition,
    ['PFK'],
    ['EB'],
));

$assert($service->status() === VacationSettingsService::VALID, 'Gültiger Organisationsvertrag wird nicht erkannt.');
$assert($service->enabledPeerEditGroups() === ['PFK'], 'Peer-Bearbeitung wird nicht aus dem Organisationsvertrag projiziert.');
$assert($service->enabledPeerApprovalGroups() === ['EB'], 'Peer-Genehmigung wird nicht aus dem Organisationsvertrag projiziert.');
$views = $service->ebAreaViews();
$assert(array_column($views, 'id') === ['eb-northeast', 'eb-west', 'eb-south'], 'EB-Büroansichten werden nicht kanonisch sortiert abgeleitet.');
$assert(($views[1]['roleGroupId'] ?? null) === 'EB', 'EB-Büroansicht verliert die kanonische Rollengruppe.');
$assert(($views[1]['areaGroupId'] ?? null) === 'Bereich-West', 'EB-Büroansicht verliert die kanonische Bereichsgruppe.');

$invalid = new VacationSettingsService(new FixedVacationSettingsGateway(VacationSettingsService::INVALID, null, ['PFK'], ['EB']));
$assert($invalid->enabledPeerEditGroups() === [], 'Ein ungültiger Provider erteilt Bearbeitungsrechte.');
$assert($invalid->enabledPeerApprovalGroups() === [], 'Ein ungültiger Provider erteilt Genehmigungsrechte.');
$assert($invalid->ebAreaViews() === [], 'Ein ungültiger Provider liefert EB-Büroansichten.');

echo "VacationSettingsServiceTest: OK\n";
