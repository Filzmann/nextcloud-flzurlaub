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
    class TemporaryAdminAccessRepository { public array $items=[]; public function historyForUid(string $uid,int $limit):array{return array_slice(array_values(array_filter($this->items,static fn(array $item):bool=>in_array($uid,[$item['targetUid'],$item['grantedBy'],$item['revokedBy']],true))),0,$limit);} }
}

namespace {
    use OCA\AdUrlaub\Model\Vacation;
    use OCA\AdUrlaub\Privacy\VacationPersonalDataProvider;
    use OCA\AdUrlaub\Privacy\VacationPrivacyProviderListener;
    use OCA\AdUrlaub\Privacy\VacationRetentionProvider;
    use OCA\AdUrlaub\Repository\VacationRepository;
    use OCA\AdUrlaub\Repository\TemporaryAdminAccessRepository;
    use OCA\AdUrlaub\Service\VacationRetentionPolicyService;
    use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
    use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
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
    $subject=new DataSubjectRef('nextcloud-user','self');
    $adminAccess=new TemporaryAdminAccessRepository();$adminAccess->items=[['id'=>7,'targetUid'=>'self','grantedBy'=>'other-admin','startsAt'=>new DateTimeImmutable('2026-08-12T08:00:00+00:00'),'endsAt'=>new DateTimeImmutable('2026-08-12T12:00:00+00:00'),'revokedAt'=>null,'revokedBy'=>null]];
    $personal=new VacationPersonalDataProvider($repo,$policy,$adminAccess);
    $descriptor=$personal->descriptor();
    if($descriptor->appId()!=='adurlaub'||$descriptor->contractVersion()!=='1.0'||!$descriptor->supportsSubjectType('nextcloud-user'))throw new RuntimeException('Urlaubs-Provider beschreibt den Standalone-V1-Vertrag nicht korrekt.');
    $report=$personal->collect(new PersonalDataRequest($subject,'de','access-report',50,[]));
    if(count($report->entries())!==2||$report->status()!=='complete')throw new RuntimeException('Urlaubsauskunft ist nicht strikt subjectgebunden, lässt Adminfreigaben aus oder meldet einen falschen Status.');
    $entry=$report->entries()[0];
    $item=[
        'categoryId'=>$entry->categoryId(),'categoryLabel'=>$entry->categoryLabel(),'reference'=>$entry->reference(),
        'summary'=>$entry->summary(),'purpose'=>$entry->purpose(),'source'=>$entry->source(),
        'recipientCategories'=>$entry->recipientCategories(),'retention'=>$entry->retention(),
        'thirdCountryTransfer'=>$entry->thirdCountryTransfer(),'automatedDecision'=>$entry->automatedDecision(),
        'thirdPartyContentNotice'=>$entry->thirdPartyContentNotice(),'attributes'=>$entry->attributes(),
    ];
    if($item['reference']!=='vacation:11'||$item['attributes']['Notiz']!=='Eigene Notiz'||str_contains(json_encode($item,JSON_THROW_ON_ERROR),'Fremde'))throw new RuntimeException('Eigene Urlaubsdaten oder Drittpersonenschutz sind falsch.');
    foreach(['Von','Bis','Status','Notiz'] as $label)if(!array_key_exists($label,$item['attributes']))throw new RuntimeException("Deutsche Detailbezeichnung fehlt: {$label}");
    foreach(['startDate','endDate','status','note'] as $technical)if(array_key_exists($technical,$item['attributes']))throw new RuntimeException("Technischer Feldname ist sichtbar: {$technical}");
    foreach(['Urlaubszeitraum','01.07.26 bis 10.07.26','Urlaubsplanung','09.08.26'] as $expected)if(!str_contains(json_encode($item,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$expected))throw new RuntimeException("Menschenlesbare Urlaubsangabe fehlt: {$expected}");
    if(!str_contains($item['retention'],'30 Tage')||$item['purpose']!=='Urlaubsplanung, Genehmigung und Verfügbarkeitsprüfung')throw new RuntimeException('Art.-15-Angaben für Urlaub fehlen.');
    $grant=$report->entries()[1];$grantJson=json_encode([$grant->categoryLabel(),$grant->summary(),$grant->attributes(),$grant->thirdPartyContentNotice()],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);if(!str_contains($grantJson,'Admin-Vollzugriff')||str_contains($grantJson,'other-admin'))throw new RuntimeException('Adminfreigabe fehlt oder legt eine fremde Admin-ID offen.');
    $repo->items[]=Vacation::get(['id'=>13,'employeeUid'=>'self','startDate'=>'2026-08-20','endDate'=>'2026-08-22','status'=>'planned','note'=>'Weitere eigene Notiz']);
    if($personal->collect(new PersonalDataRequest($subject,'de','access-report',1,[]))->status()!=='partial')throw new RuntimeException('Begrenzter Urlaubsbericht behauptet Vollständigkeit.');
    array_pop($repo->items);
    $unsupported=$personal->collect(new PersonalDataRequest(new DataSubjectRef('external-applicant','self'),'de','access-report',50,[]));
    if($unsupported->status()!=='not_applicable'||$unsupported->entries()!==[])throw new RuntimeException('Ein nicht unterstützter Subject-Typ erhielt Urlaubsdaten.');
    try{
        $personal->collect((new PersonalDataRequest($subject,'de','access-report',50,['adurlaub'=>'opaque']))->forProvider('adurlaub',50));
        throw new RuntimeException('Ein unbekannter Provider-Cursor wurde akzeptiert.');
    }catch(InvalidArgumentException){}

    $retention=new VacationRetentionProvider($repo,$policy,$clock);
    $retentionSubject=new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER,'self');
    $preview=$retention->preview(new RetentionPreviewRequest($retentionSubject,50));
    if(count($preview->candidates())!==1||$preview->candidates()[0]->toArray()['action']!=='REVIEW')throw new RuntimeException('Urlaubs-Retention berücksichtigt die Adminregel nicht.');
    $listener=new VacationPrivacyProviderListener($personal,$retention);
    $personalRegistry=new RegisterPersonalDataProvidersEvent();$listener->handle($personalRegistry);
    $retentionRegistry=new RetentionProviderRegistryEvent();$listener->handle($retentionRegistry);
    if(array_keys($personalRegistry->providers())!==['adurlaub']||array_keys($retentionRegistry->providers())!==['adurlaub'])throw new RuntimeException('Urlaubs-Provider werden nicht registriert.');
    echo "AD Urlaub privacy provider test passed\n";
}
