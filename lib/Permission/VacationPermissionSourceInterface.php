<?php
declare(strict_types=1);
namespace OCA\AdUrlaub\Permission;
use OCA\LocalBase\Organization\AdOrganizationDefinition;
interface VacationPermissionSourceInterface{public function definition():AdOrganizationDefinition;public function teamGroupIds():array;public function enabledPeerGroups():array;public function asnPeerGroup():string;}
