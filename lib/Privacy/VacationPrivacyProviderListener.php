<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Privacy;

use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class VacationPrivacyProviderListener implements IEventListener {
    public function __construct(private VacationPersonalDataProvider $personal,private VacationRetentionProvider $retention){}
    public function handle(Event $event):void{
        if($event instanceof RegisterPersonalDataProvidersEvent)$event->register($this->personal);
        if($event instanceof RegisterRetentionProvidersEvent&&$this->retention->isEnabled())$event->register($this->retention);
    }
}
