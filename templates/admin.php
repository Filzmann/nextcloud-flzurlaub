<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('flzurlaub', 'admin');
\OCP\Util::addStyle('flzurlaub', 'admin');
?>
<section id="flzurlaub-admin" class="section flz-vacation-admin" aria-labelledby="flz-vacation-admin-heading">
    <h2 id="flz-vacation-admin-heading">Filzmann Urlaubsplanung</h2>
    <section class="flz-vacation-admin-panel" aria-labelledby="flz-vacation-demo-heading">
        <h3 id="flz-vacation-demo-heading">Demo-Pack</h3>
        <p>Das Pack legt synthetische lokale Demokonten sowie geplante und genehmigte Beispielurlaube für alle konfigurierten Fachgruppen an. Es wird nicht automatisch installiert und importiert keine Bestandsdaten.</p>
        <p>Fremde Konten und read-only LDAP-Gruppen werden vor der ersten Änderung abgewiesen.</p>
        <p id="flz-vacation-demo-notice" class="flz-vacation-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="flz-vacation-demo-confirm"><input id="flz-vacation-demo-confirm" type="checkbox"> Ich bestätige die Installation synthetischer Demodaten.</label>
        <button id="flz-vacation-demo-install" type="button" class="primary" disabled>Urlaubs-Demo-Pack installieren</button>
    </section>
</section>
