<?php
declare(strict_types=1);
namespace OCA\FlzUrlaub\Permission;
use OCA\FlzUrlaub\Service\VacationSettingsService;
use OCA\FlzPermissionMatrix\PublicApi\V1\{PermissionCondition,PermissionProvider,PermissionProviderDescriptor,PermissionProviderResult,PermissionRule};
final class VacationPermissionProvider implements PermissionProvider{
 public function __construct(private VacationPermissionSourceInterface $source){}
 public function descriptor():PermissionProviderDescriptor{return new PermissionProviderDescriptor('flzurlaub','Filzmann Urlaubsplanung','1.0',['permissions']);}
 public function collect():PermissionProviderResult{
  $definition=$this->source->definition();$teams=$this->source->teamGroupIds();$rules=[
   $this->rule('Urlaub','Eigene geplante Urlaube','Planen, ändern und löschen; genehmigte Urlaube benötigen Genehmigungsrecht','vacation.manage-own','Eigene Planung verwalten','own-planned-vacation',PermissionCondition::self()),
   $this->rule('Urlaub','Alle Urlaube','Native Nextcloud-Administration mit aktiver app-lokaler Freigabe (maximal 24 Stunden)','vacation.manage-all','Alle Urlaube verwalten','all-vacations',PermissionCondition::all([PermissionCondition::nextcloudAdmin(),PermissionCondition::temporaryAppAdminGrant()])),
   $this->rule('Technische Administration','Synthetisches Demo-Pack','Nur Installation synthetischer Demodaten; kein fachlicher Vollzugriff','vacation.demo.install','Demo-Pack installieren','synthetic-demo',PermissionCondition::nextcloudAdmin()),
   $this->rule('Datenschutz','Temporäre Adminfreigaben','Freigaben und Historie app-lokal verwalten','vacation.admin-grant.manage','Temporäre Adminfreigaben verwalten','admin-grants',PermissionCondition::group('Datenschutzbeauftragte')),
   $this->rule('Datenschutz','Retention-REVIEW','REVIEW-Regel und Vorschau verwalten; keine automatische Löschung','vacation.retention.manage','Retention-REVIEW verwalten','retention-review',PermissionCondition::group('Datenschutzbeauftragte')),
  ];
  foreach($definition->roleGroupIds() as $group)$rules[]=$this->rule('Urlaubsansicht','Organisationssicht','Nur der durch VacationVisibilityPolicy erlaubte Personenausschnitt','vacation.view-scope','Urlaubsansicht lesen','visibility-policy',PermissionCondition::group($group));
  foreach($teams as $team)$rules[]=$this->rule('Urlaubsansicht',$team,'Gemeinsames Assistenzteam lesen','vacation.view-team','Teamurlaub lesen','team:'.$team,PermissionCondition::group($team));
  foreach($definition->hierarchy() as $actorKey=>$targets){$actorGroup=$definition->roleGroupId((string)$actorKey);if($actorGroup===null)continue;foreach($targets as $targetKey){$area=$definition->roleManagementIsAreaScoped((string)$actorKey)?'; gemeinsamer Bürobereich erforderlich':'';$rules[]=$this->rule('Urlaub','Direkt unterstellte Rolle '.$definition->roleLabel((string)$targetKey),'Genehmigen und genehmigte Urlaube bearbeiten'.$area,'vacation.approve-subordinate','Direkt unterstellte Urlaube genehmigen','target-role:'.$targetKey,PermissionCondition::group($actorGroup));}}
  $eb=(string)$definition->roleGroupId('eb');foreach($teams as $team)$rules[]=$this->rule('Urlaub',$team,'Genehmigung durch zuständige Einsatzbegleitung; ausschließlich AS des gemeinsamen Teams','vacation.approve-team','Urlaub von AS des Teams genehmigen','team:'.$team,PermissionCondition::all([PermissionCondition::group($team),PermissionCondition::group($eb)]));
  $asnPeer=$this->source->asnPeerGroup();foreach($this->source->enabledPeerEditGroups() as $group){if($group===$asnPeer){foreach($teams as $team)$rules[]=$this->rule('Urlaub',$team,'Freigeschaltete Bearbeitung geplanter Urlaube unter Assistenzkolleg*innen','vacation.edit-asn-peer','Teamurlaub als Peer bearbeiten','team:'.$team,PermissionCondition::group($team));continue;}$rules[]=$this->rule('Urlaub','Peer-Gruppe '.$group,'Geplante Urlaube ausdrücklich freigeschalteter Kolleg*innen bearbeiten; Bereichsgrenzen bleiben wirksam','vacation.edit-peer','Peer-Urlaub bearbeiten','peer-group:'.$group,PermissionCondition::group((string)$group));}
  foreach($this->source->enabledPeerApprovalGroups() as $group){if($group===$asnPeer){foreach($teams as $team)$rules[]=$this->rule('Urlaub',$team,'Freigeschaltete Genehmigung unter Assistenzkolleg*innen; eigene Genehmigung bleibt gesperrt','vacation.approve-asn-peer','Teamurlaub als Peer genehmigen','team:'.$team,PermissionCondition::group($team));continue;}$rules[]=$this->rule('Urlaub','Peer-Gruppe '.$group,'Ausdrücklich freigeschaltete Peergenehmigung; Bereichsgrenzen und Selbstgenehmigungsverbot bleiben wirksam','vacation.approve-peer','Peer-Urlaub genehmigen','peer-group:'.$group,PermissionCondition::group((string)$group));}
  $status=$this->source->authorizationStatus();
  return new PermissionProviderResult($rules,$status===VacationSettingsService::VALID,$status===VacationSettingsService::VALID?[]:['LocalBase-Urlaubsautorisierung: '.$status]);
 }
 private function rule(string $type,string $name,string $detail,string $key,string $label,string $scope,PermissionCondition $condition):PermissionRule{return new PermissionRule($type,$name,$detail,$key,$label,'allow',$scope,$condition,'flzurlaub:VacationAccessService','high');}
}
