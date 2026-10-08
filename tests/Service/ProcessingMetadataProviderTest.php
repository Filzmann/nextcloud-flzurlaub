<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}

namespace {
    require_once dirname(__DIR__) . '/bootstrap.php';

    use OCA\FlzUrlaub\Privacy\VacationProcessingMetadataProvider;
    use OCA\FlzUrlaub\Privacy\VacationProcessingMetadataProviderListener;
    use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
    use OCP\EventDispatcher\Event;

    $provider = new VacationProcessingMetadataProvider();
    $catalog = $provider->catalog();
    $descriptor = $provider->descriptor();
    if ($descriptor->appId() !== 'flzurlaub' || $descriptor->displayName() !== 'Filzmann Urlaubsplanung' || $descriptor->contractVersion() !== '1.0') {
        throw new RuntimeException('Der Processing-Metadata-Provider beschreibt Filzmann Urlaubsplanung nicht korrekt.');
    }
    if ($catalog->appId() !== 'flzurlaub') {
        throw new RuntimeException('Processing-Metadata-Provider und Katalog verwenden nicht die kanonische App-ID.');
    }
    if ($catalog->processingIds() !== [
        'vacation_management',
        'temporary_admin_full_access',
    ]) {
        throw new RuntimeException('Der app-lokale Processing-Katalog ist unvollständig.');
    }
    if (array_key_exists('personal_runtime_data', $catalog->toArray())) {
        throw new RuntimeException('Der Processing-Katalog enthält personenbezogene Laufzeitdaten.');
    }
    $encodedCatalog=json_encode($catalog->toArray(),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    if(!str_contains($encodedCatalog,'App-lokaler Freigabevorgang durch Datenschutzbeauftragte in Filzmann Urlaubsplanung')||str_contains($encodedCatalog,'Laufzeitdurchsetzung der Ziel- und Gruppenbedingungen sowie Allow-, Deny- und Manipulationsprüfungen stehen aus'))throw new RuntimeException('Der Processing-Katalog projiziert den umgesetzten DPO-Freigabevertrag nicht korrekt.');

    $registration = new RegisterProcessingMetadataProvidersEvent();
    $listener = new VacationProcessingMetadataProviderListener($provider);
    $listener->handle(new Event());
    if ($registration->providers() !== []) {
        throw new RuntimeException('Ein fremdes Event registriert den Processing-Metadata-Provider.');
    }
    $listener->handle($registration);
    if (($registration->providers()['flzurlaub'] ?? null) !== $provider) {
        throw new RuntimeException('Der Processing-Metadata-Provider wird nicht lazy registriert.');
    }

    $application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(RegisterProcessingMetadataProvidersEvent::class, VacationProcessingMetadataProviderListener::class)')) {
        throw new RuntimeException('Der Bootstrap registriert den Processing-Metadata-Provider nicht am öffentlichen V1-Event.');
    }

    echo "Filzmann Urlaubsplanung processing metadata provider test passed\n";
}
