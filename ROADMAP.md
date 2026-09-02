# Roadmap – AD Urlaub

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Nextcloud-Kompatibilitätsgate

### ADU-NC-COMPAT – OpenDesk-Boden 33 und künftige Majors nachweisen

Status: `info.xml` bleibt bei 34/34; NC 33.0.7 ist nur statisch geprüft. Vor
`min-version="33"` müssen Fresh Install/Upgrade, DI, Migrationen, Jobs,
Urlaubs- und Genehmigungsrechte, Konfliktabfrage sowie Standalone-,
LocalBase- und Kalenderkombinationen einschließlich fehlendem Provider,
Assets und sichtbare Jahresmatrix grün sein. Die Obergrenze folgt ausschließlich
dem lückenlosen app-lokalen `verify-nextcloud-future-compatibility`-Nachweis.

## Systemweit gegatete app-lokale Aufgabe

### ADU-L10N – Oberfläche und Datumsdarstellung lokalisieren

Aktivierung ausschließlich nach Freigabe des Root-Vorhabens `ZM-06`.
Sichtbare Texte sowie Monats- und Wochentagsnamen werden app-lokal auf
Nextcloud-l10n umgestellt; ISO-Zeiträume, Status-, Rollen-, Bereichs- und
Providerwerte bleiben sprachneutral. Provider-Namen werden nur gemäß ihrer
belastbaren Locale-Quelle dargestellt.

## Aktueller Fokus

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Jahresmatrix, eigene Anträge, Genehmigungshierarchie und Bereichsgrenzen auf einem realitätsnahen Staging fachlich abnehmen.
- Die Konfliktprüfung gegen AD Kalender und den gültigen Standalone-Betrieb ohne Kalender absichern.
- Sichtbarkeit und Datenschutz von Urlaubsnotizen und Organisationsansichten produktiv prüfen.
- Die additiv migrierten Pflege-, Fahrzeugverwaltungs- und Empfangsansichten samt positiven und negativen Hierarchierechten fachlich abnehmen.

## Geplante Erweiterungen

- Weitere Funktionen werden erst aufgenommen, wenn ein konkreter fachlicher Bedarf und der betroffene Rechtevertrag benannt sind.
- Optionale Integrationen bleiben read-only oder verwenden einen ausdrücklich freigegebenen kleinen LocalBase-Vertrag; fehlende Provider bleiben ein gültiger Zustand.
