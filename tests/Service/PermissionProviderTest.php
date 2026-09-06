<?php
declare(strict_types=1);
namespace OCP\EventDispatcher {
 class Event{}
 interface IEventListener{public function handle(Event $event):void;}
}
namespace OCA\FilzmannPermissionMatrix\PublicApi\V1 {
 interface PermissionProvider{public function descriptor():PermissionProviderDescriptor;public function collect():PermissionProviderResult;} final class PermissionProviderDescriptor{public function __construct(...$a){}} final class PermissionCondition{private function __construct(public string $operator,public ?string $groupId=null,public array $children=[]){}public static function group(string $id):self{return new self('group',$id);}public static function all(array $c):self{return new self('all',null,$c);}public static function self():self{return new self('self');}public static function nextcloudAdmin():self{return new self('nextcloud-admin');}public static function temporaryAppAdminGrant():self{return new self('app-admin-grant');}} final class PermissionRule{public function __construct(public string $type,public string $name,public string $detail,public string $permission,public string $label,public string $effect,public string $scope,public PermissionCondition $condition,public string $source,public string $confidence){}} final class PermissionProviderResult{public function __construct(public array $rules,public bool $complete=true,public array $warnings=[]){}} final class RegisterPermissionProvidersEvent extends \OCP\EventDispatcher\Event{public array $providers=[];public function register(PermissionProvider $p):void{$this->providers[]=$p;}}
}
namespace {
 use OCA\AdUrlaub\Permission\VacationPermissionProvider;use OCA\AdUrlaub\Permission\VacationPermissionProviderListener;use OCA\AdUrlaub\Permission\VacationPermissionSourceInterface;use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;use OCA\LocalBase\Organization\AdOrganizationDefinition;use OCP\EventDispatcher\Event;use OCP\EventDispatcher\IEventListener;
 $source=new class implements VacationPermissionSourceInterface{public function definition():AdOrganizationDefinition{return AdOrganizationDefinition::defaults();}public function teamGroupIds():array{return ['ad-ASN-A'];}public function enabledPeerGroups():array{return ['ad-EB'];}public function asnPeerGroup():string{return 'ad-ASN-*';}};
 $provider=new VacationPermissionProvider($source);$rules=$provider->collect()->rules;$by=[];foreach($rules as $r)$by[$r->permission][]=$r;
 if(($by['vacation.manage-own'][0]->condition->operator??null)!=='self')throw new RuntimeException('Eigene Planung muss self bleiben.');
 $admin=$by['vacation.manage-all'][0]->condition??null;if($admin?->operator!=='all'||($admin->children[0]->operator??null)!=='nextcloud-admin'||($admin->children[1]->operator??null)!=='app-admin-grant')throw new RuntimeException('Vollzugriff muss native Administration und aktive app-lokale Freigabe verlangen.');
 $teamApproval=array_values(array_filter($by['vacation.approve-team']??[],fn($r)=>$r->scope==='team:ad-ASN-A'))[0]??null;if($teamApproval?->condition->operator!=='all'||array_map(fn($c)=>$c->groupId,$teamApproval->condition->children)!==['ad-ASN-A','ad-EB'])throw new RuntimeException('Teamgenehmigung muss Team UND EB verlangen.');
 if(isset($by['vacation.approve-asn-peer']))throw new RuntimeException('ASN-Peer-Genehmigung darf ohne aktiven Sternschalter nicht erscheinen.');
 $peer=array_map(fn($r)=>$r->condition->groupId,$by['vacation.approve-peer']??[]);if($peer!==['ad-EB'])throw new RuntimeException('Nur konfigurierte Peer-Gruppen dürfen erscheinen.');
 $listener=new VacationPermissionProviderListener($provider);if(!$listener instanceof IEventListener)throw new RuntimeException('Der Permission-Provider-Listener muss den Nextcloud-Eventvertrag implementieren.');
 $event=new RegisterPermissionProvidersEvent();$listener->handle($event);if(($event->providers[0]??null)!==$provider)throw new RuntimeException('Lazy-Registrierung fehlt.');
 $foreignEvent=new Event();$listener->handle($foreignEvent);if(count($event->providers)!==1)throw new RuntimeException('Ein fremdes Event darf keine weitere Providerregistrierung auslösen.');echo "Vacation permission provider tests passed\n";
}
