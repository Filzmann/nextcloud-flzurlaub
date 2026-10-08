<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('flzurlaub', 'admin-access');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('flzurlaub', 'models/holiday-calendar');
\OCP\Util::addScript('flzurlaub', 'components/vacation-plan');
\OCP\Util::addScript('flzurlaub', 'modules/vacation-app');
\OCP\Util::addScript('flzurlaub', 'main');
\OCP\Util::addStyle('flzurlaub', 'style');
?>
<main id="flzurlaub-app" class="flz-vacation-app">
    <div class="orgsuite-host" data-orgsuite data-suite="flz" data-current-app="flzurlaub"></div>
    <header class="flz-vacation-header">
        <div class="flz-vacation-title-row">
            <h1>Filzmann Urlaubsplanung</h1>
            <?php if ($_['showMissingAdminGrant'] ?? false): ?>
                <details class="flz-vacation-admin-access-warning">
                    <summary aria-label="Informationen zum fehlenden fachlichen Admin-Vollzugriff"><span aria-hidden="true">⚠</span></summary>
                    <div class="flz-vacation-admin-access-warning__panel">
                        <strong>Kein fachlicher Admin-Vollzugriff aktiv.</strong>
                        <p>Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Mitglieder der Gruppe Datenschutzbeauftragte können eine app-lokale Freigabe von höchstens 24 Stunden erteilen.</p>
                        <?php if ($_['showAdminAccessLink'] ?? false): ?><a href="#flz-vacation-full-access" target="_blank" rel="noopener noreferrer">Freigabesteuerung in neuem Tab öffnen</a><?php endif; ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>
        <div class="flz-vacation-controls">
            <label>Team <select id="flz-vacation-team"></select></label>
            <label>Jahr <input id="flz-vacation-year" type="number" min="2000" max="2100" step="1"></label>
        </div>
    </header>
    <?php if ($_['canManageAdminAccess'] ?? false): ?>
        <section id="flz-vacation-full-access" class="flz-vacation-admin-access" aria-labelledby="flz-vacation-full-access-heading">
            <h2 id="flz-vacation-full-access-heading">Zeitlich begrenzter Admin-Vollzugriff</h2>
            <p>Nur Mitglieder der Nextcloud-Gruppe Datenschutzbeauftragte dürfen Freigaben für aktive native Administrationskonten verwalten. Maximal 24 Stunden sind zulässig.</p>
            <form id="flz-vacation-full-access-form">
                <label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label>
                <label>Dauer <select name="durationMinutes" required><option value="60">1 Stunde</option><option value="240">4 Stunden</option><option value="480">8 Stunden</option><option value="1440">24 Stunden</option></select></label>
                <label><input id="flz-vacation-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label>
                <button type="submit" class="primary">Freigabe aktivieren</button>
            </form>
            <p id="flz-vacation-full-access-status" role="status" aria-live="polite"></p>
            <div class="flz-vacation-table-wrap flz-vacation-admin-access-history">
                <table><caption>Protokollierte Admin-Vollzugriffszeiträume</caption><thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead><tbody id="flz-vacation-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody></table>
            </div>
        </section>
    <?php endif; ?>
    <div id="flz-vacation-notice" role="status" aria-live="polite"></div>
    <p id="flz-vacation-integration-status" class="flz-vacation-integration-status" role="status" hidden></p>
    <section id="flz-vacation-calendar-view" class="flz-vacation-section" aria-labelledby="flz-vacation-plan-title">
        <header class="flz-vacation-section-head">
            <h2 id="flz-vacation-plan-title">Urlaubsplan</h2>
            <p class="flz-vacation-legend"><span class="flz-vacation-request flz-vacation-request-planned">U?</span> geplant, Hinweis <span class="flz-vacation-request flz-vacation-request-approved">U</span> genehmigt, blockiert Kalenderzeiten <span class="flz-vacation-school-holiday-legend"><span class="flz-vacation-school-holiday-swatch" aria-hidden="true"></span> Berliner Schulferien</span> <span class="flz-vacation-public-holiday-legend"><span class="flz-vacation-public-holiday-swatch" aria-hidden="true"></span> gesetzliche Feiertage</span></p>
        </header>
        <p id="flz-vacation-holiday-status" class="flz-vacation-holiday-status" role="status" aria-live="polite"></p>
        <p class="flz-vacation-holiday-source">Quelle: <a href="https://www.openholidaysapi.org/" target="_blank" rel="noopener noreferrer">OpenHolidays API</a>, Datenlizenz ODbL.</p>
        <form id="flz-vacation-own-form" class="flz-vacation-vacation-form">
            <label>Von <input name="startDate" type="date" required></label>
            <label>Bis <input name="endDate" type="date" required></label>
            <label>Notiz <input name="note" type="text" maxlength="500"></label>
            <button type="submit">Eintragen</button>
        </form>
        <div id="flz-vacation-own-requests" class="flz-vacation-request-list" aria-label="Meine Urlaubsanträge"></div>
        <div id="flz-vacation-conflicts" class="flz-vacation-conflicts" role="alert" hidden></div>
        <div class="flz-vacation-table-wrap flz-vacation-year-wrap">
            <table class="flz-vacation-table flz-vacation-year-table">
                <caption>Jahresurlaub nach Team und Mitarbeiter*in</caption>
                <thead id="flz-vacation-calendar-head"></thead>
                <tbody id="flz-vacation-calendar-body"><tr><td>Daten werden geladen.</td></tr></tbody>
            </table>
        </div>
    </section>
</main>
