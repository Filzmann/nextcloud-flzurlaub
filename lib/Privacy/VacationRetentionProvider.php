<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Privacy;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use OCA\FlzUrlaub\AppInfo\AppId;
use OCA\FlzUrlaub\Model\Vacation;
use OCA\FlzUrlaub\Repository\VacationRepository;
use OCA\FlzUrlaub\Service\VacationRetentionPolicyService;
use OCA\FlzDataProtection\PublicApi\V1\RetentionCandidate;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPolicy;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPreviewPage;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPreviewRequest;
use OCA\FlzDataProtection\PublicApi\V1\RetentionProvider;
use OCA\FlzDataProtection\PublicApi\V1\RetentionProviderDescriptor;

final class VacationRetentionProvider implements RetentionProvider {
    public const POLICY_ID='vacation_review';
    public function __construct(private VacationRepository $vacations,private VacationRetentionPolicyService $policy){}
    public function descriptor():RetentionProviderDescriptor{return new RetentionProviderDescriptor(AppId::VALUE,'Filzmann Urlaubsplanung','1.0',200);}
    public function policies():array{
        $policy=$this->policy->policy();
        return [new RetentionPolicy(self::POLICY_ID,'Urlaubszeiträume','Administrative Prüfung beendeter Urlaubszeiträume nach der app-eigenen Vorschaufrist','COMPLETED_AT',$policy['reviewAfterDays'],'REVIEW','1.0')];
    }
    public function isEnabled():bool{return $this->policy->policy()['enabled'];}
    public function preview(RetentionPreviewRequest $request):RetentionPreviewPage{
        $policy=$this->policy->policy();
        if(!$policy['enabled']||$request->policyId()!==self::POLICY_ID)return new RetentionPreviewPage('not_applicable');
        $offset=$this->offset($request->cursor());
        $cutoff=(new DateTimeImmutable($request->evaluatedAt()))->sub(new DateInterval('P'.$policy['reviewAfterDays'].'D'))->format('Y-m-d');
        $rows=$this->vacations->findEndedBefore($cutoff,$request->limit()+1,$offset);
        $hasMore=count($rows)>$request->limit();
        if($hasMore)array_pop($rows);
        $items=array_map(static fn(Vacation $vacation)=>new RetentionCandidate(
            self::POLICY_ID,'vacation:'.$vacation->id(),$vacation->endDate().'T00:00:00+00:00','REVIEW',
            sprintf('Urlaub endete vor dem administrativ konfigurierten REVIEW-Stichtag (%d Tage).',$policy['reviewAfterDays'])
        ),$rows);
        return new RetentionPreviewPage($hasMore?'partial':'complete',$items,[],$hasMore?(string)($offset+$request->limit()):null);
    }
    private function offset(?string $cursor):int{
        if($cursor===null)return 0;
        if(!preg_match('/^(?:0|[1-9][0-9]{0,8})$/',$cursor))throw new InvalidArgumentException('Invalid vacation retention cursor.');
        return (int)$cursor;
    }
}
