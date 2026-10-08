<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Migration;

use Closure;
use OCA\FlzUrlaub\BackgroundJob\RefreshHolidayCalendarJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Registriert den Ferien-/Feiertagsjob auch bei Updates bestehender Installationen. */
final class Version000004Date202607220001 extends SimpleMigrationStep {
    public function __construct(private IJobList $jobs) {}

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        if (!$this->jobs->has(RefreshHolidayCalendarJob::class, null)) {
            $this->jobs->add(RefreshHolidayCalendarJob::class);
        }
    }
}
