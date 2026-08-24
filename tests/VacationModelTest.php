<?php

declare(strict_types=1);


use OCA\AdUrlaub\Model\Vacation;

$planned = Vacation::get(['employeeUid'=>'alice','startDate'=>'2026-07-13','endDate'=>'2026-07-15','status'=>'planned','note'=>'Test']);
if ($planned->toArray()['marker'] !== 'U?' || $planned->toArray()['blocks']) throw new RuntimeException('Planned-Vertrag verletzt.');
$approved = Vacation::get(['employeeUid'=>'alice','startDate'=>'2026-07-13','endDate'=>'2026-07-13','status'=>'approved']);
if ($approved->toArray()['marker'] !== 'U' || !$approved->toArray()['blocks']) throw new RuntimeException('Approved-Vertrag verletzt.');
try { Vacation::get(['employeeUid'=>'alice','startDate'=>'2026-07-15','endDate'=>'2026-07-13','status'=>'planned']); throw new RuntimeException('Invertierter Zeitraum akzeptiert.'); } catch (InvalidArgumentException) {}
echo "VacationModelTest: OK\n";
