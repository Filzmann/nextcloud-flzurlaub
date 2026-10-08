(function () {
    'use strict';

    /**
     * Zweck: Rendert Teamauswahl, Jahresmatrix, eigene Anträge und Konfliktlisten ohne API-Zugriffe.
     * Zusammenspiel: VacationApp aktualisiert den Zustand und stößt danach die passenden Render-Methoden an.
     */
    class VacationPlan {
        constructor({ elements, state, holidays = new window.FlzUrlaub.models.HolidayCalendar() }) {
            this.elements = elements;
            this.state = state;
            this.holidays = holidays;
        }

        renderChrome() {
            const categories = [
                ['asn', 'Assistenzteams'],
                ['organization', 'Büros und Fachgruppen'],
            ];
            this.elements.team.replaceChildren(...categories.map(([category, label]) => {
                const group = document.createElement('optgroup');
                group.label = label;
                group.append(...this.state.teams.filter(team => team.category === category).map(team => {
                    const option = this.node('option', team.displayName);
                    option.value = team.id;
                    option.selected = team.id === this.state.teamId;
                    return option;
                }));
                return group;
            }).filter(group => group.children.length));
            this.elements.year.value = String(this.state.year);
            const team = this.selectedTeam();
            this.elements['plan-title'].textContent = team ? `${team.displayName} – Urlaub ${this.state.year}` : `Urlaub ${this.state.year}`;
            this.holidays.replace(this.state.plan?.holidays || {});
            this.renderHolidayStatus();
        }

        renderHolidayStatus() {
            const status = this.elements['holiday-status'];
            if (!status) return;
            const calendar = this.holidays.toArray();
            if (!this.state.plan) {
                status.textContent = 'Berliner Ferien- und Feiertagsdaten werden geladen.';
                return;
            }
            if (calendar.cacheStatus === 'unavailable') {
                status.textContent = `Der Ferien- und Feiertagsdienst ist nicht erreichbar. Für ${this.state.year} liegen noch keine zwischengespeicherten Daten vor.`;
                return;
            }
            const fetchedAt = this.dateTime(calendar.fetchedAt);
            status.textContent = calendar.cacheStatus === 'stale'
                ? `Berliner Ferien und Feiertage aus dem letzten erfolgreichen Abruf vom ${fetchedAt}. Die Aktualisierung ist derzeit nicht möglich.`
                : `Berliner Ferien und Feiertage von ${calendar.source.name}, aktualisiert am ${fetchedAt}.`;
        }

        renderPlan() {
            const plan = this.state.plan;
            if (!plan) return;
            const currentUserInTeam = plan.assistants.some(employee => employee.uid === this.state.currentUser?.uid);
            this.elements['own-form'].hidden = !currentUserInTeam;
            this.elements['own-requests'].hidden = !currentUserInTeam;
            this.elements['calendar-head'].replaceChildren(...this.renderHeader(plan.days));
            this.elements['calendar-body'].replaceChildren(...plan.assistants.map(employee => this.renderEmployee(employee, plan.days)));
            this.renderOwnRequests();
        }

        renderOwnRequests() {
            const own = (this.state.plan?.requests || []).filter(item => item.employeeUid === this.state.currentUser?.uid);
            this.elements['own-requests'].replaceChildren(...own.map(request => {
                const badge = this.node(
                    'span',
                    `${this.dateShort(request.startDate)}–${this.dateShort(request.endDate)} · ${request.status === 'approved' ? 'genehmigt' : 'geplant'}`,
                    `flz-vacation-request flz-vacation-request-${request.status}`,
                );
                if (request.status === 'planned') {
                    const button = this.node('button', '×');
                    button.type = 'button';
                    button.dataset.action = 'delete-own';
                    button.dataset.id = String(request.id);
                    button.title = 'Urlaubsantrag löschen';
                    button.setAttribute('aria-label', 'Urlaubsantrag löschen');
                    badge.append(button);
                }
                return badge;
            }));
        }

        clearConflicts() {
            this.elements.conflicts.hidden = true;
            this.elements.conflicts.replaceChildren();
        }

        showConflicts(error) {
            if (error.status !== 409 || !error.data?.conflicts) return;
            this.elements.conflicts.hidden = false;
            this.elements.conflicts.replaceChildren(
                this.node('strong', 'Genehmigung nicht möglich:'),
                ...error.data.conflicts.map(conflict => this.node(
                    'div',
                    `${conflict.type === 'shift' ? 'Dienst' : 'Termin'} ${new Date(conflict.start).toLocaleString('de-DE')}–${new Date(conflict.end).toLocaleString('de-DE')}${conflict.label ? ` · ${conflict.label}` : ''}`,
                )),
            );
        }

        renderHeader(days) {
            return [this.renderSchoolHolidayBand(days), this.renderPublicHolidayBand(days), this.renderDayHeader(days)];
        }

        renderSchoolHolidayBand(days) {
            const row = document.createElement('tr');
            row.className = 'flz-vacation-school-holiday-row';
            const label = this.node('th', 'Schulferien');
            label.scope = 'col';
            row.append(label);
            for (let start = 0; start < days.length;) {
                const name = this.holidays.schoolHolidayName(days[start].date);
                let end = start + 1;
                while (end < days.length && this.holidays.schoolHolidayName(days[end].date) === name) end++;
                const cell = document.createElement('th');
                cell.colSpan = end - start;
                if (name) {
                    cell.scope = 'colgroup';
                    cell.className = 'is-school-holiday';
                    cell.title = name;
                    cell.setAttribute('aria-label', `${name}: ${this.dateLong(days[start].date)}–${this.dateLong(days[end - 1].date)}`);
                    cell.append(this.node('span', name, 'flz-vacation-holiday-label'));
                } else {
                    cell.setAttribute('aria-hidden', 'true');
                }
                row.append(cell);
                start = end;
            }
            return row;
        }

        renderPublicHolidayBand(days) {
            const row = document.createElement('tr');
            row.className = 'flz-vacation-public-holiday-row';
            const label = this.node('th', 'Feiertage');
            label.scope = 'col';
            row.append(label);
            for (let start = 0; start < days.length;) {
                const name = this.holidays.publicHolidayName(days[start].date);
                let end = start + 1;
                while (end < days.length && this.holidays.publicHolidayName(days[end].date) === name) end++;
                const cell = document.createElement('th');
                cell.colSpan = end - start;
                if (name) {
                    cell.scope = 'colgroup';
                    cell.className = 'is-public-holiday';
                    cell.title = name;
                    cell.setAttribute('aria-label', `${name}: ${this.dateLong(days[start].date)}–${this.dateLong(days[end - 1].date)}`);
                    cell.append(this.node('span', name, 'flz-vacation-holiday-label'));
                } else {
                    cell.setAttribute('aria-hidden', 'true');
                }
                row.append(cell);
                start = end;
            }
            return row;
        }

        renderDayHeader(days) {
            const head = document.createElement('tr');
            head.className = 'flz-vacation-day-row';
            const name = this.node('th', 'Mitarbeiter*in');
            name.scope = 'col';
            head.append(name);
            for (const day of days) {
                const th = document.createElement('th');
                th.scope = 'col';
                const schoolHoliday = this.holidays.schoolHolidayName(day.date);
                const publicHoliday = this.holidays.publicHolidayName(day.date);
                const yearEndSpecial = this.yearEndSpecialName(day.date);
                if (day.weekday === 6) th.classList.add('is-saturday');
                if (day.weekday === 7) th.classList.add('is-sunday');
                if (publicHoliday) th.classList.add('is-public-holiday-column');
                if (yearEndSpecial) th.classList.add('is-year-end-special');
                const labels = [this.dateShort(day.date), this.weekdayName(day), yearEndSpecial, schoolHoliday && `Berliner Schulferien: ${schoolHoliday}`, publicHoliday && `Feiertag: ${publicHoliday}`].filter(Boolean);
                if (labels.length > 1) th.setAttribute('aria-label', labels.join(' – '));
                th.append(this.monthHeader(day));
                head.append(th);
            }
            return head;
        }

        renderEmployee(employee, days) {
            const row = document.createElement('tr');
            row.dataset.employeeUid = employee.uid;
            const name = this.node('th', employee.displayName);
            name.scope = 'row';
            row.append(name);
            for (const day of days) row.append(this.renderDay(employee, day));
            return row;
        }

        renderDay(employee, day) {
            const vacation = this.requestFor(employee.uid, day.date);
            const status = vacation?.status || '';
            const schoolHoliday = this.holidays.schoolHolidayName(day.date);
            const publicHoliday = this.holidays.publicHolidayName(day.date);
            const yearEndSpecial = this.yearEndSpecialName(day.date);
            const cell = document.createElement('td');
            cell.dataset.day = day.date;
            cell.className = ['flz-vacation-vac-cell', status && `flz-vacation-vac-${status}`, status && 'has-vacation', day.weekday === 6 && 'is-saturday', day.weekday === 7 && 'is-sunday', publicHoliday && 'is-public-holiday-column', yearEndSpecial && 'is-year-end-special'].filter(Boolean).join(' ');
            const title = [employee.displayName, this.dateShort(day.date), this.weekdayName(day), yearEndSpecial, schoolHoliday ? `Berliner Schulferien: ${schoolHoliday}` : '', publicHoliday ? `Feiertag: ${publicHoliday}` : '', status === 'approved' ? 'genehmigt' : status === 'planned' ? 'geplant' : ''].filter(Boolean).join(' – ');
            if (employee.canApprove) {
                const button = this.node('button', status === 'approved' ? 'U' : status === 'planned' ? 'U?' : '');
                button.type = 'button';
                button.dataset.action = 'set-status';
                button.dataset.status = status === '' ? 'planned' : status === 'planned' ? 'approved' : 'planned';
                button.title = title;
                button.setAttribute('aria-label', `${title} – Status ändern`);
                cell.append(button);
            } else {
                cell.title = title;
                if (status) cell.append(this.node('span', status === 'approved' ? 'U' : 'U?'));
            }
            return cell;
        }

        requestFor(uid, date) {
            return (this.state.plan?.requests || []).find(item => item.employeeUid === uid && item.startDate <= date && item.endDate >= date) || null;
        }

        selectedTeam() {
            return this.state.teams.find(team => team.id === this.state.teamId) || null;
        }

        monthHeader(day) {
            const names = ['', 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
            const fragment = document.createDocumentFragment();
            fragment.append(document.createTextNode(names[day.month] || String(day.month)), this.node('span', String(day.dayOfMonth)));
            return fragment;
        }

        dateShort(date) {
            const [year, month, day] = String(date).split('-');
            return year && month && day ? `${day}.${month}.` : String(date);
        }

        dateLong(date) {
            const [year, month, day] = String(date).split('-');
            return year && month && day ? `${day}.${month}.${year}` : String(date);
        }

        dateTime(value) {
            if (!value) return 'unbekannt';
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? 'unbekannt' : this.dateLong(String(value).slice(0, 10));
        }

        weekdayName(day) {
            return day.weekday === 6 ? 'Samstag' : day.weekday === 7 ? 'Sonntag' : '';
        }

        yearEndSpecialName(date) {
            const monthDay = String(date).slice(5);
            return monthDay === '12-24' ? 'Heiligabend' : monthDay === '12-31' ? 'Silvester' : '';
        }

        node(tag, text, className) {
            const result = document.createElement(tag);
            if (text !== undefined) result.textContent = text;
            if (className) result.className = className;
            return result;
        }
    }

    window.FlzUrlaub = window.FlzUrlaub || {};
    window.FlzUrlaub.components = window.FlzUrlaub.components || {};
    window.FlzUrlaub.components.VacationPlan = VacationPlan;
}());
