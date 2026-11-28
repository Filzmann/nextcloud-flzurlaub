<?php

declare(strict_types=1);

namespace {

    use OCA\FlzUrlaub\Service\HolidayCalendarService;
    use OCA\FlzUrlaub\Service\OrganizationHolidayCalendarService;

    $gateway = new class extends OrganizationHolidayCalendarService {
        public array $calls = [];
        public function __construct() {}
        public function forYear(int $year): ?array {
            $this->calls[] = $year;
            return [
                'year' => $year,
                'context' => ['countryCode' => 'DE', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Berlin'],
                'cacheStatus' => 'current',
                'schoolHolidays' => [['name' => 'Winterferien', 'startDate' => '2026-02-02', 'endDate' => '2026-02-07']],
                'publicHolidays' => [['name' => 'Internationaler Frauentag', 'startDate' => '2026-03-08', 'endDate' => '2026-03-08']],
            ];
        }
    };
    $service = new HolidayCalendarService($gateway);
    $calendar = $service->forYear(2026, true);
    if ($gateway->calls !== [2026]) throw new RuntimeException('Filzmann Urlaubsplanung delegiert nicht an den V1-Holiday-Gateway.');
    if (($calendar['context']['subdivisionCode'] ?? '') !== 'DE-BE'
        || ($calendar['schoolHolidays'][0]['name'] ?? '') !== 'Winterferien'
        || ($calendar['publicHolidays'][0]['name'] ?? '') !== 'Internationaler Frauentag') {
        throw new RuntimeException('Filzmann Urlaubsplanung verliert Daten des V1-Holiday-Snapshots.');
    }

    $empty = new class extends OrganizationHolidayCalendarService {
        public function __construct() {}
        public function forYear(int $year): ?array { return null; }
    };
    $fallback = (new HolidayCalendarService($empty))->forYear(2027);
    if (($fallback['cacheStatus'] ?? '') !== 'unavailable' || ($fallback['year'] ?? 0) !== 2027
        || ($fallback['publicHolidays'] ?? null) !== [] || ($fallback['schoolHolidays'] ?? null) !== []) {
        throw new RuntimeException('Der Standalone-Fallback ohne Holiday-V1-Provider ist falsch.');
    }

    echo "HolidayCalendarServiceTest: OK\n";
}
