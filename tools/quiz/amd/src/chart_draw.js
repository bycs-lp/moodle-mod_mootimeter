import ChartJS from 'core/chartjs';
import {call as fetchMany} from 'core/ajax';

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    const pageid = document.getElementById('mootimeterstate').dataset.pageid;

    getAnswersAsync(pageid, id);

    setTimeout(() => {
        const intervalms = document.getElementById('mootimeterstate').dataset.refreshinterval;
        const interval = setInterval(() => {
            if (!document.getElementById(id)) {
                clearInterval(interval);
                return;
            }
            getAnswers(pageid, id);
        }, intervalms);
    }, 2000);

    const mtmstate = document.getElementById('mootimeterstate');
    mtmstate.setAttribute('data-quizlastupdated', 0);

};

/**
 * Measure the width of the widest line of a (multi-line) label.
 *
 * @param {CanvasRenderingContext2D} ctx the canvas context
 * @param {object} font the font as returned by ChartJS.helpers.toFont
 * @param {string|string[]} label the label, arrays are rendered as multiple lines by Chart.js
 * @returns {number} the width in pixels
 */
const measureLabel = (ctx, font, label) => {
    ctx.save();
    ctx.font = font.string;
    const width = Math.max(0, ...[].concat(label).map((line) => ctx.measureText(String(line)).width));
    ctx.restore();
    return width;
};

/**
 * Wrap a label into lines of at most maxwidth pixels (Chart.js renders arrays as multiple lines).
 *
 * Words wider than maxwidth are kept on their own line.
 *
 * @param {CanvasRenderingContext2D} ctx the canvas context
 * @param {object} font the font as returned by ChartJS.helpers.toFont
 * @param {string|string[]} label the label
 * @param {number} maxwidth maximum width of a line in pixels
 * @returns {string|string[]} the label as single string if it fits into one line, otherwise the lines
 */
const wrapLabel = (ctx, font, label, maxwidth) => {
    const words = [].concat(label).join(' ').trim().split(/\s+/);
    const lines = [];
    let line = '';
    words.forEach((word) => {
        const candidate = line === '' ? word : line + ' ' + word;
        if (line !== '' && measureLabel(ctx, font, candidate) > maxwidth) {
            lines.push(line);
            line = word;
        } else {
            line = candidate;
        }
    });
    lines.push(line);
    return lines.length > 1 ? lines : lines[0];
};

/**
 * Number of lines of a label.
 *
 * @param {string|string[]} label the label
 * @returns {number}
 */
const countLines = (label) => (Array.isArray(label) ? label.length : 1);

/**
 * Wrap the tick labels of a category axis according to the space available for the axis.
 *
 * @param {object} scale the Chart.js scale
 */
const wrapScaleTicks = (scale) => {
    const font = ChartJS.helpers.toFont(scale.options.ticks.font);
    const padding = scale.options.ticks.padding + 6;
    const labels = scale.ticks.map((tick) => tick.label);
    const wrapAll = (maxwidth) => labels.map((label) => wrapLabel(scale.ctx, font, label, maxwidth));
    let wrapped;
    if (scale.isHorizontal()) {
        const tickwidth = scale.maxWidth / Math.max(labels.length, 1) - padding;
        const maxheight = scale.maxHeight - padding;
        // Prefer horizontal labels wrapped to the width of a tick.
        wrapped = wrapAll(tickwidth);
        const fits = wrapped.every((label) => measureLabel(scale.ctx, font, label) <= tickwidth
            && countLines(label) * font.lineHeight <= maxheight);
        if (!fits) {
            // Chart.js rotates the labels: allow longer lines, but only as many lines as fit next to each other.
            const maxlines = Math.max(1, Math.floor(tickwidth / font.lineHeight));
            const widest = Math.max(...labels.map((label) => measureLabel(scale.ctx, font, label)));
            let linewidth = Math.min(maxheight, widest / maxlines);
            wrapped = wrapAll(linewidth);
            // Word wrapping needs more lines than the ideal split, so widen the lines until they fit.
            while (linewidth < maxheight && Math.max(...wrapped.map(countLines)) > maxlines) {
                linewidth = Math.min(maxheight, linewidth * 1.1);
                wrapped = wrapAll(linewidth);
            }
        }
    } else {
        wrapped = wrapAll(scale.maxWidth - padding);
    }
    scale.ticks.forEach((tick, i) => {
        tick.label = wrapped[i];
    });
};

/**
 * Wrap the items of a horizontal legend according to the available width.
 *
 * Chart.js uses one fixed row height for horizontal legends, so the spacing between the rows is increased by the extra
 * lines.
 *
 * @param {object} legend the Chart.js legend
 */
const wrapLegendItems = (legend) => {
    const labels = legend.options.labels;
    if (legend.mtmbasepadding === undefined) {
        legend.mtmbasepadding = labels.padding;
    }
    labels.padding = legend.mtmbasepadding;
    if (!legend.options.display || !legend.isHorizontal()) {
        return;
    }
    const font = ChartJS.helpers.toFont(labels.font);
    const boxwidth = labels.boxWidth ?? font.size;
    const texts = legend.legendItems.map((item) => item.text);
    // A larger row spacing also indents the items, so wrap again until the spacing is stable.
    for (let i = 0; i < 3; i++) {
        const maxwidth = legend.maxWidth - 2 * labels.padding - boxwidth - font.size / 2;
        legend.legendItems.forEach((item, j) => {
            item.text = wrapLabel(legend.ctx, font, texts[j], maxwidth);
        });
        const maxlines = Math.max(1, ...legend.legendItems.map((item) => countLines(item.text)));
        const padding = legend.mtmbasepadding + (maxlines - 1) * font.lineHeight;
        if (padding === labels.padding) {
            break;
        }
        labels.padding = padding;
    }
};

/**
 * Fit a horizontal legend with multi-line items.
 *
 * Chart.js only reserves one line for the last row and measures multi-line items as if they were one line.
 *
 * @param {object} legend the Chart.js legend
 */
const fitMultiLineLegend = (legend) => {
    if (!legend.options.display || !legend.isHorizontal() || !legend.legendHitBoxes.length) {
        return;
    }
    const labels = legend.options.labels;
    const font = ChartJS.helpers.toFont(labels.font);
    const boxwidth = labels.boxWidth ?? font.size;
    // Use the real width of the widest line for aligning the rows.
    legend.lineWidths = [];
    legend.legendHitBoxes.forEach((hitbox, i) => {
        const width = boxwidth + font.size / 2 + measureLabel(legend.ctx, font, legend.legendItems[i].text);
        legend.lineWidths[hitbox.row] = (legend.lineWidths[hitbox.row] || 0) + width + labels.padding;
    });
    // Reserve the extra lines of the last row, which would be clipped otherwise.
    const lastrow = legend.legendHitBoxes[legend.legendHitBoxes.length - 1].row;
    const maxlines = Math.max(...legend.legendItems
        .filter((item, i) => legend.legendHitBoxes[i].row === lastrow)
        .map((item) => countLines(item.text)));
    legend.height = Math.min(legend.height + (maxlines - 1) * font.lineHeight, legend.options.maxHeight || legend.maxHeight);
};

/**
 * Chart.js plugin wrapping long answer labels of all chart types according to the available space.
 */
const wrapLabelsPlugin = {
    id: 'mootimetertool_quiz_wraplabels',
    afterInit: (chart) => {
        const legend = chart.legend;
        if (legend) {
            // Chart.js rebuilds the legend items after the layout as well, so wrap them whenever they are built.
            const buildLabels = legend.buildLabels;
            legend.buildLabels = function() {
                buildLabels.call(this);
                wrapLegendItems(this);
            };
            const fit = legend.fit;
            legend.fit = function() {
                fit.call(this);
                fitMultiLineLegend(this);
            };
        }
    },
    beforeLayout: (chart) => {
        Object.values(chart.scales).forEach((scale) => {
            if (scale.type !== 'category' || scale.mtmwraplabels) {
                return;
            }
            const afterTickToLabelConversion = scale.afterTickToLabelConversion;
            scale.afterTickToLabelConversion = function() {
                afterTickToLabelConversion.call(this);
                wrapScaleTicks(this);
            };
            scale.mtmwraplabels = true;
        });
    },
};

/**
 * This is because the execution should be finished befor proceeding.
 * @param {int} pageid
 * @param {string} id
 */
async function getAnswersAsync(pageid, id) {
    await getAnswers(pageid, id);
}

/**
 * Execute the ajax call to get the aswers and more important data.
 * @param {int} pageid
 * @returns {mixed}
 */
const execGetAnswers = (
    pageid,
) => fetchMany([{
    methodname: 'mootimetertool_quiz_get_answers',
    args: {
        pageid,
    },
}])[0];

/**
 * Get the answers and other important data, as well as processing them.
 * @param {int} pageid
 * @param {string} id
 * @returns {mixed}
 */
const getAnswers = async (pageid, id) => {

    const mtmstate = document.getElementById('mootimeterstate');

    // Early exit if there are no changes.
    if (mtmstate.dataset.contentlastupdated == mtmstate.dataset.contentchangedat) {
        return;
    }

    const response = await execGetAnswers(pageid);

    if (!document.getElementById(id)) {
        window.console.log("Canvas not found");
        return;
    }

    if (mtmstate.dataset.quizlastupdated && mtmstate.dataset.quizlastupdated == mtmstate.dataset.contentchangedat) {
        return;
    }

    // Write the new data to the canvas data attributes.

    let nodecanvas = document.getElementById(id);
    nodecanvas.setAttribute('data-labels', response.labels);
    nodecanvas.setAttribute('data-values', response.values);
    nodecanvas.setAttribute('data-chartsettings', response.chartsettings);

    // Parse settings once to avoid repeated JSON.parse calls.
    const chartsettings = JSON.parse(response.chartsettings);
    const labels = JSON.parse(response.labels);
    const values = JSON.parse(response.values);

    // Get or create chart.
    let chartStatus = ChartJS.getChart(id);

    if (chartStatus != undefined) {
        // Update existing chart instead of destroying and recreating.
        chartStatus.data.labels = labels;
        chartStatus.data.datasets[0].label = response.question;
        chartStatus.data.datasets[0].data = values;
        chartStatus.data.datasets[0].backgroundColor = chartsettings.backgroundColor;
        chartStatus.data.datasets[0].borderRadius = chartsettings.borderRadius;
        chartStatus.data.datasets[0].pointStyle = chartsettings.pointStyle;
        chartStatus.data.datasets[0].pointRadius = chartsettings.pointRadius;
        chartStatus.data.datasets[0].pointHoverRadius = chartsettings.pointHoverRadius;

        // Check if chart type changed (rare case).
        if (chartStatus.config.type !== chartsettings.charttype) {
            chartStatus.destroy();
            chartStatus = null; // Will be recreated below.
        } else {
            // Update without animation for instant response.
            chartStatus.update('none');
        }
    }

    if (!chartStatus) {
        // Create new chart only if it doesn't exist.
        var config = {
            type: chartsettings.charttype,
            data: {
                labels: labels,
                datasets: [{
                    label: response.question,
                    data: values,
                    backgroundColor: chartsettings.backgroundColor,
                    borderRadius: chartsettings.borderRadius,
                    pointStyle: chartsettings.pointStyle,
                    pointRadius: chartsettings.pointRadius,
                    pointHoverRadius: chartsettings.pointHoverRadius,
                }],
            },
            options: chartsettings.options,
            plugins: [wrapLabelsPlugin],
        };

        new ChartJS(document.getElementById(id), config);
        ChartJS.defaults.font.size = 25;
        ChartJS.defaults.stepSize = 1;
    }

    // Set quizlastupdated.
    mtmstate.setAttribute('data-quizlastupdated', mtmstate.dataset.contentchangedat);
};
