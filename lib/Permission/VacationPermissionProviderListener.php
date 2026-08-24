<?php
declare(strict_types=1);
namespace OCA\AdUrlaub\Permission;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
final class VacationPermissionProviderListener{public function __construct(private VacationPermissionProvider $provider){}public function handle(object $event):void{if($event instanceof RegisterPermissionProvidersEvent)$event->register($this->provider);}}
