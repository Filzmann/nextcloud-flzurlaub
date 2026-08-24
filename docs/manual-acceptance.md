# Manuelles Abnahmeformular – AD Urlaub

Dieses Formular dokumentiert die fachliche, visuelle und sicherheitsbezogene
Abnahme von AD Urlaub auf einem realitätsnahen Staging-System. Pro Prüffall
wird genau ein Ergebnis markiert und unter „Warum/Beleg/Abweichung“ knapp
festgehalten, was beobachtet wurde.

Keine personenbezogenen Echtdaten, Gesundheits- oder Urlaubsdetails,
Zugangsdaten oder internen Kennungen eintragen. Ausschließlich neutrale
Testkonten, synthetische Urlaubszeiträume und datensparsame Notizen verwenden.

## Kopfdaten

| Feld | Eintrag |
|---|---|
| Datum und Uhrzeit | |
| Prüfer*in | |
| Umgebung und URL | |
| AD-Urlaub-Version | |
| Nextcloud-Version | |
| Browser und Version | |
| Fenstergröße / Zoom | |
| Neutrale Testkonten, Rollen, Bereiche und Teams | |
| Aktive optionale Apps | |
| Kalenderregion und fachliche Zeitzone | |

Ergebniskennzeichnung: `[ ] erfolgreich` / `[ ] nicht erfolgreich` /
`[ ] nicht geprüft`. Bei „nicht erfolgreich“ oder „nicht geprüft“ ist eine
Begründung verpflichtend.

## Automatisierter Vorabstand am 08.08.2026

Für `0.6.0-rc.2` sind die vollständigen PHP- und JavaScript-Suiten, der
authentifizierte selbstbereinigende HTTP-Smoke, die reale DDEV-Rechtematrix
sowie das isolierte Fresh-/Upgrade-Migrationsschema grün. Die Matrix deckt
positive und negative Sicht-, Bearbeitungs- und Genehmigungsbeziehungen für
PDL, BL, Büroorganisation, PFK, EB und Assistenzteam ab. Der gemeinsame
AD-Kalender-/Urlaubsvertrag wurde zusätzlich durch den realen
Default-Shift-/Abwesenheits-Smoke des AD Kalenders nachgewiesen.

Diese Nachweise füllen das folgende manuelle Formular nicht automatisch aus.
Insbesondere Sichtkontrolle, Tastaturführung, persönlicher Zeitzonenfall,
Provider-Ausfallbilder und die fachliche Gesamtentscheidung bleiben manuell
abzunehmen.

## A. Einstieg, Ansichten und Jahresmatrix

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| A1 | Standalone-Einstieg | AD Urlaub ohne aktive OrgSuite öffnen. | Ein eigener Nextcloud-Einstieg ist vorhanden und die Urlaubsplanung wird ohne AD Kalender geladen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A2 | Suite-Einstieg | Mit aktiver OrgSuite über den AD-Einstieg öffnen und zwischen aktivierten AD-Apps wechseln. | Es gibt keinen doppelten Haupteinstieg; AD Urlaub ist im gemeinsamen Menü korrekt markiert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A3 | Jahr und Ansicht | Zwischen zwei Jahren, einem Assistenzteam und mehreren Organisationsansichten wechseln. | Überschrift, Personen, Tage und Urlaube gehören stets zur gewählten Ansicht und zum gewählten Jahr. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A4 | Bereichsübergreifende Leitung | Eine neutrale Leitung mit zwei Bürobereichen in beiden Ansichten prüfen. | Die Person erscheint in jeder passenden Ansicht, ohne die Büros zusammenzufassen oder innerhalb einer Ansicht doppelt aufzutreten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A5 | Ergänzte Ansichten und Reihenfolge | Pflege, Fahrzeugverwaltung und Empfang öffnen. | Stv. PDL steht vor Büroorganisation Pflege und PFK; Fahrzeugverwaltung und Empfang besitzen getrennte globale Ansichten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A6 | Tastatur, Fokus und Scrollen | Team-/Jahresauswahl, Antrag, Matrixaktionen und große Jahresmatrix nur mit Tastatur bedienen. | Alle Funktionen sind erreichbar, Fokus ist sichtbar, und die Matrix bleibt horizontal sowie vertikal innerhalb der App scrollbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## B. Eigene Urlaubszeiträume

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Geplanter eigener Urlaub | Als normales Testkonto einen mehrtägigen Zeitraum mit neutraler Notiz anlegen und neu laden. | Der inklusive Datumsbereich bleibt als eigener geplanter Urlaub erhalten und erscheint als `U?`. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B2 | Angrenzende Zeiträume | Einen zweiten Zeitraum direkt am Tag vor oder nach B1 anlegen. | Angrenzende Zeiträume sind zulässig und bleiben getrennte Datensätze. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B3 | Überlappung | Einen teilweise und einen vollständig überlappenden Zeitraum für dieselbe Person versuchen. | Beide Versuche werden verständlich abgewiesen; der bestehende Urlaub bleibt unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B4 | Änderung ohne Selbstkollision | Einen bestehenden eigenen Zeitraum verschieben, ohne einen anderen Zeitraum zu berühren. | Die Änderung gelingt; der bearbeitete Datensatz kollidiert nicht fälschlich mit sich selbst. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B5 | Löschung | Einen entbehrlichen eigenen geplanten Testurlaub löschen. | Nur dieser Zeitraum verschwindet; andere Urlaube bleiben erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B6 | Fremde Person aus Requestdaten | Als normales Konto versuchen, über direkten API-Aufruf einen Urlaub für eine andere UID anzulegen oder zu ändern. | Der Server verweigert die Aktion; die Zielperson wird nicht aus unberechtigten Requestdaten übernommen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## C. Genehmigung, Sichtbarkeit und Hierarchie

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| C1 | Vorgesetztenhierarchie | Mit neutralen Leitungs- und unterstellten Konten einen geplanten Urlaub genehmigen; die Gegenrichtung versuchen. | Die Leitung darf im vorgesehenen Scope genehmigen; Untergebene dürfen übergeordnete Personen nicht genehmigen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C2 | Bereichsgrenze | Als BL oder StvBL Urlaube von BO/EB im eigenen und in einem fremden Bereich genehmigen. | Nur der passende Bürobereich ist erlaubt; PFK wird durch diese Bürohierarchie nicht freigegeben. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C3 | Peer-Freigabe | Administrative Peer-Freigabe für eine geeignete Fachgruppe aus- und einschalten und direkte Kolleg*innen vergleichen. | Ohne Freigabe wird verweigert; mit Freigabe gilt sie nur in der definierten Fachgruppe und bei BO/EB im gemeinsamen Bereich. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C4 | Keine Selbstgenehmigung | Den eigenen geplanten Urlaub als normales beziehungsweise leitendes Konto genehmigen; anschließend als Nextcloud-Admin prüfen. | Selbstgenehmigung bleibt außer für Nextcloud-Admins gesperrt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C5 | Lesescope | Ansichten mit eigener Mitgliedschaft, gemeinsamen Assistenzteams, unterstellten und fachlich fremden Personen über UI und direkten Request aufrufen. | Nur der erlaubte Personen- und Ansichtsausschnitt wird geliefert; fremde Ansichten bleiben serverseitig gesperrt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C6 | Urlaubsnotiz | Eine neutrale Notiz als berechtigte und als unberechtigte Person prüfen sowie einen Consumer öffnen. | Die Notiz bleibt ausschließlich im erlaubten AD-Urlaub-Kontext und wird nicht an Consumer-Apps übertragen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## D. Ferien, Feiertage und zugängliche Darstellung

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| D1 | Ferienbänder | Ein Jahr mit mehreren Schulferienzeiträumen anzeigen. | Jeder zusammenhängende Zeitraum besitzt ein flaches, fixiertes Namensband; Tagesspalten werden nicht verbreitert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D2 | Lange Bezeichnung | Einen langen Feriennamen in schmalem Fenster prüfen. | Der sichtbare Text wird nötigenfalls gekürzt; Tooltip und zugängliche Beschriftung enthalten den vollständigen Namen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D3 | Wochenenden und Feiertage | Samstag, Sonntag und einen gesetzlichen Feiertag vergleichen. | Samstag und Sonntag sind unterschiedlich markiert; ein Feiertag kennzeichnet die vollständige Spalte wie ein Sonntag und bleibt textlich benannt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D4 | Heiligabend und Silvester | Beide Jahresendtage in der Matrix prüfen. | Sie sind unabhängig von gesetzlichen Feiertagen als eigene Jahresendtage optisch und textlich unterscheidbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D5 | Urlaub bleibt erkennbar | Geplanten und genehmigten Urlaub auf Wochenend-, Ferien- und Feiertagsspalten anzeigen. | Urlaubsstatus bleibt erkennbar und wird nicht ausschließlich über Farbe vermittelt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D6 | Veralteter Kalenderstand | Mit vorhandenem Cache einen Providerausfall in einer isolierten Testumgebung simulieren. | Der letzte gültige Stand bleibt sichtbar und wird als veraltet erklärt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D7 | Noch kein Kalenderstand | Einen nicht gecachten Jahresstand bei simuliertem Providerausfall öffnen. | Die Nichtverfügbarkeit wird verständlich gemeldet und nicht als gültiger leerer Kalender dargestellt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## E. AD-Kalender-Integration und Standalone-Betrieb

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| E1 | Geplanter Urlaub im Kalender | Mit aktiver Integration einen geplanten Urlaub in AD Kalender anzeigen und dort am selben Tag einen Eintrag anlegen. | `U?` erscheint read-only und blockiert die Kalenderaktion nicht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E2 | Genehmigter Urlaub im Kalender | Einen konfliktfreien Testurlaub genehmigen und Dienste, Termine, Standarddienste sowie Meetingverfügbarkeit prüfen. | `U` erscheint read-only und blockiert die vereinbarten Kalenderwege. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E3 | Konflikt bei Genehmigung | Einen geplanten Urlaub über einen bestehenden synthetischen Dienst oder Termin genehmigen. | Die Genehmigung wird mit read-only Konfliktliste abgelehnt; es wird weder Urlaub noch Kalendereintrag automatisch gelöscht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E4 | Standalone ohne Kalender | AD Kalender deaktivieren und Anlegen, Ändern, Genehmigen sowie Löschen mit konfliktfreiem Testfall wiederholen. | Urlaubsplanung bleibt nutzbar; die fehlende automatische Konfliktprüfung wird sichtbar erklärt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E5 | Fehlerisolation | Einen optionalen Konfliktprovider gezielt fehlschlagen lassen. | Der Fehler erweitert keine Rechte und erzeugt keine stillschweigende Genehmigung oder Datenlöschung. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E6 | Demo-Pack-Schutz | Demo-Pack ohne Bestätigung versuchen und anschließend nur in einer vorgesehenen Testumgebung bestätigen. | Ohne Bestätigung bleibt die Aktion gesperrt; ausschließlich synthetische lokale Konten und Urlaube werden verwendet. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## Abschlussentscheidung

| Feld | Eintrag |
|---|---|
| Anzahl erfolgreich | |
| Anzahl nicht erfolgreich | |
| Anzahl nicht geprüft | |
| Kritische Abweichungen / Ticketreferenzen | |
| Erneute Prüfung erforderlich bis | |
| Gesamtentscheidung | [ ] abgenommen [ ] mit Auflagen abgenommen [ ] nicht abgenommen |
| Begründung der Gesamtentscheidung | |
| Name / Datum | |
