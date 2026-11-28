<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Service;

/**
 * Dünner Consumeradapter: Filzmann Urlaubsplanung behält sein API-Array und liest den
 * öffentlichen HolidayCalendar-Vertrag der flz_organization V1.
 */
final class HolidayCalendarService {
    public function __construct(private OrganizationHolidayCalendarService $organization) {}

    /** @return array<string,mixed> */
    public function forYear(int $year, bool $forceRefresh = false): array {
        unset($forceRefresh);
        return $this->organization->forYear($year) ?? [
            'year' => $year,
            'context' => null,
            'cacheStatus' => 'unavailable',
            'schoolHolidays' => [],
            'publicHolidays' => [],
        ];
    }
}
