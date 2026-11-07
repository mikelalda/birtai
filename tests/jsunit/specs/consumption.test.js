// Tests of aiprovider_birtai/consumption: the consumption history table.
import {renderTemplate, flush} from '../helpers';

let Ajax;
let Notification;
let Str;
let init;

const SERVICES = {services: [{id: 's1', name: 'Chat'}, {id: 's2', name: 'Images'}]};
const ACTIONS = {actions: [{id: 'generate_text', name: 'Generate text'}]};
const ROW = {
    id_consumption: 7, userid: 2, action: 'generate_text', id_service: 's1',
    cant_tokens: 120, balance: 880, date: '2026-11-01',
};

/**
 * A page of history as the web service returns it.
 *
 * @param {number} current
 * @param {number} totalPages
 * @returns {object}
 */
const historyPage = (current, totalPages) => ({
    consumption: [ROW],
    pagination: {current_page: current, total_pages: totalPages, total: 25},
});

const byId = (id) => document.getElementById(id);
const optionValues = (id) => Array.from(byId(id).options).map((option) => option.value);
const historyCalls = () => Ajax.callsTo('aiprovider_birtai_get_consumption_history');

/**
 * Start the page with the given answer for the history.
 *
 * @param {object|function} history
 * @param {object} others Other web service answers.
 */
const start = async(history, others = {}) => {
    Ajax.setResponses({
        'aiprovider_birtai_get_services': SERVICES,
        'aiprovider_birtai_get_actions': ACTIONS,
        'aiprovider_birtai_get_consumption_history': history,
        ...others,
    });
    await init();
    await flush();
};

beforeEach(() => {
    jest.resetModules();
    sessionStorage.clear();
    document.body.innerHTML = renderTemplate('aiprovider_birtai/consumption_page', {});
    Ajax = require('core/ajax');
    Notification = require('core/notification').default;
    Str = require('core/str');
    ({init} = require('aiprovider_birtai/consumption'));
});

describe('consumption history', () => {
    it('fills the filters and shows the first page', async() => {
        await start(historyPage(1, 3));

        expect(optionValues('filter-service')).toEqual(['', 's1', 's2']);
        expect(optionValues('filter-action')).toEqual(['', 'generate_text']);
        expect(historyCalls()).toEqual([{page: 1, limit: 10, service: '', action: '', fromdate: '', todate: ''}]);
        const cells = Array.from(document.querySelectorAll('#consumption-table-body td')).map((td) => td.textContent);
        expect(cells).toEqual(['7', '2', 'generate_text', 's1', '120', '880', '2026-11-01']);
    });

    it('shows the page the server returned and enables the buttons around it', async() => {
        await start(historyPage(2, 3));

        expect(Str.get_string).toHaveBeenCalledWith('pageinfo', 'aiprovider_birtai', {current: 2, totalpages: 3, total: 25});
        expect(byId('page-info').textContent).toBe('[pageinfo]');
        expect(byId('filter-page').value).toBe('2');
        expect(byId('prev-page').disabled).toBe(false);
        expect(byId('next-page').disabled).toBe(false);
    });

    it('disables previous on the first page and next on the last one', async() => {
        await start(historyPage(1, 1));

        expect(byId('prev-page').disabled).toBe(true);
        expect(byId('next-page').disabled).toBe(true);
    });

    it('disables both buttons and clears the page info without pagination', async() => {
        await start({consumption: []});

        expect(byId('page-info').textContent).toBe('');
        expect(byId('prev-page').disabled).toBe(true);
        expect(byId('next-page').disabled).toBe(true);
    });

    it('asks for the next page and remembers it for the next visit', async() => {
        await start((args) => historyPage(args.page, 3));

        byId('next-page').click();
        await flush();

        expect(historyCalls().map((args) => args.page)).toEqual([1, 2]);
        expect(sessionStorage.getItem('consumptionPage')).toBe('2');
        expect(byId('filter-page').value).toBe('2');
    });

    it('starts from the remembered page and limit', async() => {
        sessionStorage.setItem('consumptionPage', '3');
        sessionStorage.setItem('consumptionLimit', '25');

        await start(historyPage(3, 3));

        expect(historyCalls()[0]).toMatchObject({page: 3, limit: 25});
    });

    it('goes back to the first page when a filter changes', async() => {
        sessionStorage.setItem('consumptionPage', '2');
        await start((args) => historyPage(args.page, 3));

        byId('filter-service').value = 's2';
        byId('filter-service').dispatchEvent(new Event('change'));
        await flush();

        expect(historyCalls()[1]).toEqual({page: 1, limit: 10, service: 's2', action: '', fromdate: '', todate: ''});
    });

    it('sorts by a column, then reverses the order on a second click', async() => {
        await start(historyPage(1, 1));

        document.querySelector('[data-sort="fecha"]').click();
        await flush();
        document.querySelector('[data-sort="fecha"]').click();
        await flush();

        expect(historyCalls().slice(1).map((args) => [args.shor, args.shordir])).toEqual([['fecha', 'asc'], ['fecha', 'desc']]);
        expect(document.querySelector('[data-sort="fecha"] .sort-icon').className).toBe('fa fa-sort-down sort-icon');
    });

    it('reports a failed list of services and still loads the history', async() => {
        const failure = new Error('Service list unavailable');
        await start(historyPage(1, 1), {
            'aiprovider_birtai_get_services': () => {
                throw failure;
            },
        });

        expect(Notification.exception).toHaveBeenCalledWith(failure);
        expect(optionValues('filter-service')).toEqual(['']);
        expect(historyCalls()).toHaveLength(1);
    });
});
