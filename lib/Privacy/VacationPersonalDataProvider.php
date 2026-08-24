<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Privacy;

use OCA\AdUrlaub\AppInfo\AppId;
use OCA\AdUrlaub\Model\Vacation;
use OCA\AdUrlaub\Repository\VacationRepository;
use OCA\AdUrlaub\Service\VacationRetentionPolicyService;
use OCA\LocalBase\Privacy\PersonalDataItem;
use OCA\LocalBase\Privacy\PersonalDataProcessingInfo;
use OCA\LocalBase\Privacy\PersonalDataProvider;
use OCA\LocalBase\Privacy\PersonalDataReport;
use OCA\LocalBase\Privacy\PersonalDataRequest;
use OCA\LocalBase\Privacy\PersonalDataSubject;

final class VacationPersonalDataProvider implements PersonalDataProvider {
    public function __construct(private VacationRepository $vacations,private VacationRetentionPolicyService $retention){}
    public function appId():string{return AppId::VALUE;}
    public function supportedSubjectTypes():array{return [PersonalDataSubject::NEXTCLOUD_USER];}
    public function collect(PersonalDataRequest $request):PersonalDataReport{
        $policy=$this->retention->policy();
        $vacations=$this->vacations->findByEmployeeUid($request->subject()->id(),$request->limit());
        $limited=count($vacations)>=$request->limit();
        $items=array_map(static fn(Vacation $vacation)=>new PersonalDataItem('vacation',self::dateRange($vacation),'vacation:'.$vacation->id(),[
            'Von'=>self::germanDate(new \DateTimeImmutable($vacation->startDate())),
            'Bis'=>self::germanDate(new \DateTimeImmutable($vacation->endDate())),
            'Status'=>$vacation->status()===Vacation::STATUS_APPROVED?'Genehmigt':'Geplant',
            'Notiz'=>$vacation->note()!==''?$vacation->note():'Keine Notiz hinterlegt',
        ],'Urlaubsplanung, Genehmigung und Verfügbarkeitsprüfung',self::retentionFor($vacation,$policy),'Folgende Urlaubszeiträume sind mit deinen Daten gespeichert:',dataType:'Urlaubszeitraum'),$vacations);
        return new PersonalDataReport($items,new PersonalDataProcessingInfo(
            purposes:['Urlaubsplanung und Genehmigung','Verfügbarkeits- und Konfliktprüfung in angebundenen Planungsapps'],
            categories:['Nextcloud-Kennung','Urlaubszeitraum und Status','Freiwillige Urlaubsnotiz'],
            recipients:['Berechtigte Vorgesetzte und freigeschaltete Kolleg*innen im zulässigen Organisationsscope','Nextcloud-Administrator*innen','Angemeldete Nutzer*innen mit berechtigter Urlaubssicht'],
            source:'Eingaben der betroffenen Person oder einer berechtigten genehmigenden beziehungsweise administrierenden Person',
            retentionCriteria:$this->retention->retentionCriteria(),
            thirdCountryTransfers:'Durch AD Urlaub sind keine Drittlandübermittlungen vorgesehen.',
            automatedDecisionMaking:'Konflikt- und Überschneidungsprüfungen unterstützen die Bearbeitung; sie treffen keine Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung.',
        ),complete:!$limited,limitations:$limited?['Ausgabelimit erreicht; weitere Urlaubszeiträume können vorhanden sein.']:[],appName:'AD Urlaub');
    }
    private static function retentionFor(Vacation $vacation,array $policy):string{
        if(!$policy['enabled'])return 'Keine feste Löschfrist festgelegt; die administrative Retention-Prüfung ist derzeit deaktiviert.';
        $reviewAt=(new \DateTimeImmutable($vacation->endDate()))->modify('+'.$policy['reviewAfterDays'].' days');
        return 'Keine feste Löschfrist festgelegt; ab '.self::germanDate($reviewAt).' zur administrativen Prüfung vorgesehen. Es erfolgt keine automatische Löschung.';
    }
    private static function dateRange(Vacation $vacation):string{
        $start=new \DateTimeImmutable($vacation->startDate());$end=new \DateTimeImmutable($vacation->endDate());
        return self::germanDate($start).' bis '.self::germanDate($end);
    }
    private static function germanDate(\DateTimeImmutable $date):string{return $date->format('d.m.y');}
}
