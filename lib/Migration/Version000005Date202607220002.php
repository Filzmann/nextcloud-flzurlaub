<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Migration;

use Closure;
use OCA\FlzUrlaub\BackgroundJob\RefreshHolidayCalendarJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Entfernt den früheren app-eigenen Refreshjob, nachdem LocalBase den gemeinsamen Kalendercache übernommen hat. */
final class Version000005Date202607220002 extends SimpleMigrationStep {
    public function __construct(private IJobList $jobs) {}

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        if ($this->jobs->has(RefreshHolidayCalendarJob::class, null)) {
            $this->jobs->remove(RefreshHolidayCalendarJob::class, null);
        }
    }
}
