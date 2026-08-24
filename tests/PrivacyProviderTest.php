<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { class Event { public function __construct(){} } interface IEventListener { public function handle(Event $event):void; } }
namespace OCP { interface IAppConfig { public function getValueString(string $appId,string $key,string $default=''):string; public function setValueString(string $appId,string $key,string $value):void; } }
namespace OCP\AppFramework\Utility { interface ITimeFactory { public function getTime():int; } }
namespace OCA\AdUrlaub\Repository {
    use OCA\AdUrlaub\Model\Vacation;
    class VacationRepository {
        public array $items=[];
        public function findByEmployeeUid(string $uid,int $limit):array{return array_slice(array_values(array_filter($this->items,fn(Vacation $v)=>$v->employeeUid()===$uid)),0,$limit);}
        public function findEndedByEmployeeUid(string $uid,string $cutoff,int $limit):array{return array_slice(array_values(array_filter($this->items,fn(Vacation $v)=>$v->employeeUid()===$uid&&$v->endDate()<=$cutoff)),0,$limit);}
    }
}

namespace {
    use OCA\AdUrlaub\Model\Vacation;
    use OCA\AdUrlaub\Privacy\VacationPersonalDataProvider;
    use OCA\AdUrlaub\Privacy\VacationPrivacyProviderListener;
    use OCA\AdUrlaub\Privacy\VacationRetentionProvider;
    use OCA\AdUrlaub\Repository\VacationRepository;
    use OCA\AdUrlaub\Service\VacationRetentionPolicyService;
    use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
    use OCA\LocalBase\Privacy\PersonalDataRequest;
    use OCA\LocalBase\Privacy\PersonalDataSubject;
    use OCA\LocalBase\Privacy\RetentionPreviewRequest;
    use OCA\LocalBase\Privacy\RetentionProviderRegistryEvent;

    $repo=new VacationRepository();
    $repo->items=[
        Vacation::get(['id'=>11,'employeeUid'=>'self','startDate'=>'2026-07-01','endDate'=>'2026-07-10','status'=>'approved','note'=>'Eigene Notiz']),
        Vacation::get(['id'=>12,'employeeUid'=>'foreign','startDate'=>'2026-06-01','endDate'=>'2026-06-03','status'=>'planned','note'=>'Fremde Notiz']),
    ];
    $config=new class implements OCP\IAppConfig { public array $values=[]; public function getValueString(string $appId,string $key,string $default=''):string{return $this->values[$appId][$key]??$default;} public function setValueString(string $appId,string $key,string $value):void{$this->values[$appId][$key]=$value;} };
    $policy=new VacationRetentionPolicyService($config);
    $policy->save(['enabled'=>true,'reviewAfterDays'=>30,'action'=>'REVIEW']);
    $clock=new class implements OCP\AppFramework\Utility\ITimeFactory { public function getTime():int{return strtotime('2026-08-12T12:00:00+00:00');} };
    $subject=new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER,'self');
    $personal=new VacationPersonalDataProvider($repo,$policy);
    $report=$personal->collect(new PersonalDataRequest($subject,'de',PersonalDataRequest::PURPOSE_SELF_SERVICE,50));
    if(count($report->items())!==1)throw new RuntimeException('Urlaubsauskunft ist nicht strikt subjectgebunden.');
    $item=$report->items()[0]->toArray();
    if($item['reference']!=='vacation:11'||$item['attributes']['Notiz']!=='Eigene Notiz'||str_contains(json_encode($item,JSON_THROW_ON_ERROR),'Fremde'))throw new RuntimeException('Eigene Urlaubsdaten oder Drittpersonenschutz sind falsch.');
    foreach(['Von','Bis','Status','Notiz'] as $label)if(!array_key_exists($label,$item['attributes']))throw new RuntimeException("Deutsche Detailbezeichnung fehlt: {$label}");
    foreach(['startDate','endDate','status','note'] as $technical)if(array_key_exists($technical,$item['attributes']))throw new RuntimeException("Technischer Feldname ist sichtbar: {$technical}");
    foreach(['Folgende Urlaubszeiträume','01.07.26 bis 10.07.26','Urlaubsplanung','09.08.26'] as $expected)if(!str_contains(json_encode($item,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$expected))throw new RuntimeException("Menschenlesbare Urlaubsangabe fehlt: {$expected}");
    $processing=$report->processing()->toArray();
    if(!str_contains($processing['retentionCriteria'],'30 Tage')||!in_array('Urlaubsplanung und Genehmigung', $processing['purposes'],true))throw new RuntimeException('Art.-15-Angaben für Urlaub fehlen.');
    if($personal->collect(new PersonalDataRequest($subject,'de',PersonalDataRequest::PURPOSE_SELF_SERVICE,1))->isComplete())throw new RuntimeException('Begrenzter Urlaubsbericht behauptet Vollständigkeit.');

    $retention=new VacationRetentionProvider($repo,$policy,$clock);
    $preview=$retention->preview(new RetentionPreviewRequest($subject,50));
    if(count($preview->candidates())!==1||$preview->candidates()[0]->toArray()['action']!=='REVIEW')throw new RuntimeException('Urlaubs-Retention berücksichtigt die Adminregel nicht.');
    $listener=new VacationPrivacyProviderListener($personal,$retention);
    $personalRegistry=new PersonalDataProviderRegistryEvent();$listener->handle($personalRegistry);
    $retentionRegistry=new RetentionProviderRegistryEvent();$listener->handle($retentionRegistry);
    if(array_keys($personalRegistry->providers())!==['adurlaub']||array_keys($retentionRegistry->providers())!==['adurlaub'])throw new RuntimeException('Urlaubs-Provider werden nicht registriert.');
    echo "AD Urlaub privacy provider test passed\n";
}
