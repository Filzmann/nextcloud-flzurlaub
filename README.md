# Filzmann Urlaubsplanung

Urlaubsplanung für Assistenzteams und organisatorische FLZ-Fachgruppen. Geplante Urlaube werden als Hinweis, genehmigte Urlaube als blockierende Abwesenheit an andere Apps geliefert. Eine bounded LocalBase-Discovery meldet dafür ausschließlich betroffene Konto-UIDs im angefragten Zeitraum; Notizen verlassen Filzmann Urlaubsplanung nicht.

Die Jahresmatrix zeigt Schulferien und gesetzliche Feiertage der organisationsweit konfigurierten Region als getrennte read-only Ebenen. Im fixierten Tabellenkopf bildet jeder zusammenhängende Zeitraum einen flachen farbigen Streifen mit Namen. Beschriftungen verändern die feste Breite der Tagesspalten nicht; zu lange Namen werden gekürzt und bleiben als Tooltip vollständig verfügbar. Samstage sind leicht grau, Sonntage dunkler; gesetzliche Feiertage färben ihre vollständige Spalte mit derselben Grauebene wie Sonntage. Heiligabend und Silvester sind als eigene Jahresendtage farblich und textlich von gesetzlichen Feiertagen abgegrenzt. Urlaubsfarben bleiben darunter erkennbar. LocalBase lädt die Daten anhand des gemeinsamen Kalenderkontexts aus der [OpenHolidays API](https://www.openholidaysapi.org/) und nutzt sie unter der ODbL. `DE-BE` und `Europe/Berlin` bleiben Bestandsdefaults. Normalisierte Jahresstände liegen regionsgebunden als AppConfig in der Nextcloud-Datenbank. Sie werden täglich und bei Bedarf erneuert; bei einem Dienstausfall bleibt der letzte gültige Stand sichtbar und wird als veraltet gekennzeichnet.

Der Zugriff verwendet ausschließlich die fest hinterlegte HTTPS-Adresse der OpenHolidays API und benötigt keinen Schlüssel. Beim ersten Aufruf eines noch nicht gecachten Jahres kann die externe Abfrage synchron erfolgen. Der gemeinsame Nextcloud-Hintergrundjob `OCA\LocalBase\BackgroundJob\RefreshHolidayCalendarJob` hält das aktuelle und die zwei folgenden Jahre vorab aktuell.

## Staging-Kompatibilität

- Nextcloud 33 bis 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 33 bis 34 unterstützten Bereichs
- Laufzeitbasis: `localbase`; `orgsuite` ist ab zwei FLZ-Fachprodukten optional aktiv
- App-ID und Installationsordner: `flzurlaub`

## Installation

Für Staging und Auslieferung das Produktbundle `flz-product-flzurlaub-<release>.tar.gz` und dessen enthaltenes `install.sh` verwenden. Es prüft und installiert LocalBase automatisch; ab dem zweiten FLZ-Fachprodukt aktiviert es OrgSuite.

Filzmann Urlaubsplanung funktioniert einzeln. Ohne Filzmann Kalender bleibt die Urlaubsplanung vollständig nutzbar; die automatische Prüfung genehmigter Urlaube gegen Dienste und Termine entfällt und wird sichtbar erklärt.

Der Befehl `flzurlaub:demo:seed` erzeugt synthetische Testdaten und wird nicht automatisch ausgeführt.

## Datenschutz

Der app-eigene Processing-Katalog beschreibt Urlaubsverwaltung einschließlich
freiwilliger Notizen sowie temporäre Adminfreigaben über den öffentlichen
V1-Vertrag des Datenschutz-Centers. Er enthält ausschließlich Policy-Metadaten
und keine personenbezogenen Laufzeitdatensätze. Offene Rechtsgrundlagen,
Retention-, Backup-, Restore- und Betroffenenrechtsentscheidungen bleiben als
`PRIVACY-DECISION-REQUIRED` sichtbar. Die Retention-Vorschau registriert sich
lazy über den öffentlichen V1-Vertrag des Datenschutz-Centers, liefert globale
Treffer paginiert und datenminimiert ausschließlich als
`REVIEW`-Kandidaten und verändert keine Daten. Ohne kompatibles
Datenschutz-Center bleibt Filzmann Urlaubsplanung eigenständig nutzbar.

## Zeitlich begrenzter Admin-Vollzugriff

Ein Nextcloud-Administrationskonto erhält nicht automatisch Zugriff auf alle Urlaubsdaten. Ausschließlich Mitglieder der Nextcloud-Gruppe `Datenschutzbeauftragte` verwalten die app-lokale Freigabe im Hauptbereich von Filzmann Urlaubsplanung für ein aktives Administrationskonto; der Datenschutzrolle muss selbst kein nativer Adminstatus zugewiesen sein. Die Freigabe gilt für 1, 4, 8 oder höchstens 24 Stunden und kann vorzeitig widerrufen werden. Native Administration allein genügt weder für die Freigabesteuerung noch für den fachlichen Zugriff. Beginn, geplantes Ende, Freigabe und Widerruf werden app-lokal protokolliert und in Datenschutz- sowie Berechtigungsprovider einbezogen.

## Roadmap

Geplante Erweiterungen und offene Produktentscheidungen stehen in der [Roadmap](ROADMAP.md).

Für die fachliche, visuelle und sicherheitsbezogene Staging-Prüfung steht ein
ausfüllbares [manuelles Abnahmeformular](docs/manual-acceptance.md) bereit.
Urlaubsdetails und personenbezogene Echtdaten werden darin nicht dokumentiert.

Installations-, Betriebs- und Abnahmeunterlagen stehen im öffentlichen [Filzmann Nextcloud Plugins-Projekt](https://github.com/Filzmann/flz-full-suite).

## Dokumentation

- [Architektur](docs/architecture.md)
- [Manuelle Abnahme](docs/manual-acceptance.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Arbeitsregeln](AGENTS.md)
