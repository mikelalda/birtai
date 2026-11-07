// Fake of core/chartjs: records each chart with its configuration.
export default class Chart {
    constructor(canvas, config) {
        this.canvas = canvas;
        this.config = config;
        this.destroyed = false;
        Chart.instances.push(this);
    }

    destroy() {
        this.destroyed = true;
    }
}
Chart.instances = [];
