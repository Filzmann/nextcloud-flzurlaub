<?php

declare(strict_types=1);

$css = file_get_contents(__DIR__ . '/../css/style.css'); $template = file_get_contents(__DIR__ . '/../templates/index.php'); $info = file_get_contents(__DIR__ . '/../appinfo/info.xml'); $holidayService = file_get_contents(__DIR__ . '/../lib/Service/HolidayCalendarService.php');
if ($info === false || str_contains($info, '<app>') || str_contains($info, '<navigations>')) throw new RuntimeException('Standalone-Appvertrag fehlt.');
foreach (['height:100%','min-height:0','overflow-y:auto','background:var(--color-main-background)','overflow-x:auto','width:max-content'] as $contract) if (!str_contains($css, $contract)) throw new RuntimeException("Scrollvertrag fehlt: {$contract}");
if (preg_match('/\.adu-app\{[^}]*width:100%/', $css) !== 1) throw new RuntimeException('Der App-Root nutzt nicht die vollständige verfügbare Breite.');
foreach (['.adu-notice:empty{display:none}', '.adu-notice--error{color:var(--color-error-text)', 'background:var(--color-error)', 'border:2px solid var(--color-error)'] as $contract) if (!str_contains($css, $contract)) throw new RuntimeException("Kontrastreicher Fehlerhinweis fehlt: {$contract}");
foreach (['<main','id="adu-calendar-view"','<form id="adu-own-form"','aria-live="polite"','<caption>', "\\OCP\\Util::addScript('adurlaub', 'components/vacation-plan')", "\\OCP\\Util::addScript('adurlaub', 'modules/vacation-app')", 'data-orgsuite data-suite="ad" data-current-app="adurlaub"'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("UI-Vertrag fehlt: {$contract}");
foreach (['id="adu-holiday-status"', "\\OCP\\Util::addScript('adurlaub', 'models/holiday-calendar')", 'OpenHolidays API', 'ODbL'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Ferien-/Feiertags-UI-Vertrag fehlt: {$contract}");
foreach (['.adu-school-holiday-row', '.adu-public-holiday-row', '.adu-day-row', 'is-school-holiday', 'is-public-holiday', 'adu-school-holiday-swatch', 'adu-public-holiday-swatch', 'background:#d8ecfa', 'background:#f5d4d4', 'height:24px', 'padding:1px 2px', 'white-space:nowrap', 'text-overflow:ellipsis', 'top:24px', 'top:48px'] as $contract) if (!str_contains($css, $contract)) throw new RuntimeException("Ferien-/Feiertags-Layoutvertrag fehlt: {$contract}");
foreach (['.adu-year-table thead .adu-holiday-label{position:absolute', 'inset:1px 2px', 'overflow:hidden', 'text-overflow:ellipsis', 'font-size:inherit'] as $contract) if (!str_contains($css, $contract)) throw new RuntimeException("Feste Tagesbreite bei langen Feiertagsnamen fehlt: {$contract}");
foreach (['.adu-table .is-saturday{background-image:linear-gradient(rgba(0,0,0,.035)', '.adu-table .is-sunday,.adu-table .is-public-holiday-column{background-image:linear-gradient(rgba(0,0,0,.09)'] as $contract) if (!str_contains($css, $contract)) throw new RuntimeException("Wochenend-/Feiertagsspaltenvertrag fehlt: {$contract}");
foreach (['.adu-table .is-year-end-special{', 'rgba(196,128,0,.12)', 'inset 2px 0 #c48000'] as $contract) if (!str_contains($css, $contract)) throw new RuntimeException("Abgegrenzte Markierung für Heiligabend und Silvester fehlt: {$contract}");
if (str_contains($css, 'top:84px') || str_contains($css, 'height:42px')) throw new RuntimeException('Ferien-/Feiertagszeilen verwenden noch die hohe Darstellung.');
if (str_contains($info, 'OCA\\AdUrlaub\\BackgroundJob\\RefreshHolidayCalendarJob')) throw new RuntimeException('Der gemeinsame LocalBase-Refreshjob wird durch AD Urlaub dupliziert.');
foreach (['OCA\\LocalBase\\Calendar\\HolidayCalendarService', '->toArray()'] as $contract) if (!str_contains($holidayService, $contract)) throw new RuntimeException("Gemeinsamer Ferien-/Feiertags-Consumervertrag fehlt: {$contract}");
if (str_contains($template, '>F</span>') || str_contains($css, 'repeating-linear-gradient')) throw new RuntimeException('Veraltete F-/Schraffurkennzeichnung der Schulferien ist noch vorhanden.');
if (str_contains($template, "addScript('orgsuite'") || str_contains($template, "addStyle('orgsuite'")) throw new RuntimeException('Direkte OrgSuite-Assetkopplung vorhanden.');
if (preg_match('/^\\s*(?:script|style)\\s*\\(/m', $template) === 1) throw new RuntimeException('Veralteter globaler Templatehelfer gefunden.');
foreach (['adu-tab-settings', 'adu-settings-view', '>Einstellungen</button>'] as $removed) if (str_contains($template, $removed)) throw new RuntimeException("Organisationsweite Einstellung liegt noch in AD Urlaub: {$removed}");
echo "LayoutSmokeTest: OK\n";
