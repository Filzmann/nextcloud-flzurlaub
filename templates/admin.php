<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adurlaub', 'admin');
\OCP\Util::addStyle('adurlaub', 'admin');
?>
<section id="adurlaub-admin" class="section adu-admin" aria-labelledby="adu-admin-heading">
    <h2 id="adu-admin-heading">AD Urlaub</h2>
    <section class="adu-admin-panel" aria-labelledby="adu-demo-heading">
        <h3 id="adu-demo-heading">Demo-Pack</h3>
        <p>Das Pack legt synthetische lokale Demokonten sowie geplante und genehmigte Beispielurlaube für alle konfigurierten Fachgruppen an. Es wird nicht automatisch installiert und importiert keine Bestandsdaten.</p>
        <p>Fremde Konten und read-only LDAP-Gruppen werden vor der ersten Änderung abgewiesen.</p>
        <p id="adu-demo-notice" class="adu-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="adu-demo-confirm"><input id="adu-demo-confirm" type="checkbox"> Ich bestätige die Installation synthetischer Demodaten.</label>
        <button id="adu-demo-install" type="button" class="primary" disabled>Urlaubs-Demo-Pack installieren</button>
    </section>
</section>
