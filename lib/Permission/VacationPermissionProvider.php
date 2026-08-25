<?php
declare(strict_types=1);
namespace OCA\AdUrlaub\Permission;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\{PermissionCondition,PermissionProvider,PermissionProviderDescriptor,PermissionProviderResult,PermissionRule};
final class VacationPermissionProvider implements PermissionProvider{
 public function __construct(private VacationPermissionSourceInterface $source){}
 public function descriptor():PermissionProviderDescriptor{return new PermissionProviderDescriptor('adurlaub','AD Urlaub','1.0',['permissions']);}
 public function collect():PermissionProviderResult{
  $definition=$this->source->definition();$teams=$this->source->teamGroupIds();$rules=[
   $this->rule('Urlaub','Eigene geplante Urlaube','Planen, ändern und löschen; genehmigte Urlaube benötigen Genehmigungsrecht','vacation.manage-own','Eigene Planung verwalten','own-planned-vacation',PermissionCondition::self()),
   $this->rule('Urlaub','Alle Urlaube','Native Nextcloud-Administration mit aktiver app-lokaler Freigabe (maximal 24 Stunden)','vacation.manage-all','Alle Urlaube verwalten','all-vacations',PermissionCondition::all([PermissionCondition::nextcloudAdmin(),PermissionCondition::temporaryAppAdminGrant()])),
  ];
  foreach($definition->roleGroupIds() as $group)$rules[]=$this->rule('Urlaubsansicht','Organisationssicht','Nur der durch VacationVisibilityPolicy erlaubte Personenausschnitt','vacation.view-scope','Urlaubsansicht lesen','visibility-policy',PermissionCondition::group($group));
  foreach($teams as $team)$rules[]=$this->rule('Urlaubsansicht',$team,'Gemeinsames Assistenzteam lesen','vacation.view-team','Teamurlaub lesen','team:'.$team,PermissionCondition::group($team));
  foreach($definition->hierarchy() as $actorKey=>$targets){$actorGroup=$definition->roleGroupId((string)$actorKey);if($actorGroup===null)continue;foreach(array_keys($definition->roles()) as $targetKey){if(!$definition->managesRole((string)$actorKey,(string)$targetKey))continue;$area=$definition->roleManagementIsAreaScoped((string)$actorKey)?'; gemeinsamer Bürobereich erforderlich':'';$rules[]=$this->rule('Urlaub','Unterstellte Rolle '.$definition->roleLabel((string)$targetKey),'Genehmigen und genehmigte Urlaube bearbeiten'.$area,'vacation.approve-subordinate','Unterstellte Urlaube genehmigen','target-role:'.$targetKey,PermissionCondition::group($actorGroup));}}
  $eb=(string)$definition->roleGroupId('eb');foreach($teams as $team)$rules[]=$this->rule('Urlaub',$team,'Genehmigung durch zuständige Einsatzbegleitung','vacation.approve-team','Teamurlaub genehmigen','team:'.$team,PermissionCondition::all([PermissionCondition::group($team),PermissionCondition::group($eb)]));
  $asnPeer=$this->source->asnPeerGroup();foreach($this->source->enabledPeerGroups() as $group){if($group===$asnPeer){foreach($teams as $team)$rules[]=$this->rule('Urlaub',$team,'Freigeschaltete Genehmigung unter Assistenzkolleg*innen; eigene Genehmigung bleibt gesperrt','vacation.approve-asn-peer','Teamurlaub als Peer genehmigen','team:'.$team,PermissionCondition::group($team));continue;}$rules[]=$this->rule('Urlaub','Peer-Gruppe '.$group,'Nur nicht übergeordnete Kolleg*innen; BO/EB zusätzlich im gemeinsamen Bürobereich','vacation.approve-peer','Peer-Urlaub genehmigen','peer-group:'.$group,PermissionCondition::group((string)$group));}
  return new PermissionProviderResult($rules);
 }
 private function rule(string $type,string $name,string $detail,string $key,string $label,string $scope,PermissionCondition $condition):PermissionRule{return new PermissionRule($type,$name,$detail,$key,$label,'allow',$scope,$condition,'adurlaub:VacationAccessService','high');}
}
