<?php
declare(strict_types=1);
namespace OCA\FlzUrlaub\Permission;
use OCA\LocalBase\Organization\FlzOrganizationDefinition;
interface VacationPermissionSourceInterface{public function definition():FlzOrganizationDefinition;public function teamGroupIds():array;public function enabledPeerEditGroups():array;public function enabledPeerApprovalGroups():array;public function asnPeerGroup():string;public function authorizationStatus():string;}
