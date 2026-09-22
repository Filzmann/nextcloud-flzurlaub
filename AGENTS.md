# AGENTS.md - AD Urlaub

## Projekt

Nextcloud-App `adurlaub` fuer geplante und genehmigte Urlaubszeitraeume aller AD-Fachgruppen.

Lokale App-URL:

    https://nextcloud-dev.ddev.site/apps/adurlaub/

Die priorisierte Produktplanung und offene Entscheidungen stehen in `ROADMAP.md`; verbindliche Fach-, Sicherheits- und Architekturregeln bleiben in dieser Datei.
Der ausführliche geltende Ist-Vertrag steht in `docs/architecture.md`; diese
Datei hält die bei jeder Arbeit benötigten Grenzen und Prüfungen.

## Fachvertrag

- Urlaube sind ganztägige, inklusive Datumsbereiche je Nextcloud-UID.
- Urlaubszeiträume derselben Person dürfen sich unabhängig vom Status nicht überschneiden. Bei Änderungen wird der bearbeitete Datensatz selbst von der Prüfung ausgenommen; angrenzende Zeiträume bleiben erlaubt.
- `planned` wird als `U?` angezeigt und ist ein Hinweis ohne Schreib- oder Verfügbarkeitsblockade.
- `approved` wird als `U` angezeigt und blockiert Dienste, Termine, Standarddienste und Meetingverfügbarkeit im AD Kalender.
- Normale Nutzer*innen lesen nur Urlaubsansichten, in denen sie selbst Mitglied sind, gemeinsame Assistenzteams sowie Ansichten mit direkt oder indirekt unterstellten Personen. Bereichsgrenzen bleiben wirksam; Nextcloud-Admins sehen alle Ansichten.
- Nutzer*innen planen eigene Urlaube. Genehmigungen und die Bearbeitung genehmigter Urlaube folgen derselben Vorgesetztenhierarchie wie AD Kalender; Nextcloud-Admins dürfen alle verwalten. Direkte Kolleg*innen dürfen nach administrativer Freischaltung innerhalb derselben Fachgruppe genehmigen, bei BO/EB nur im selben Bürobereich. Eigene Genehmigung bleibt außer für Nextcloud-Admins gesperrt.
- Genehmigungen mit überschneidenden Diensten oder Terminen werden mit einer read-only Konfliktliste abgelehnt; es erfolgt keine automatische Löschung.
- Gruppen stammen aus derselben konfigurierbaren `AdOrganizationDefinition` wie AD Kalender und AdPlaner. Fachrollen, Bereiche, Assistenzteam-Präfix und Organisationssichten werden nicht zusätzlich in AD Urlaub festverdrahtet.
- AD Urlaub ist die kanonische schreibende Urlaubsquelle. Die Jahresmatrix fasst dynamische Assistenzteams und die konfigurierten Organisationssichten zusammen.
- Die Jahresmatrix zeigt Schulferien und gesetzliche Feiertage der organisationsweit konfigurierten Kalenderregion stets als read-only Hintergrundebenen. Fixierte Bänder im Tabellenkopf tragen über jedem zusammenhängenden Zeitraum den Namen, ohne einzelne Tagesspalten zu verbreitern; zu lange Namen werden gekürzt und bleiben vollständig als Tooltip und zugängliche Beschriftung verfügbar. Samstage sind leicht grau, Sonntage dunkler grau; gesetzliche Feiertage markieren ihre vollständige Spalte mit derselben Grauebene wie Sonntage. Heiligabend und Silvester erhalten unabhängig davon eine eigene, vom gesetzlichen Feiertag unterscheidbare Spaltenmarkierung. Die zugängliche Tagesbeschriftung nennt Wochenendart, Jahresendtag, Ferien und Feiertage zusätzlich in Textform. AD Urlaub liest den validierten, dynamischen OpenHolidays-Jahresstand ausschließlich über den gemeinsamen read-only LocalBase-Vertrag; `DE`, `DE-BE` und `Europe/Berlin` bleiben Bestandsdefaults. Der gemeinsame Cache wird täglich sowie bedarfsgesteuert aktualisiert; bei einem Dienstausfall bleibt der letzte gültige Stand mit sichtbarem Veraltet-Hinweis verfügbar. Ferien und Feiertage verändern weder Konflikte, Verfügbarkeit, Genehmigungen noch Rechte.
- Büro Nordost, Büro West und Büro Süd sind eigenständig auswählbare Organisationssichten. Bereichsübergreifende Leitungen erscheinen durch ihre Bereichsmitgliedschaften in jeder passenden Sicht, ohne die Büros zusammenzufassen.
- Die Pflegeansicht enthält Stv. PDL, Büroorganisation Pflege und PFK in dieser Reihenfolge. Stv. PDL darf Büroorganisation Pflege und PFK führen; PDL bleibt beiden übergeordnet. Fahrzeugverwaltung und Empfang bilden eigene globale Ansichten und folgen der gemeinsamen GF-Digi-/Sekretariats-Hierarchie.
- Assistenzteams verwenden dieselbe Nextcloud-Gruppe wie AdPlaner. Separate Gruppen mit einem Suffix wie `-Urlaub` sind keine unterstützte Datenquelle.
- Eigene Urlaubszeiträume werden kompakt über Von/Bis/Notiz eingetragen. Berechtigte Koordinator*innen wechseln den Tagesstatus direkt in der Jahresmatrix; Konflikte werden inline angezeigt.
- AdPlaner bindet seine Urlaubssicht ausschließlich an diese Quelle an und besitzt keine parallele Urlaubspersistenz.
- Die read-only Cross-App-Verträge sind die bounded UID-Abfrage `OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent` sowie `AbsenceQueryEvent` mit `AbsenceInterval`. Die Discovery liefert nur Konten mit geplanten oder genehmigten Urlauben im angefragten halboffenen Zeitraum; Urlaubsnotizen bleiben ausgeschlossen. AD Urlaub greift niemals direkt auf Tabellen anderer Apps zu.
- Der app-eigene Adminabschnitt bietet einen ausschließlich manuell bestätigten Demo-Pack. Er verwendet die gemeinsamen synthetischen Suite-Demokonten, niemals zufällig ausgewählte reale Gruppenmitglieder.
- Fremde oder LDAP-verwaltete Konten werden nicht als Demokonto übernommen; read-only LDAP-Gruppen brechen die Demo-Installation im Preflight vor jeder Mutation ab.
- WordPress-Bestandsdaten werden nicht importiert. Es existiert keine Legacy-Importstrecke.

## Architektur und Sicherheit

- AD Urlaub registriert den subjectgebundenen PersonalDataProvider lazy über
  den öffentlichen Standalone-V1-Vertrag von `filzmann_data_protection` und
  den bestehenden reinen Retention-Dry-Run bis zu dessen gesonderter
  Migration weiterhin über den LocalBase-Pilot. Die Auskunft enthält nur
  Urlaube der typisierten UID einschließlich eigener Notizen; fremde Notizen
  werden niemals übernommen. Retention liefert ausschließlich
  administrativ konfigurierte `REVIEW`-Kandidaten und verändert keine Daten.
- Jeder eigene Urlaubszeitraum erscheint menschenlesbar mit Zeitraum, Zweck
  und einer aus der aktuellen Retention-Regel abgeleiteten Aussage. Ein
  REVIEW-Stichtag wird nicht als automatische Löschfrist dargestellt.
- AD Urlaub registriert zusätzlich einen `ProcessingMetadataProvider` lazy
  über den öffentlichen V1-Vertrag des Datenschutz-Centers. Seine einzige
  fachliche Policyquelle ist `resources/privacy-processing.json`; sie enthält
  keine personenbezogenen Laufzeitdaten und markiert ungeklärte Entscheidungen
  als `PRIVACY-DECISION-REQUIRED`.
- Controller bleiben dünn; Rechte liegen in `VacationAccessService`, Fachlogik in `VacationService`, Datenzugriff im Repository.
- Jeder schreibende API-Pfad prüft serverseitig Zielperson und Besitz/Adminrecht. UI-Ausblendungen sind kein Schutz.
- Auch lesende Team-, Jahres- und Wochenendpunkte liefern nur den durch `VacationVisibilityPolicy` erlaubten Personen- und Ansichtsausschnitt; direkte Requests auf andere Teams bleiben gesperrt.
- Urlaubsnotizen verbleiben in AD Urlaub und werden nicht an konsumierende Apps übertragen.
- App-Root und Tabellenwrapper erfüllen den im lokalen Skill `work-in-nextcloud-app` vollständig beschriebenen Nextcloud-Scrollvertrag.
- Modelle nutzen `get(...)`, `get_all([...])` und `toArray()`.
- Im Frontend bleibt `main.js` ein schlanker Bootstrap. `VacationApp` orchestriert API, Zustand und Ereignisse; `VacationPlan` rendert Teamauswahl, Jahresmatrix, eigene Anträge und Konflikte ohne eigene API-Zugriffe.
- Organisationsweite Gruppen- und Genehmigungsfreigaben liegen bei einer Einzelinstallation im Adminabschnitt von AD Urlaub, ab zwei AD-Produkten im Adminabschnitt der OrgSuite. AD Urlaub besitzt derzeit keine persönlichen Dauer-Einstellungen und deshalb keinen leeren Einstellungstab.

## Gemeinsame Suite-Navigation

- Ohne aktive OrgSuite registriert AD Urlaub einen eigenen Nextcloud-Hauptnavigationseintrag. Ab zwei AD-Produkten ersetzt `orgsuite` diesen durch den gemeinsamen Einstieg `AD`.
- Das Template stellt den optionalen Menühost mit `data-suite="ad"` und `data-current-app="adurlaub"` bereit, lädt aber keine OrgSuite-Assets direkt.
- Ohne AD Kalender bleibt Urlaubsplanung möglich; lediglich die automatische Prüfung gegen Dienste und Termine entfällt. Dieser Standalone-Zustand ist kein Fehler.
- Urlaubs-, Team- und Genehmigungsrechte bleiben ausschliesslich serverseitig im AD Urlaub; Menuesichtbarkeit ist keine Berechtigung.
- Native Nextcloud-Administration erteilt keinen fachlichen Urlaubs-Vollzugriff. Er setzt pro Administrationskonto eine aktive, app-lokale Freigabe von höchstens 24 Stunden voraus; Beginn, geplantes Ende und Widerruf bleiben historisch protokolliert. Ausschließlich Mitglieder der Nextcloud-Gruppe `Datenschutzbeauftragte` verwalten Freigaben und Historie im AD-Urlaub-Hauptbereich; native Administration allein genügt weder für die Steuerung noch für den fachlichen Zugriff.
- Ein natives Administrationskonto ohne aktive Freigabe erhält im Hauptbereich eine sichere Hinweismeldung. Der Direktlink zur Freigabesteuerung erscheint nur, wenn dasselbe Konto zugleich Mitglied von `Datenschutzbeauftragte` ist.
- Das Installieren fachlicher Demodaten benötigt dieselbe aktive Freigabe. Änderungen an Freigabehistorie oder Urlaubsrechten werden gleichzeitig im PersonalDataProvider und PermissionProvider nachgeführt.

## Git und Tests

- Eigenständiges Git-Repository. Diese Datei und lokal referenzierte Skills bilden bei einem direkten Start die vollständige Repository-Steuerung.
- Fuer Git-, Sandbox-, DDEV-/`occ`-Sicherheit, Verifikation und Learning Candidates gilt der lokal mitgefuehrte Skill `work-in-nextcloud-app`; die folgenden Urlaubs-Pruefungen ergaenzen ihn.
- Schnelle Tests: `php tests/run.php` und `node tests/run-js.mjs`.
- Authentifizierter DOM-/CSRF-/Überschneidungs-Smoke: `ADU_BASE_URL=... ADU_USER=... ADU_PASSWORD=... tests/http-smoke.sh`.
- Selbstbereinigende DDEV-Rechtematrix: `tests/access-matrix-ddev-smoke.sh`.
- Migration/DI zusätzlich in DDEV prüfen.

## DDEV

Mount: `/var/www/html/html/custom_apps/adurlaub`

## Dokumentenverantwortung

- `README.md` beschreibt ausschließlich den aktuellen nutzbaren Stand,
  Installation, Betrieb, Tests und den Dokumentationsindex.
- `ROADMAP.md` enthält ausschließlich offene, zurückgestellte oder
  freigabepflichtige Arbeit und Entscheidungen.
- `CHANGELOG.md` dokumentiert erledigte Änderungen releasebezogen; erledigte
  Checklisten verbleiben nicht in der Roadmap.
- `docs/architecture.md` ist die ausführliche Quelle für geltende fachliche
  und technische Architekturverträge.
- `docs/manual-acceptance.md` enthält wiederholbare manuelle Prüfungen und
  keine Produktplanung.
- `AGENTS.md` enthält ausschließlich verbindliche Arbeits-, Sicherheits-,
  Architektur- und Prüfregeln. Zusätzliche Dokumente werden in `README.md`
  mit eindeutiger Zuständigkeit eingeordnet.

## Parent-Governance-Vertrag: 2

- Die für dieses Subrepository anwendbaren Regeln des Parent-Workspaces sind
  verbindlich. Dazu gehören insbesondere app-übergreifende ADRs und
  öffentliche Verträge, Repositorygrenzen sowie Workspace-, Delivery- und
  Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und verschärfen,
  aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-App-, Release- oder Delivery-Arbeit muss ein vermuteter
  neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den Parent
  geprüft werden.

### Entwicklungsphase und Kompatibilitätsbedarf

Entscheidung vom 5. September 2026: Das Gesamtprojekt befindet sich vollständig
in der Entwicklung. Es gibt kein PROD, keinen produktiven Datenbestand und
keinen bereits betriebenen Bestand mit zu erhaltendem Upgradepfad. STAGING
ist eine wegwerfbare Entwicklungs- und Integrationsumgebung und darf im
konkret beauftragten Reinstall vollständig neu aufgebaut werden. Wenige
externe Testnutzer ändern diese Einordnung nicht.

Vor einer Datenmigration, Legacy-Unterstützung, Compatibility Layer,
Deprecated API, Dual-Read/Dual-Write, einem Altschema-Fallback, Übergangsformat
oder der Unterstützung historischer Entwicklungsstände wird geprüft:

1. Wurde der betroffene Zustand jemals produktiv eingesetzt?
2. Benötigen reale Daten oder Nutzer seine Erhaltung?
3. Gibt es einen anderen konkreten technischen Erhaltungsgrund, insbesondere
   einen geltenden Plattform- oder externen API-Vertrag?

Sind alle relevanten Antworten nein, ist die saubere Breaking-Change-/
Reinstall-Lösung der Standard. Frühere rein interne Entwicklungsstände
begründen weder Abwärtskompatibilität noch eine Deprecationfrist.
Entwicklungsschemata dürfen durch ein kanonisches Installationsschema ersetzt,
alte interne APIs und Konfigurationsformate samt ausschließlich dafür
benötigten Adaptern und Tests entfernt werden. Architekturqualität und der
saubere Zielzustand haben Vorrang. Nextclouds nötige Installationsmigrationen
bleiben erhalten; ein Verzeichnisname `Migration` beweist keine Altlast.

Breaking Changes werden im selben Änderungskontext vollständig durchgezogen:
betroffene Provider, Consumer, standardisierte APIs, Vertragsversionen,
Metadaten, Tests und Dokumentation müssen zusammenpassen. Unterstützte
Nextcloud-/openDesk-Plattformverträge, externe Standards, Autorisierung und
Datenschutz gelten unverändert. Fehlende oder inkompatible optionale Provider
bleiben kontrolliert sichtbar. Ein Reinstall erlaubt keine privaten
Fremdtabellenzugriffe oder parallel erfundenen Plattformmechanismen.

Vor destruktiver Arbeit werden die tatsächlich benötigten externen
Testidentitäten, Gruppen, Rollen und nicht reproduzierbaren Testdaten gezielt
gesichert oder über bestehende native Setup-Strukturen reproduzierbar gemacht.
Echte Personen- und Zugangsdaten bleiben außerhalb von Git. Diese begrenzte
Sicherung begründet keine allgemeine Legacy-Unterstützung. Ein Reinstall
bleibt ein normaler unterstützter Entwicklungsweg; der vorhandene
Compatibility-Workflow besitzt den Fresh-Install-Nachweis, dessen aktueller
Belegstatus in `docs/workspace.md` beschrieben ist.

Diese Phase endet ausschließlich durch einen ausdrücklich dokumentierten,
von Simon freigegebenen **Production-Readiness-/Production-Freeze-Entscheid**.
Ein Release Candidate, eine Versionsnummer, ein Staging-Deployment oder ein
externer Testzugang lösen den Wechsel nicht aus. Der Entscheid wird in dieser
kanonischen Lifecycle-Quelle mit Datum, Geltungsbereich und betroffenem
Versions-/Datenstand festgehalten und in die lokale Steuerung projiziert.
Dann werden Upgradepfade, Datenbankmigrationen, Persistenz, Backup/Restore,
Rollback, Release-/API-Kompatibilitätszusagen, Deployment-/Freigabeprozess und
PROD→STAGING/COPY-Strategie neu bewertet. Eine vollständige PROD-Governance
wird jetzt nicht vorweggenommen.

Diese Regel entscheidet den Kompatibilitätsbedarf, erweitert aber keinen
Repository-Schreibauftrag und ersetzt keine Freigabe für eine konkrete
destruktive Aktion. Lokale Regelprojektionen folgen dem bestehenden
`docs/parent-governance-contract.md`; ein unsynchronisierter Einzel-Checkout
darf keinen abweichenden Phasenstand stillschweigend annehmen.

### Prüfaufwand

- Vor einem neuen Test, Scan, Linter, Architektur- oder Systemcheck wird
  geprüft, welcher bestehende Check dieselbe Eigenschaft bereits nachweist.
  Diesen erweitern oder sein nachweislich passendes Ergebnis wiederverwenden;
  ein zusätzlicher Check braucht eine benannte zusätzliche Fehlerklasse oder
  Vertrauensgrenze. Gleicher Input, gleiche Prüfung, gleiche Fehlerklasse und
  gleiche Phase begründen keinen zweiten Lauf. Gestaffelte Unit-, Contract-
  und Runtime-Nachweise bleiben erhalten. Die dokumentierten lokalen
  Prüfeinstiege bestimmen Umfang und Ergebnisgültigkeit.
