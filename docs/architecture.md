# Fach- und Integrationsarchitektur von AD Urlaub

Diese Datei dokumentiert den geltenden Ist-Vertrag. Zukünftige Ziele stehen
in `ROADMAP.md`; kurze harte Arbeits-, Rechte- und Testregeln stehen in
`AGENTS.md`.

## Urlaubsmodell

Urlaube sind ganztägige inklusive Datumsbereiche je Nextcloud-UID. Zeiträume
derselben Person dürfen sich unabhängig vom Status nicht überschneiden;
angrenzende Zeiträume bleiben erlaubt. `planned` wird als `U?` angezeigt und
blockiert nicht. `approved` wird als `U` angezeigt und blockiert Dienste,
Termine, Standarddienste und Meetingverfügbarkeit im AD Kalender.

AD Urlaub ist die kanonische schreibende Urlaubsquelle. AdPlaner besitzt keine
parallele Urlaubspersistenz. Der read-only Cross-App-Vertrag ist
`OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent` für die bounded
Ermittlung betroffener Konto-UIDs sowie `AbsenceQueryEvent` mit
`AbsenceInterval`. Ganztägige inklusive Urlaubsdaten werden an den lokalen
Datumsgrenzen der im Event angefragten fachlichen Zeitzone ausgewertet;
Urlaubsnotizen verlassen AD Urlaub nicht. Fachapps greifen nicht direkt auf
Tabellen anderer Apps zu.

## Sichtbarkeit und Genehmigung

Normale Nutzer*innen lesen nur eigene, gemeinsame Assistenzteam- oder
organisatorisch unterstellte Ansichten. Bereichsgrenzen bleiben wirksam;
Nextcloud-Admins sehen alle Ansichten. Nutzer*innen planen eigene Urlaube.
Genehmigung und Bearbeitung genehmigter Urlaube folgen der gemeinsamen
Vorgesetztenhierarchie und administrativ freigegebenen Peergrenzen. Eigene
Genehmigung bleibt außer für Nextcloud-Admins gesperrt.

Genehmigungen mit überschneidenden Diensten oder Terminen werden mit einer
read-only Konfliktliste abgelehnt; es erfolgt keine automatische Löschung.
Auch lesende Team-, Jahres- und Wochenendpunkte liefern nur den durch
`VacationVisibilityPolicy` erlaubten Ausschnitt. Urlaubsnotizen verbleiben in
AD Urlaub und werden nicht an Consumer übertragen.

## Organisation und Ansichten

Gruppen, Rollen, Bereiche, Assistenzteam-Präfix, Hierarchie und
Organisationssichten stammen aus derselben konfigurierbaren
`AdOrganizationDefinition` wie AD Kalender und AdPlaner. Separate
Assistenzteamgruppen mit einem Urlaubssuffix sind keine unterstützte
Datenquelle.

Büro Nordost, West und Süd bleiben eigenständige Ansichten. Die Pflegeansicht
enthält stellvertretende PDL, Büroorganisation Pflege und PFK in dieser
Reihenfolge. Fahrzeugverwaltung und Empfang besitzen eigene globale Ansichten.

## Ferien und Feiertage

Die Jahresmatrix zeigt Schulferien und gesetzliche Feiertage der
organisationsweit konfigurierten Region als getrennte read-only
Hintergrundebenen. Zusammenhängende Zeiträume erhalten flache benannte Bänder,
ohne Tagesspalten zu verbreitern. Vollständige Namen bleiben per Tooltip und
zugänglicher Beschriftung verfügbar. Wochenenden, Feiertage, Heiligabend und
Silvester werden zusätzlich textlich unterschieden.

LocalBase lädt die validierten Jahresstände anhand des gemeinsamen
Kalenderkontexts aus der OpenHolidays API. `DE`, `DE-BE` und
`Europe/Berlin` bleiben Bestandsdefaults. Der regionsgebundene gemeinsame
Cache wird täglich für das aktuelle und die zwei folgenden Jahre sowie
bedarfsgesteuert aktualisiert. Bei Ausfällen bleibt der letzte gültige Stand
mit erkennbarem Veraltet-Status verfügbar. Ferien und Feiertage verändern
weder Konflikte, Verfügbarkeit, Genehmigungen noch Rechte.

## Standalone, Demo und Administration

Ohne AD Kalender bleibt Urlaubsplanung gültig; nur die automatische
Dienst-/Terminkonfliktprüfung entfällt. Organisationsweite Gruppen- und
Genehmigungsfreigaben liegen bei Einzelinstallation im Adminabschnitt von AD
Urlaub und ab zwei AD-Produkten im OrgSuite-Adminabschnitt. AD Urlaub besitzt
derzeit keine persönlichen Dauereinstellungen und deshalb keinen leeren
Einstellungstab.

Das Demo-Pack läuft nur nach ausdrücklicher Bestätigung, verwendet gemeinsame
synthetische Suite-Demokonten und übernimmt niemals fremde oder
LDAP-verwaltete Konten. Read-only LDAP-Gruppen brechen den Preflight ab.
WordPress-Bestandsdaten werden nicht importiert.
