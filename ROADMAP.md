# Roadmap – AD Urlaub

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Zukunftsplanung – nicht freigegeben

### ADU-L10N – AD Urlaub vollständig lokalisieren

Status: später, nicht freigegeben; Pilot-App, Reihenfolge und Rohtext-Gate
werden vor jeder Umsetzung appübergreifend separat freigegeben

- Manuelle Monats- und Wochenendnamen sowie sichtbare UI-, Konflikt-,
  Validierungs- und Fehlermeldungen auf aktive Nextcloud-Locale und
  Nextcloud-l10n umstellen.
- ISO-Datumsbereiche, Urlaubsstatus, Rollen-/Bereichsschlüssel und
  Providerpayloads unverändert lassen; Ferien- und Feiertagsnamen nur gemäß
  ihrer belastbaren Provider-/Locale-Quelle darstellen.
- Deutsche Ausgabe, eine weitere Locale, Fallback, Jahresgrenzen,
  Pluralformen, Platzhalter, Escaping und zugängliche Tagesbeschriftungen
  testen.
- Erst nach vollständiger Pilotmigration den app-eigenen Rohtext-Check
  verbindlich schalten.

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
