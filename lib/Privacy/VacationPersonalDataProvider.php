<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Privacy;

use OCA\AdUrlaub\AppInfo\AppId;
use OCA\AdUrlaub\Model\Vacation;
use OCA\AdUrlaub\Repository\VacationRepository;
use OCA\AdUrlaub\Repository\TemporaryAdminAccessRepository;
use OCA\AdUrlaub\Service\VacationRetentionPolicyService;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;
use InvalidArgumentException;

final class VacationPersonalDataProvider implements PersonalDataProvider {
    public function __construct(private VacationRepository $vacations,private VacationRetentionPolicyService $retention,private TemporaryAdminAccessRepository $adminAccess){}
    public function descriptor():ProviderDescriptor{
        return new ProviderDescriptor(AppId::VALUE,'AD Urlaub','1.0',['nextcloud-user'],['personal-data'],500);
    }
    public function collect(PersonalDataRequest $request):PersonalDataPage{
        if($request->subject()->subjectType()!=='nextcloud-user')return new PersonalDataPage('not_applicable');
        if($request->cursor()!==null)throw new InvalidArgumentException('AD Urlaub does not support cursor paging.');
        $policy=$this->retention->policy();
        $vacations=$this->vacations->findByEmployeeUid($request->subject()->subjectId(),$request->pageLimit()+1);
        $adminHistory=$this->adminAccess->historyForUid($request->subject()->subjectId(),$request->pageLimit()+1);
        $limited=count($vacations)+count($adminHistory)>$request->pageLimit();
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
        foreach($adminHistory as $grant){$uid=$request->subject()->subjectId();$roles=[];if($grant['targetUid']===$uid)$roles[]='Ziel der Vollzugriffsfreigabe';if($grant['grantedBy']===$uid)$roles[]='Freigebendes Mitglied von Datenschutzbeauftragte';if($grant['revokedBy']===$uid)$roles[]='Widerrufendes Mitglied von Datenschutzbeauftragte';$actualEnd=$grant['revokedAt']??$grant['endsAt'];$items[]=new PersonalDataEntry(categoryId:'admin-access',categoryLabel:'Zeitlich begrenzter Admin-Vollzugriff',reference:'admin-access:'.$grant['id'],summary:self::germanDateTime($grant['startsAt']).' bis '.self::germanDateTime($actualEnd),purpose:'Nachweis einer zeitlich begrenzten administrativen Urlaubsfreigabe',source:'App-lokale Freigabesteuerung in AD Urlaub',recipientCategories:['Betroffene Person und ausdrücklich berechtigte Datenschutz-Prüfrolle'],retention:'Keine feste Löschfrist festgelegt; die sicherheitsrelevante Freigabehistorie bleibt bis zu einer gesonderten Aufbewahrungsentscheidung erhalten.',thirdCountryTransfer:'Durch AD Urlaub sind keine Drittlandübermittlungen für diese Freigabehistorie vorgesehen.',automatedDecision:'Der Server beendet den Vollzugriff spätestens nach 24 Stunden automatisch.',thirdPartyContentNotice:'Kennungen anderer beteiligter Personen werden nicht ausgegeben.',attributes:['Eigene Rolle im Vorgang'=>implode(', ',$roles),'Beginn'=>self::germanDateTime($grant['startsAt']),'Geplantes Ende'=>self::germanDateTime($grant['endsAt']),'Tatsächliches Ende'=>self::germanDateTime($actualEnd),'Status'=>$grant['revokedAt']===null?'planmäßig beendet oder noch aktiv':'widerrufen']);}
        if($limited)$items=array_slice($items,0,$request->pageLimit());
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
    private static function germanDateTime(\DateTimeImmutable $date):string{return $date->format('d.m.y, H:i').' Uhr';}
}
