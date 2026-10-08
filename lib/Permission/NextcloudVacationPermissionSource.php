<?php
declare(strict_types=1);
namespace OCA\FlzUrlaub\Permission;
use OCA\FlzUrlaub\Service\VacationSettingsService;use OCA\LocalBase\Organization\FlzOrganizationDefinition;use OCA\LocalBase\Organization\FlzOrganizationSettingsService;use OCP\IGroupManager;
final class NextcloudVacationPermissionSource implements VacationPermissionSourceInterface{
 public function __construct(private IGroupManager $groups,private FlzOrganizationSettingsService $organization,private VacationSettingsService $settings){}
 public function definition():FlzOrganizationDefinition{return $this->organization->definition();}
 public function teamGroupIds():array{$prefix=$this->definition()->teamGroupPrefix();$ids=[];foreach($this->groups->search($prefix,10000,0) as $group){$id=(string)$group->getGID();if($id!==$prefix&&str_starts_with($id,$prefix))$ids[]=$id;}$ids=array_values(array_unique($ids));sort($ids,SORT_NATURAL|SORT_FLAG_CASE);return $ids;}
 public function enabledPeerGroups():array{return $this->settings->enabledPeerGroups();}
 public function asnPeerGroup():string{return $this->settings->asnPeerGroup();}
}
