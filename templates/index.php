<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adurlaub', 'admin-access');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('adurlaub', 'models/holiday-calendar');
\OCP\Util::addScript('adurlaub', 'components/vacation-plan');
\OCP\Util::addScript('adurlaub', 'modules/vacation-app');
\OCP\Util::addScript('adurlaub', 'main');
\OCP\Util::addStyle('adurlaub', 'style');
?>
<main id="adurlaub-app" class="adu-app">
    <div class="orgsuite-host" data-orgsuite data-suite="ad" data-current-app="adurlaub"></div>
    <header class="adu-header">
        <div class="adu-title-row">
            <h1>AD Urlaub</h1>
            <?php if ($_['showMissingAdminGrant'] ?? false): ?>
                <details class="adu-admin-access-warning">
                    <summary aria-label="Informationen zum fehlenden fachlichen Admin-Vollzugriff"><span aria-hidden="true">⚠</span></summary>
                    <div class="adu-admin-access-warning__panel">
                        <strong>Kein fachlicher Admin-Vollzugriff aktiv.</strong>
                        <p>Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Mitglieder der Gruppe Datenschutzbeauftragte können eine app-lokale Freigabe von höchstens 24 Stunden erteilen.</p>
                        <?php if ($_['showAdminAccessLink'] ?? false): ?><a href="#adu-full-access" target="_blank" rel="noopener noreferrer">Freigabesteuerung in neuem Tab öffnen</a><?php endif; ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>
        <div class="adu-controls">
            <label>Team <select id="adu-team"></select></label>
            <label>Jahr <input id="adu-year" type="number" min="2000" max="2100" step="1"></label>
        </div>
    </header>
    <?php if ($_['canManageAdminAccess'] ?? false): ?>
        <section id="adu-full-access" class="adu-admin-access" aria-labelledby="adu-full-access-heading">
            <h2 id="adu-full-access-heading">Zeitlich begrenzter Admin-Vollzugriff</h2>
            <p>Nur Mitglieder der Nextcloud-Gruppe Datenschutzbeauftragte dürfen Freigaben für aktive native Administrationskonten verwalten. Maximal 24 Stunden sind zulässig.</p>
            <form id="adu-full-access-form">
                <label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label>
                <label>Dauer <select name="durationMinutes" required><option value="60">1 Stunde</option><option value="240">4 Stunden</option><option value="480">8 Stunden</option><option value="1440">24 Stunden</option></select></label>
                <label><input id="adu-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label>
                <button type="submit" class="primary">Freigabe aktivieren</button>
            </form>
            <p id="adu-full-access-status" role="status" aria-live="polite"></p>
            <div class="adu-table-wrap adu-admin-access-history">
                <table><caption>Protokollierte Admin-Vollzugriffszeiträume</caption><thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead><tbody id="adu-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody></table>
            </div>
        </section>
    <?php endif; ?>
    <div id="adu-notice" role="status" aria-live="polite"></div>
    <p id="adu-integration-status" class="adu-integration-status" role="status" hidden></p>
    <section id="adu-calendar-view" class="adu-section" aria-labelledby="adu-plan-title">
        <header class="adu-section-head">
            <h2 id="adu-plan-title">Urlaubsplan</h2>
            <p class="adu-legend"><span class="adu-request adu-request-planned">U?</span> geplant, Hinweis <span class="adu-request adu-request-approved">U</span> genehmigt, blockiert Kalenderzeiten <span class="adu-school-holiday-legend"><span class="adu-school-holiday-swatch" aria-hidden="true"></span> Berliner Schulferien</span> <span class="adu-public-holiday-legend"><span class="adu-public-holiday-swatch" aria-hidden="true"></span> gesetzliche Feiertage</span></p>
        </header>
        <p id="adu-holiday-status" class="adu-holiday-status" role="status" aria-live="polite"></p>
        <p class="adu-holiday-source">Quelle: <a href="https://www.openholidaysapi.org/" target="_blank" rel="noopener noreferrer">OpenHolidays API</a>, Datenlizenz ODbL.</p>
        <form id="adu-own-form" class="adu-vacation-form">
            <label>Von <input name="startDate" type="date" required></label>
            <label>Bis <input name="endDate" type="date" required></label>
            <label>Notiz <input name="note" type="text" maxlength="500"></label>
            <button type="submit">Eintragen</button>
        </form>
        <div id="adu-own-requests" class="adu-request-list" aria-label="Meine Urlaubsanträge"></div>
        <div id="adu-conflicts" class="adu-conflicts" role="alert" hidden></div>
        <div class="adu-table-wrap adu-year-wrap">
            <table class="adu-table adu-year-table">
                <caption>Jahresurlaub nach Team und Mitarbeiter*in</caption>
                <thead id="adu-calendar-head"></thead>
                <tbody id="adu-calendar-body"><tr><td>Daten werden geladen.</td></tr></tbody>
            </table>
        </div>
    </section>
</main>
