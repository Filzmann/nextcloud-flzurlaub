<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adurlaub', 'admin');
\OCP\Util::addStyle('adurlaub', 'admin');
?>
<section id="adurlaub-admin" class="section adu-admin" aria-labelledby="adu-admin-heading">
    <h2 id="adu-admin-heading">AD Urlaub</h2>
    <section class="adu-admin-panel" aria-labelledby="adu-full-access-heading">
        <h3 id="adu-full-access-heading">Zeitlich begrenzter Admin-Vollzugriff</h3>
        <p>Native Nextcloud-Administration erteilt keinen automatischen Zugriff auf Urlaubsdaten. Eine Freigabe gilt nur für das angegebene Administrationskonto. Maximal 24 Stunden sind zulässig.</p>
        <form id="adu-full-access-form"><label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label><label>Dauer <select name="durationMinutes" required><option value="60">1 Stunde</option><option value="240">4 Stunden</option><option value="480">8 Stunden</option><option value="1440">24 Stunden</option></select></label><label><input id="adu-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label><button type="submit" class="primary">Freigabe aktivieren</button></form>
        <p id="adu-full-access-status" role="status" aria-live="polite"></p>
        <table><caption>Protokollierte Admin-Vollzugriffszeiträume</caption><thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead><tbody id="adu-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody></table>
    </section>
    <section class="adu-admin-panel" aria-labelledby="adu-demo-heading">
        <h3 id="adu-demo-heading">Demo-Pack</h3>
        <p>Das Pack legt synthetische lokale Demokonten sowie geplante und genehmigte Beispielurlaube für alle konfigurierten Fachgruppen an. Es wird nicht automatisch installiert und importiert keine Bestandsdaten.</p>
        <p>Fremde Konten und read-only LDAP-Gruppen werden vor der ersten Änderung abgewiesen.</p>
        <p id="adu-demo-notice" class="adu-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="adu-demo-confirm"><input id="adu-demo-confirm" type="checkbox"> Ich bestätige die Installation synthetischer Demodaten.</label>
        <button id="adu-demo-install" type="button" class="primary" disabled>Urlaubs-Demo-Pack installieren</button>
    </section>
</section>
