<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar {
    final class HolidayCalendar {
        public function __construct(private array $data) {}
        public function toArray(): array { return $this->data; }
    }
    final class HolidayCalendarService {
        public array $calls = [];
        public function forYear(int $year, bool $forceRefresh = false): HolidayCalendar {
            $this->calls[] = [$year, $forceRefresh];
            return new HolidayCalendar([
                'year' => $year,
                'context' => ['countryCode' => 'DE', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Berlin'],
                'cacheStatus' => 'current',
                'schoolHolidays' => [['name' => 'Winterferien', 'startDate' => '2026-02-02', 'endDate' => '2026-02-07']],
                'publicHolidays' => [['name' => 'Internationaler Frauentag', 'startDate' => '2026-03-08', 'endDate' => '2026-03-08']],
            ]);
        }
    }
}

namespace {

    $shared = new \OCA\LocalBase\Calendar\HolidayCalendarService();
    $service = new \OCA\AdUrlaub\Service\HolidayCalendarService($shared);
    $calendar = $service->forYear(2026, true);
    if ($shared->calls !== [[2026, true]]) throw new RuntimeException('AD Urlaub delegiert nicht vollständig an den gemeinsamen Kalendervertrag.');
    if (($calendar['context']['subdivisionCode'] ?? '') !== 'DE-BE'
        || ($calendar['schoolHolidays'][0]['name'] ?? '') !== 'Winterferien'
        || ($calendar['publicHolidays'][0]['name'] ?? '') !== 'Internationaler Frauentag') {
        throw new RuntimeException('AD Urlaub verliert Daten des gemeinsamen Kalender-DTOs.');
    }
    echo "HolidayCalendarServiceTest: OK\n";
}
