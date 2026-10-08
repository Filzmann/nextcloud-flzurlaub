(function () {
    'use strict';

    /** Zweck: Kapselt die vom Server gelieferten, gecachten Berliner Ferien- und Feiertagsdaten für die Darstellung. */
    class HolidayCalendar {
        constructor(payload = {}) {
            this.replace(payload);
        }

        replace(payload = {}) {
            this.payload = {
                year: Number(payload.year) || null,
                fetchedAt: typeof payload.fetchedAt === 'string' ? payload.fetchedAt : null,
                cacheStatus: typeof payload.cacheStatus === 'string' ? payload.cacheStatus : 'unavailable',
                source: {
                    name: typeof payload.source?.name === 'string' ? payload.source.name : 'OpenHolidays API',
                    url: typeof payload.source?.url === 'string' ? payload.source.url : 'https://www.openholidaysapi.org/',
                    license: typeof payload.source?.license === 'string' ? payload.source.license : 'ODbL',
                },
                schoolHolidays: this.periods(payload.schoolHolidays),
                publicHolidays: this.periods(payload.publicHolidays),
            };
        }

        schoolHolidayName(date) {
            return this.name(this.payload.schoolHolidays, date);
        }

        publicHolidayName(date) {
            return this.name(this.payload.publicHolidays, date);
        }

        toArray() {
            return {
                ...this.payload,
                source: { ...this.payload.source },
                schoolHolidays: this.payload.schoolHolidays.map(period => ({ ...period })),
                publicHolidays: this.payload.publicHolidays.map(period => ({ ...period })),
            };
        }

        periods(value) {
            if (!Array.isArray(value)) return [];
            return value.filter(period => period
                && typeof period.name === 'string'
                && /^\d{4}-\d{2}-\d{2}$/.test(period.startDate)
                && /^\d{4}-\d{2}-\d{2}$/.test(period.endDate)
                && period.startDate <= period.endDate
            ).map(period => ({ name: period.name, startDate: period.startDate, endDate: period.endDate }));
        }

        name(periods, date) {
            const value = String(date);
            return periods.find(period => value >= period.startDate && value <= period.endDate)?.name || '';
        }
    }

    window.FlzUrlaub = window.FlzUrlaub || {};
    window.FlzUrlaub.models = window.FlzUrlaub.models || {};
    window.FlzUrlaub.models.HolidayCalendar = HolidayCalendar;
}());
