<?php

declare(strict_types=1);


use OCA\AdUrlaub\Model\VacationTeam;

$team = VacationTeam::get(['id'=>'office-now','code'=>'office-now','displayName'=>'Büro NOW','category'=>'organization','employees'=>[['uid'=>'anna']]]);
if (!$team->contains('anna') || $team->contains('bert')) throw new RuntimeException('Teammitgliedschaft wird falsch ausgewertet.');
if ($team->toArray()['displayName'] !== 'Büro NOW') throw new RuntimeException('Teamserialisierung ist fehlerhaft.');
echo "VacationTeamModelTest: OK\n";
