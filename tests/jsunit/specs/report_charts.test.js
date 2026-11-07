// Tests of aiprovider_birtai/report_charts: balance, totals and the three charts.
import {renderTemplate, flush} from '../helpers';

let Ajax;
let Chart;
let Notification;
let init;

const SERVICES = {services: [{id: 's1', name: 'Chat'}, {id: 's2', name: 'Images'}]};
const ALL = [
    {date: '2026-10-20 11:00:00', cant_tokens: 50, action: 'summarise_text'},
    {date: '2026-10-03 10:00:00', cant_tokens: 100, action: 'generate_text'},
    {date: '2026-11-02 09:00:00', cant_tokens: 30, action: 'generate_text'},
];
const CHAT_ONLY = [ALL[1]];

const byId = (id) => document.getElementById(id);
const consumptionCalls = () => Ajax.callsTo('aiprovider_birtai_get_all_consumption');
const chartOn = (canvasId) => Chart.instances.filter((chart) => chart.canvas === byId(canvasId) && !chart.destroyed);
const select = async(id, value) => {
    byId(id).value = value;
    byId(id).dispatchEvent(new Event('change'));
    await flush();
};

beforeEach(async() => {
    jest.resetModules();
    document.body.innerHTML = renderTemplate('aiprovider_birtai/report_page', {});
    Ajax = require('core/ajax');
    Chart = require('core/chartjs').default;
    Chart.instances = [];
    Notification = require('core/notification').default;
    ({init} = require('aiprovider_birtai/report_charts'));

    Ajax.setResponses({
        'aiprovider_birtai_get_credits_balance': {balance: 870},
        'aiprovider_birtai_get_services': SERVICES,
        'aiprovider_birtai_get_all_consumption': (args) => ({
            status: 'success',
            consumption: args.service === 's1' || args.fromdate ? CHAT_ONLY : ALL,
        }),
    });
    await init();
    await flush();
});

describe('consumption report', () => {
    it('shows the balance and the tokens consumed', () => {
        expect(byId('tokens-available').textContent).toBe('870');
        expect(byId('tokens-consumed').textContent).toBe('180');
    });

    it('offers every service in both service filters', () => {
        const values = (id) => Array.from(byId(id).options).map((option) => option.value);
        expect(values('filter-service-bar')).toEqual(['', 's1', 's2']);
        expect(values('filter-service-pie')).toEqual(['', 's1', 's2']);
    });

    it('draws the tokens by month, by action and by day', () => {
        const [bar] = chartOn('chart-tokens-by-month');
        expect(bar.config.type).toBe('bar');
        expect(bar.config.data.labels).toEqual(['2026-10', '2026-11']);
        expect(bar.config.data.datasets[0].data).toEqual([150, 30]);

        const [pie] = chartOn('chart-actions');
        expect(pie.config.type).toBe('pie');
        expect(pie.config.data.labels).toEqual(['summarise_text', 'generate_text']);
        expect(pie.config.data.datasets[0].data).toEqual([50, 130]);

        const [day] = chartOn('chart-tokens-by-day');
        expect(day.config.type).toBe('line');
        expect(day.config.data.labels).toEqual(['2026-10-03', '2026-10-20', '2026-11-02']);
        expect(day.config.data.datasets[0].data).toEqual([100, 50, 30]);
    });

    it('redraws the month chart for one service, then for all of them without asking again', async() => {
        await select('filter-service-bar', 's1');
        expect(consumptionCalls().slice(1)).toEqual([{service: 's1', action: '', fromdate: '', todate: ''}]);
        expect(chartOn('chart-tokens-by-month')).toHaveLength(1);
        expect(chartOn('chart-tokens-by-month')[0].config.data.datasets[0].data).toEqual([100]);

        await select('filter-service-bar', '');
        expect(consumptionCalls()).toHaveLength(2);
        expect(chartOn('chart-tokens-by-month')[0].config.data.datasets[0].data).toEqual([150, 30]);
    });

    it('redraws the action chart for one service', async() => {
        await select('filter-service-pie', 's1');

        expect(consumptionCalls().slice(1)).toEqual([{service: 's1', action: '', fromdate: '', todate: ''}]);
        expect(chartOn('chart-actions')[0].config.data.labels).toEqual(['generate_text']);
    });

    it('redraws the daily chart for a date range, and for everything once it is cleared', async() => {
        byId('filter-end-date').value = '2026-10-31';
        await select('filter-start-date', '2026-10-01');
        expect(consumptionCalls().slice(1)).toEqual([{service: '', action: '', fromdate: '2026-10-01', todate: '2026-10-31'}]);
        expect(chartOn('chart-tokens-by-day')[0].config.data.labels).toEqual(['2026-10-03']);

        byId('filter-end-date').value = '';
        await select('filter-start-date', '');
        expect(consumptionCalls()).toHaveLength(2);
        expect(chartOn('chart-tokens-by-day')[0].config.data.labels).toHaveLength(3);
    });

    it('reports a failed request and draws an empty chart', async() => {
        const failure = new Error('No connection');
        Ajax.setResponses({
            'aiprovider_birtai_get_all_consumption': () => {
                throw failure;
            },
        });

        await select('filter-service-bar', 's2');

        expect(Notification.exception).toHaveBeenCalledWith(failure);
        expect(chartOn('chart-tokens-by-month')[0].config.data.labels).toEqual([]);
    });
});
