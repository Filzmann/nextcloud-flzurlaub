<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { class Event { public function __construct(){} } interface IEventListener { public function handle(Event $event):void; } }
namespace OCP { interface IAppConfig { public function getValueString(string $appId,string $key,string $default=''):string; public function setValueString(string $appId,string $key,string $value):void; } }
namespace OCP\AppFramework\Utility { interface ITimeFactory { public function getTime():int; } }
namespace OCA\FlzUrlaub\Repository {
    use OCA\FlzUrlaub\Model\Vacation;
    class VacationRepository {
        public array $items=[];
        public int $deleteCalls=0;
        public function findByEmployeeUid(string $uid,int $limit):array{return array_slice(array_values(array_filter($this->items,fn(Vacation $v)=>$v->employeeUid()===$uid)),0,$limit);}
        public function findEndedByEmployeeUid(string $uid,string $cutoff,int $limit):array{return array_slice(array_values(array_filter($this->items,fn(Vacation $v)=>$v->employeeUid()===$uid&&$v->endDate()<=$cutoff)),0,$limit);}
        public function findEndedBefore(string $cutoff,int $limit,int $offset=0):array{
            $items=array_values(array_filter($this->items,fn(Vacation $v)=>$v->endDate()<=$cutoff));
            usort($items,static fn(Vacation $left,Vacation $right):int=>[$left->endDate(),$left->id()]<=>[$right->endDate(),$right->id()]);
            return array_slice($items,$offset,$limit);
        }
        public function delete(int $id):void{$this->deleteCalls++;}
    }
    class TemporaryAdminAccessRepository { public array $items=[]; public function historyForUid(string $uid,int $limit):array{return array_slice(array_values(array_filter($this->items,static fn(array $item):bool=>in_array($uid,[$item['targetUid'],$item['grantedBy'],$item['revokedBy']],true))),0,$limit);} }
}

namespace {
    use OCA\FlzUrlaub\Model\Vacation;
    use OCA\FlzUrlaub\Privacy\VacationPersonalDataProvider;
    use OCA\FlzUrlaub\Privacy\VacationPrivacyProviderListener;
    use OCA\FlzUrlaub\Privacy\VacationRetentionProvider;
    use OCA\FlzUrlaub\Repository\VacationRepository;
    use OCA\FlzUrlaub\Repository\TemporaryAdminAccessRepository;
    use OCA\FlzUrlaub\Service\VacationRetentionPolicyService;
    use OCA\FlzDataProtection\PublicApi\V1\DataSubjectRef;
    use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
    use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
    use OCA\FlzDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
    use OCA\FlzDataProtection\PublicApi\V1\RetentionPreviewRequest;

    $repo=new VacationRepository();
    $repo->items=[
        Vacation::get(['id'=>11,'employeeUid'=>'self','startDate'=>'2026-07-01','endDate'=>'2026-07-10','status'=>'approved','note'=>'Eigene Notiz']),
        Vacation::get(['id'=>12,'employeeUid'=>'foreign','startDate'=>'2026-06-01','endDate'=>'2026-06-03','status'=>'planned','note'=>'Fremde Notiz']),
    ];
    $config=new class implements OCP\IAppConfig { public array $values=[]; public function getValueString(string $appId,string $key,string $default=''):string{return $this->values[$appId][$key]??$default;} public function setValueString(string $appId,string $key,string $value):void{$this->values[$appId][$key]=$value;} };
    $policy=new VacationRetentionPolicyService($config);
    $policy->save(['enabled'=>true,'reviewAfterDays'=>30,'action'=>'REVIEW']);
    $subject=new DataSubjectRef('nextcloud-user','self');
    $adminAccess=new TemporaryAdminAccessRepository();$adminAccess->items=[['id'=>7,'targetUid'=>'self','grantedBy'=>'self','startsAt'=>new DateTimeImmutable('2026-08-12T08:00:00+00:00'),'endsAt'=>new DateTimeImmutable('2026-08-12T12:00:00+00:00'),'revokedAt'=>new DateTimeImmutable('2026-08-12T10:00:00+00:00'),'revokedBy'=>'other-admin']];
    $personal=new VacationPersonalDataProvider($repo,$policy,$adminAccess);
    $descriptor=$personal->descriptor();
    if($descriptor->appId()!=='flzurlaub'||$descriptor->contractVersion()!=='1.0'||!$descriptor->supportsSubjectType('nextcloud-user'))throw new RuntimeException('Urlaubs-Provider beschreibt den Standalone-V1-Vertrag nicht korrekt.');
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
    $grant=$report->entries()[1];$grantJson=json_encode([$grant->categoryLabel(),$grant->summary(),$grant->source(),$grant->recipientCategories(),$grant->attributes(),$grant->thirdPartyContentNotice()],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);if(!str_contains($grantJson,'Admin-Vollzugriff')||str_contains($grantJson,'other-admin'))throw new RuntimeException('Adminfreigabe fehlt oder legt eine fremde Admin-ID offen.');
    foreach(['Freigebendes Mitglied von Datenschutzbeauftragte','App-lokale Freigabesteuerung in Filzmann Urlaubsplanung','Datenschutz-Prüfrolle'] as $expected)if(!str_contains($grantJson,$expected))throw new RuntimeException("Adminfreigabe projiziert Rolle oder Quelle nicht korrekt: {$expected}");
    $repo->items[]=Vacation::get(['id'=>13,'employeeUid'=>'self','startDate'=>'2026-08-20','endDate'=>'2026-08-22','status'=>'planned','note'=>'Weitere eigene Notiz']);
    if($personal->collect(new PersonalDataRequest($subject,'de','access-report',1,[]))->status()!=='partial')throw new RuntimeException('Begrenzter Urlaubsbericht behauptet Vollständigkeit.');
    array_pop($repo->items);
    $unsupported=$personal->collect(new PersonalDataRequest(new DataSubjectRef('external-applicant','self'),'de','access-report',50,[]));
    if($unsupported->status()!=='not_applicable'||$unsupported->entries()!==[])throw new RuntimeException('Ein nicht unterstützter Subject-Typ erhielt Urlaubsdaten.');
    try{
        $personal->collect((new PersonalDataRequest($subject,'de','access-report',50,['flzurlaub'=>'opaque']))->forProvider('flzurlaub',50));
        throw new RuntimeException('Ein unbekannter Provider-Cursor wurde akzeptiert.');
    }catch(InvalidArgumentException){}

    $retention=new VacationRetentionProvider($repo,$policy);
    $preview=$retention->preview(new RetentionPreviewRequest('vacation_review','2026-08-12T12:00:00+00:00',1));
    if($preview->status()!=='partial'||count($preview->candidates())!==1||$preview->candidates()[0]->toArray()['action']!=='REVIEW'||$preview->nextCursor()===null)throw new RuntimeException('Globale Urlaubs-Retention oder Pagination fehlt.');
    $continued=$retention->preview(new RetentionPreviewRequest('vacation_review','2099-01-01T00:00:00+00:00',1,$preview->nextCursor()));
    $continuedJson=json_encode($continued->candidates()[0]->toArray(),JSON_THROW_ON_ERROR);
    if($continued->status()!=='complete'||count($continued->candidates())!==1||str_contains($continuedJson,'self')||str_contains($continuedJson,'Notiz'))throw new RuntimeException('Urlaubs-Retention-Folgeseite ist unvollständig oder legt personenbezogene Inhalte offen.');
    if($retention->preview(new RetentionPreviewRequest('unknown_policy','2026-08-12T12:00:00+00:00',20))->status()!=='not_applicable')throw new RuntimeException('Eine unbekannte Urlaubs-Retention-Policy wird nicht kontrolliert abgelehnt.');
    try{$retention->preview(new RetentionPreviewRequest('vacation_review','2026-08-12T12:00:00+00:00',20,'manipulated'));throw new RuntimeException('Ein manipulierter Provider-Cursor wurde akzeptiert.');}catch(InvalidArgumentException){}
    if($repo->deleteCalls!==0)throw new RuntimeException('Retention-Preview verändert Urlaube.');
    $listener=new VacationPrivacyProviderListener($personal,$retention);
    $personalRegistry=new RegisterPersonalDataProvidersEvent();$listener->handle($personalRegistry);
    $retentionRegistry=new RegisterRetentionProvidersEvent();$listener->handle($retentionRegistry);
    if(array_keys($personalRegistry->providers())!==['flzurlaub']||array_keys($retentionRegistry->providers())!==['flzurlaub'])throw new RuntimeException('Urlaubs-Provider werden nicht registriert.');
    $policy->save(['enabled'=>false,'reviewAfterDays'=>0,'action'=>'REVIEW']);
    $disabledRegistry=new RegisterRetentionProvidersEvent();$listener->handle($disabledRegistry);
    if($disabledRegistry->providers()!==[])throw new RuntimeException('Eine deaktivierte Urlaubs-Retention registriert fälschlich einen Provider.');

    $application=(string)file_get_contents(dirname(__DIR__).'/lib/AppInfo/Application.php');
    if(!str_contains($application,'registerEventListener(RegisterRetentionProvidersEvent::class, VacationPrivacyProviderListener::class)')||str_contains($application,'RetentionProviderRegistryEvent'))throw new RuntimeException('Der Bootstrap verwendet nicht ausschließlich den Standalone-V1-Retention-Vertrag.');
    echo "Filzmann Urlaubsplanung privacy provider test passed\n";
}
