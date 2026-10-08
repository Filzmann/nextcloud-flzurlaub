(function () {
    'use strict';

    /**
     * Zweck: Orchestriert Urlaubs-API, Seitenzustand und Benutzerinteraktionen.
     * Zusammenspiel: main.js startet die App; VacationPlan übernimmt ausschließlich die Darstellung.
     */
    class VacationApp {
        constructor({ client, notice, location = window.location }) {
            this.client = client;
            this.notice = notice;
            this.location = location;
            const ids = ['team', 'year', 'calendar-head', 'calendar-body', 'own-form', 'own-requests', 'conflicts', 'calendar-view', 'plan-title', 'integration-status', 'holiday-status'];
            this.elements = Object.fromEntries(ids.map(id => [id, document.getElementById(`flz-vacation-${id}`)]));
            this.state = { teams: [], teamId: '', year: new Date().getFullYear(), currentUser: null, plan: null, integrations: {} };
            this.plan = new window.FlzUrlaub.components.VacationPlan({ elements: this.elements, state: this.state });
            this.bindEvents();
        }

        start() {
            return this.init();
        }

        async init() {
            try {
                const payload = await this.client.request('/api/teams');
                this.state.teams = payload.teams || [];
                this.state.currentUser = payload.currentUser || null;
                this.state.integrations = payload.integrations || {};
                this.renderIntegrationStatus();
                const params = new URLSearchParams(this.location.search);
                const requested = params.get('team');
                this.state.teamId = this.state.teams.some(team => team.id === requested)
                    ? requested
                    : (this.state.teams.find(team => team.employees?.some(employee => employee.uid === this.state.currentUser?.uid))?.id || this.state.teams[0]?.id || '');
                const year = Number(params.get('year'));
                if (year >= 2000 && year <= 2100) this.state.year = year;
                this.plan.renderChrome();
                await this.loadYear();
            } catch (error) {
                this.notice.error(error);
            }
        }

        renderIntegrationStatus() {
            const status = this.elements['integration-status'];
            const automaticCheck = this.state.integrations.calendarConflictCheck?.available === true;
            status.hidden = automaticCheck;
            status.textContent = automaticCheck
                ? ''
                : 'Filzmann Kalender ist nicht aktiv. Dienstkonflikte werden nicht automatisch geprüft.';
        }

        async loadYear() {
            if (!this.state.teamId) return;
            try {
                this.state.plan = await this.client.request(`/api/teams/${this.client.encode(this.state.teamId)}/years/${this.client.encode(this.state.year)}`);
                this.plan.renderChrome();
                this.plan.renderPlan();
                this.notice.clear();
            } catch (error) {
                this.notice.error(error);
            }
        }

        bindEvents() {
            this.elements.team.addEventListener('change', event => this.changeTeam(event));
            this.elements.year.addEventListener('change', event => this.changeYear(event));
            this.elements['calendar-body'].addEventListener('click', event => this.changeStatus(event));
            this.elements['own-form'].addEventListener('submit', event => this.createOwn(event));
            this.elements['own-requests'].addEventListener('click', event => this.deleteOwn(event));
        }

        async changeTeam(event) {
            this.state.teamId = event.target.value;
            await this.loadYear();
        }

        async changeYear(event) {
            const year = Number(event.target.value);
            if (year < 2000 || year > 2100) return;
            this.state.year = year;
            await this.loadYear();
        }

        async changeStatus(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action="set-status"]') : null;
            if (!button) return;
            const employee = this.state.plan.assistants.find(item => item.uid === button.closest('tr')?.dataset.employeeUid);
            if (!employee?.canApprove) return;
            this.plan.clearConflicts();
            try {
                await this.client.request(`/api/teams/${this.client.encode(this.state.teamId)}/years/${this.client.encode(this.state.year)}/status`, {
                    method: 'POST',
                    body: JSON.stringify({ employeeUid: employee.uid, date: button.closest('td').dataset.day, status: button.dataset.status }),
                });
                await this.loadYear();
                this.notice.success('Urlaubsstatus gespeichert.');
            } catch (error) {
                this.plan.showConflicts(error);
                this.notice.error(error);
            }
        }

        async createOwn(event) {
            event.preventDefault();
            const formElement = event.currentTarget;
            const form = new FormData(formElement);
            try {
                await this.client.request('/api/vacations', {
                    method: 'POST',
                    body: JSON.stringify({
                        employeeUid: this.state.currentUser.uid,
                        startDate: form.get('startDate'),
                        endDate: form.get('endDate'),
                        status: 'planned',
                        note: form.get('note'),
                    }),
                });
                formElement.reset();
                await this.loadYear();
                this.notice.success('Urlaub eingetragen.');
            } catch (error) {
                this.notice.error(error);
            }
        }

        async deleteOwn(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action="delete-own"]') : null;
            if (!button) return;
            try {
                await this.client.request(`/api/vacations/${this.client.encode(button.dataset.id)}`, { method: 'DELETE', body: '{}' });
                await this.loadYear();
                this.notice.success('Urlaub gelöscht.');
            } catch (error) {
                this.notice.error(error);
            }
        }
    }

    window.FlzUrlaub = window.FlzUrlaub || {};
    window.FlzUrlaub.modules = window.FlzUrlaub.modules || {};
    window.FlzUrlaub.modules.VacationApp = VacationApp;
}());
