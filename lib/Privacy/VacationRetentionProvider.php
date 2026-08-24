<?php

declare(strict_types=1);

namespace OCA\AdUrlaub\Privacy;

use DateInterval;
use DateTimeImmutable;
use OCA\AdUrlaub\AppInfo\AppId;
use OCA\AdUrlaub\Model\Vacation;
use OCA\AdUrlaub\Repository\VacationRepository;
use OCA\AdUrlaub\Service\VacationRetentionPolicyService;
use OCA\LocalBase\Privacy\RetentionPreviewCandidate;
use OCA\LocalBase\Privacy\RetentionPreviewPage;
use OCA\LocalBase\Privacy\RetentionPreviewRequest;
use OCA\LocalBase\Privacy\RetentionProvider;
use OCP\AppFramework\Utility\ITimeFactory;

final class VacationRetentionProvider implements RetentionProvider {
    public function __construct(private VacationRepository $vacations,private VacationRetentionPolicyService $policy,private ITimeFactory $clock){}
    public function appId():string{return AppId::VALUE;}
    public function preview(RetentionPreviewRequest $request):RetentionPreviewPage{
        $policy=$this->policy->policy();
        if(!$policy['enabled'])return new RetentionPreviewPage([]);
        $cutoff=(new DateTimeImmutable('@'.$this->clock->getTime()))->sub(new DateInterval('P'.$policy['reviewAfterDays'].'D'))->format('Y-m-d');
        $items=array_map(static fn(Vacation $vacation)=>new RetentionPreviewCandidate(
            'vacation:'.$vacation->id(),'vacation',RetentionPreviewCandidate::REVIEW,
            sprintf('Urlaub endete vor dem administrativ konfigurierten REVIEW-Stichtag (%d Tage).',$policy['reviewAfterDays'])
        ),$this->vacations->findEndedByEmployeeUid($request->subject()->id(),$cutoff,$request->limit()));
        return new RetentionPreviewPage($items);
    }
}
