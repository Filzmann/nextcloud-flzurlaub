<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Privacy;

use OCA\AdUrlaub\AppInfo\AppId;
use OCA\AdUrlaub\Model\Vacation;
use OCA\AdUrlaub\Repository\VacationRepository;
use OCA\AdUrlaub\Service\VacationRetentionPolicyService;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;
use InvalidArgumentException;

final class VacationPersonalDataProvider implements PersonalDataProvider {
    public function __construct(private VacationRepository $vacations,private VacationRetentionPolicyService $retention){}
    public function descriptor():ProviderDescriptor{
        return new ProviderDescriptor(AppId::VALUE,'AD Urlaub','1.0',['nextcloud-user'],['personal-data'],500);
    }
    public function collect(PersonalDataRequest $request):PersonalDataPage{
        if($request->subject()->subjectType()!=='nextcloud-user')return new PersonalDataPage('not_applicable');
        if($request->cursor()!==null)throw new InvalidArgumentException('AD Urlaub does not support cursor paging.');
        $policy=$this->retention->policy();
        $vacations=$this->vacations->findByEmployeeUid($request->subject()->subjectId(),$request->pageLimit()+1);
        $limited=count($vacations)>$request->pageLimit();
        if($limited)$vacations=array_slice($vacations,0,$request->pageLimit());
        $items=array_map(static fn(Vacation $vacation)=>new PersonalDataEntry(
            categoryId:'vacation',
            categoryLabel:'Urlaubszeitraum',
            reference:'vacation:'.$vacation->id(),
            summary:self::dateRange($vacation),
            purpose:'Urlaubsplanung, Genehmigung und Verfügbarkeitsprüfung',
            source:'Eingaben der betroffenen Person oder einer berechtigten genehmigenden beziehungsweise administrierenden Person',
            recipientCategories:['Berechtigte Vorgesetzte und freigeschaltete Kolleg*innen im zulässigen Organisationsscope','Nextcloud-Administrator*innen','Angemeldete Nutzer*innen mit berechtigter Urlaubssicht'],
            retention:self::retentionFor($vacation,$policy),
            thirdCountryTransfer:'Durch AD Urlaub sind keine Drittlandübermittlungen vorgesehen.',
            automatedDecision:'Konflikt- und Überschneidungsprüfungen unterstützen die Bearbeitung; sie treffen keine Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung.',
            thirdPartyContentNotice:'Die freiwillige eigene Urlaubsnotiz kann Angaben zu anderen Personen enthalten und wird deshalb als möglicher Drittpersoneninhalt gekennzeichnet.',
            attributes:[
                'Von'=>self::germanDate(new \DateTimeImmutable($vacation->startDate())),
                'Bis'=>self::germanDate(new \DateTimeImmutable($vacation->endDate())),
                'Status'=>$vacation->status()===Vacation::STATUS_APPROVED?'Genehmigt':'Geplant',
                'Notiz'=>$vacation->note()!==''?$vacation->note():'Keine Notiz hinterlegt',
            ],
        ),$vacations);
        if($items===[])return new PersonalDataPage('not_applicable');
        return new PersonalDataPage($limited?'partial':'complete',$items,$limited?['Ausgabelimit erreicht; weitere Urlaubszeiträume können vorhanden sein.']:[]);
    }
    private static function retentionFor(Vacation $vacation,array $policy):string{
        if(!$policy['enabled'])return 'Keine feste Löschfrist festgelegt; die administrative Retention-Prüfung ist derzeit deaktiviert.';
        $reviewAt=(new \DateTimeImmutable($vacation->endDate()))->modify('+'.$policy['reviewAfterDays'].' days');
        return 'Keine feste Löschfrist festgelegt; nach '.$policy['reviewAfterDays'].' Tagen, ab '.self::germanDate($reviewAt).', zur administrativen Prüfung vorgesehen. Es erfolgt keine automatische Löschung.';
    }
    private static function dateRange(Vacation $vacation):string{
        $start=new \DateTimeImmutable($vacation->startDate());$end=new \DateTimeImmutable($vacation->endDate());
        return self::germanDate($start).' bis '.self::germanDate($end);
    }
    private static function germanDate(\DateTimeImmutable $date):string{return $date->format('d.m.y');}
}
