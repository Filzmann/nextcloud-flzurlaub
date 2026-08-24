# Changelog

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
