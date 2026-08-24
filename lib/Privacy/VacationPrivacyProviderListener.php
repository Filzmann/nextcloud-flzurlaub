<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Privacy;

use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
use OCA\LocalBase\Privacy\RetentionProviderRegistryEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class VacationPrivacyProviderListener implements IEventListener {
    public function __construct(private VacationPersonalDataProvider $personal,private VacationRetentionProvider $retention){}
    public function handle(Event $event):void{
        if($event instanceof PersonalDataProviderRegistryEvent)$event->register($this->personal);
        if($event instanceof RetentionProviderRegistryEvent)$event->register($this->retention);
    }
}
