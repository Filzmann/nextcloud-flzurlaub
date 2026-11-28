<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { class Event { public function __construct() {} } interface IEventListener { public function handle(Event $event): void; } }
namespace OCA\FlzUrlaub\AppInfo { final class Application { public const APP_ID = 'flzurlaub'; } }

namespace {

    use OCA\FlzUrlaub\Listener\IntegrationCapabilityQueryListener;
    use OCA\FlzOrganization\PublicApi\V1\IntegrationCapabilities;
    use OCA\FlzOrganization\PublicApi\V1\IntegrationCapabilityQueryEvent;

    $event = new IntegrationCapabilityQueryEvent([IntegrationCapabilities::ABSENCE_READ]);
    (new IntegrationCapabilityQueryListener())->handle($event);
    if ($event->providersFor(IntegrationCapabilities::ABSENCE_READ) !== ['flzurlaub']) throw new RuntimeException('Urlaubsfähigkeit fehlt.');
    if ($event->isAvailable(IntegrationCapabilities::SCHEDULE_CONFLICT_READ)) throw new RuntimeException('Urlaub meldet eine fremde Fähigkeit.');

    echo "Filzmann Urlaubsplanung capability listener test passed\n";
}
