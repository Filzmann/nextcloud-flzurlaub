<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/lib/Migration/Version000005Date202607220002.php');
$info = file_get_contents($root . '/appinfo/info.xml');
$adapter = file_get_contents($root . '/lib/Service/HolidayCalendarService.php');
foreach ([$migration, $info, $adapter] as $source) if ($source === false) throw new RuntimeException('Gemeinsamer Kalender-Consumervertrag konnte nicht gelesen werden.');

foreach (['IJobList', 'postSchemaChange', 'RefreshHolidayCalendarJob::class', '->has(', '->remove('] as $contract) {
    if (!str_contains($migration, $contract)) throw new RuntimeException("Migration des früheren Urlaubsjobs fehlt: {$contract}");
}
if (str_contains($info, 'OCA\\FlzUrlaub\\BackgroundJob\\RefreshHolidayCalendarJob')) throw new RuntimeException('Der doppelte Urlaubs-Refreshjob wird weiterhin registriert.');
foreach (['OCA\\LocalBase\\Calendar\\HolidayCalendarService', '->forYear(', '->toArray()'] as $contract) {
    if (!str_contains($adapter, $contract)) throw new RuntimeException("Filzmann-Urlaubsplanung-Consumervertrag fehlt: {$contract}");
}
echo "SharedHolidayCalendarMigrationSmokeTest: OK\n";
