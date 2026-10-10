# Changelog

## Unreleased

- Nextcloud 35.0.1 durch Fresh Install, Upgrade 34→35 sowie Provider-,
  Berechtigungs-, Runtime-, UI-/API- und Asset-Smokes nachgewiesen und den
  unterstützten Bereich auf die lückenlosen Hauptversionen 33 bis 35
  erweitert. Nextcloud 36 bleibt ungeprüft.
- Retention-Dry-Run vom LocalBase-Pilot auf den öffentlichen
  V1-Providervertrag des Datenschutz-Centers migriert; globale Vorschauen sind
  paginiert, datenminimiert und weiterhin strikt `REVIEW`-only.
- Die zeitlich begrenzte fachliche Adminfreigabe auf Mitglieder der
  Nextcloud-Gruppe `Datenschutzbeauftragte` begrenzt, die Steuerung aus dem
  technischen Adminbereich in den rollenabhängigen Hauptbereich verschoben
  und Allow-, Deny-, Manipulations-, UI-, Audit- und Providerprojektionen
  automatisiert abgesichert.
- Einen app-eigenen Processing-Metadata-Katalog für Urlaubsverwaltung und
  temporäre Adminfreigaben über den V1-Vertrag des Datenschutz-Centers
  veröffentlicht; die bestehende Retention-Vorschau bleibt `REVIEW`-only.
- Nextcloud 33.0.7 bis 34.0.2 durch Fresh Install und Upgrade 33→34 mit
  App-Suiten, DI-/Registrierungs-, API-, Rechte-, HTTPS-, Asset- und UI-Smokes unterstützt.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.

## 0.7.0-rc.1

- Subjectgebundene persönliche Datenauskunft für eigene Urlaubszeiträume einschließlich eigener Notizen ergänzt.
- Konfigurierbare, ausschließlich lesende Retention-Vorschau mit `REVIEW`-Kandidaten eingeführt; sie löscht und verändert keine Daten.

## 0.6.0-rc.2

- Ferien- und Feiertagsdaten auf den gemeinsamen, ausfallsicheren LocalBase-Kalendervertrag umgestellt und den früheren app-eigenen Refreshjob entfernt.
- Sichtbarkeits-, Hierarchie- und Peer-Rechte mit lokalen sowie realen selbstbereinigenden DDEV-Matrizen abgesichert.
- Fresh- und Upgrade-Schema mit synthetischen Bestandsdaten reproduzierbar geprüft und das manuelle Abnahmeformular auf den aktuellen Produktvertrag erweitert.

## 0.5.0-rc.4

- Berliner Schulferien und gesetzliche Feiertage dynamisch aus der OpenHolidays API geladen, validiert und als ausfallsicherer Jahrescache in Nextcloud gespeichert.
- Flache, benannte Ferien- und Feiertagsbänder sowie feste Personenachse und gleich breite Tagesspalten ergänzt.
- Samstage, dunklere Sonntage, Feiertagsspalten sowie eigenständige Markierungen für Heiligabend und Silvester zugänglich und farblich unterschieden.
- Täglichen Hintergrundjob für die Aktualisierung des aktuellen und der zwei folgenden Jahre auch bei Updates bestehender Installationen idempotent registriert.

## 0.5.0-rc.1

- Eigenständige Navigation ohne OrgSuite ergänzt.
- Abwesenheitsfähigkeit über den optionalen LocalBase-Integrationsvertrag veröffentlicht.
- Sichtbarer Standalone-Hinweis ergänzt, wenn die automatische Kalenderkonfliktprüfung fehlt.

## 0.4.14-rc.1

- Öffentliche Projekt-, Quellcode- und Fehlerkanäle ergänzt.
- Neutrale Assistenzteams Team A, Team B und Team C in Sichtbarkeitsverträgen.

## 0.4.13-rc.1

- Erster reproduzierbarer Staging-Releasekandidat für Nextcloud 34 und PHP ab 8.3.
- Gemeinsame Team- und Organisationssichten mit serverseitiger Rechteprüfung.
- Überschneidungs- und Dienst-/Terminkonflikte bei Genehmigungen.
- Authentifizierte CSRF-, Konflikt- und HTTP-Rechtematrix.
