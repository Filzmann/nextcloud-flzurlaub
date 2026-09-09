<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Privacy;

use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class VacationProcessingMetadataProviderListener implements IEventListener {
    public function __construct(private VacationProcessingMetadataProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof RegisterProcessingMetadataProvidersEvent) {
            $event->register($this->provider);
        }
    }
}
