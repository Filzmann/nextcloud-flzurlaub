# AD Urlaub

Urlaubsplanung für Assistenzteams und organisatorische AD-Fachgruppen. Geplante Urlaube werden als Hinweis, genehmigte Urlaube als blockierende Abwesenheit an andere Apps geliefert. Eine bounded LocalBase-Discovery meldet dafür ausschließlich betroffene Konto-UIDs im angefragten Zeitraum; Notizen verlassen AD Urlaub nicht.

Die Jahresmatrix zeigt Schulferien und gesetzliche Feiertage der organisationsweit konfigurierten Region als getrennte read-only Ebenen. Im fixierten Tabellenkopf bildet jeder zusammenhängende Zeitraum einen flachen farbigen Streifen mit Namen. Beschriftungen verändern die feste Breite der Tagesspalten nicht; zu lange Namen werden gekürzt und bleiben als Tooltip vollständig verfügbar. Samstage sind leicht grau, Sonntage dunkler; gesetzliche Feiertage färben ihre vollständige Spalte mit derselben Grauebene wie Sonntage. Heiligabend und Silvester sind als eigene Jahresendtage farblich und textlich von gesetzlichen Feiertagen abgegrenzt. Urlaubsfarben bleiben darunter erkennbar. LocalBase lädt die Daten anhand des gemeinsamen Kalenderkontexts aus der [OpenHolidays API](https://www.openholidaysapi.org/) und nutzt sie unter der ODbL. `DE-BE` und `Europe/Berlin` bleiben Bestandsdefaults. Normalisierte Jahresstände liegen regionsgebunden als AppConfig in der Nextcloud-Datenbank. Sie werden täglich und bei Bedarf erneuert; bei einem Dienstausfall bleibt der letzte gültige Stand sichtbar und wird als veraltet gekennzeichnet.

Der Zugriff verwendet ausschließlich die fest hinterlegte HTTPS-Adresse der OpenHolidays API und benötigt keinen Schlüssel. Beim ersten Aufruf eines noch nicht gecachten Jahres kann die externe Abfrage synchron erfolgen. Der gemeinsame Nextcloud-Hintergrundjob `OCA\LocalBase\BackgroundJob\RefreshHolidayCalendarJob` hält das aktuelle und die zwei folgenden Jahre vorab aktuell.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- Laufzeitbasis: `localbase`; `orgsuite` ist ab zwei AD-Fachprodukten optional aktiv
- App-ID und Installationsordner: `adurlaub`

## Installation

Für Staging und Auslieferung das Produktbundle `ad-product-adurlaub-<release>.tar.gz` und dessen enthaltenes `install.sh` verwenden. Es prüft und installiert LocalBase automatisch; ab dem zweiten AD-Fachprodukt aktiviert es OrgSuite.

AD Urlaub funktioniert einzeln. Ohne AD Kalender bleibt die Urlaubsplanung vollständig nutzbar; die automatische Prüfung genehmigter Urlaube gegen Dienste und Termine entfällt und wird sichtbar erklärt.

Der Befehl `adurlaub:demo:seed` erzeugt synthetische Testdaten und wird nicht automatisch ausgeführt.

## Roadmap

Geplante Erweiterungen und offene Produktentscheidungen stehen in der [Roadmap](ROADMAP.md).

Für die fachliche, visuelle und sicherheitsbezogene Staging-Prüfung steht ein
ausfüllbares [manuelles Abnahmeformular](docs/manual-acceptance.md) bereit.
Urlaubsdetails und personenbezogene Echtdaten werden darin nicht dokumentiert.

Installations-, Betriebs- und Abnahmeunterlagen stehen im öffentlichen [AD-Suite-Projekt](https://github.com/Filzmann/ad-suite).
