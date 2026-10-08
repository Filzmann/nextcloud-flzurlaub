<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

use OCA\LocalBase\Calendar\HolidayCalendarService as SharedHolidayCalendarService;

/** Dünner Consumeradapter: Filzmann Urlaubsplanung behält sein API-Array und liest den gemeinsamen LocalBase-Kalendervertrag. */
final class HolidayCalendarService {
    public function __construct(private SharedHolidayCalendarService $shared) {}

    public function forYear(int $year, bool $forceRefresh = false): array {
        return $this->shared->forYear($year, $forceRefresh)->toArray();
    }
}
