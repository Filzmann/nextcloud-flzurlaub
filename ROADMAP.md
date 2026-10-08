# Roadmap – Filzmann Urlaubsplanung

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Aktueller Fokus

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Jahresmatrix, eigene Anträge, Genehmigungshierarchie und Bereichsgrenzen auf einem realitätsnahen Staging fachlich abnehmen.
- Die Konfliktprüfung gegen Filzmann Kalender und den gültigen Standalone-Betrieb ohne Kalender absichern.
- Sichtbarkeit und Datenschutz von Urlaubsnotizen und Organisationsansichten produktiv prüfen.
- Die additiv migrierten Pflege-, Fahrzeugverwaltungs- und Empfangsansichten samt positiven und negativen Hierarchierechten fachlich abnehmen.
- Die DPO-gesteuerte Adminfreigabe mit getrennten Konten für DPO, nativen
  Admin ohne DPO-Rolle und gewöhnliche Nutzung in DDEV oder Staging prüfen;
  Ablauf, Widerruf, Rollenverlust, CSRF und Tastaturbedienung einschließen.

## Geplante Erweiterungen

- Weitere Funktionen werden erst aufgenommen, wenn ein konkreter fachlicher Bedarf und der betroffene Rechtevertrag benannt sind.
- Optionale Integrationen bleiben read-only oder verwenden einen ausdrücklich freigegebenen kleinen LocalBase-Vertrag; fehlende Provider bleiben ein gültiger Zustand.

## Bewusst zurückgestellt – niedrigste Priorität

### FLZU-L10N – Oberfläche und Datumsdarstellung lokalisieren

Status seit 17. September 2026: Die Umsetzung beginnt erst nach allen höher
priorisierten Roadmap-Aufgaben und einer erneuten ausdrücklichen Freigabe des
Root-Vorhabens `ZM-06`. Neue Funktionen und Codeänderungen berücksichtigen
die spätere Lokalisierbarkeit an den jeweils berührten Stellen, lösen aber
keine flächige Umstellung oder Übersetzungsimplementierung aus.

Bei der späteren Umsetzung werden sichtbare Texte sowie Monats- und
Wochentagsnamen app-lokal auf Nextcloud-l10n umgestellt; ISO-Zeiträume,
Status-, Rollen-, Bereichs- und Providerwerte bleiben sprachneutral.
Provider-Namen werden nur gemäß ihrer belastbaren Locale-Quelle dargestellt.
