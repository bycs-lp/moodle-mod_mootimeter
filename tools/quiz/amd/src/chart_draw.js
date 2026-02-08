import ChartJS from 'mootimetertool_quiz/chart.umd';
import {call as fetchMany} from 'core/ajax';

/**
 * MBS-10584:Wrap a long text label into multiple lines by splitting at word boundaries.
 * @param {string} text
 * @param {number} maxWidth
 * @returns {string|string[]}
 */
function wrapLabel(text, maxWidth) {
    const words = text.split(' ');
    const lines = [];
    let currentLine = '';
    for (const word of words) {
        if ((currentLine + ' ' + word).trim().length > maxWidth) {
            lines.push(currentLine.trim());
            currentLine = word;
        } else {
            currentLine = (currentLine + ' ' + word).trim();
        }
    }
    if (currentLine) {
        lines.push(currentLine.trim());
    }
    return lines.length > 1 ? lines : text;
}

/**
 * MBS-10584: Truncate a label to a maximum length, appending an ellipsis if needed.
 * @param {string} text
 * @param {number} maxLength
 * @returns {string}
 */
function truncateLabel(text, maxLength) {
    if (text.length <= maxLength) {
        return text;
    }
    return text.substring(0, maxLength - 1) + '…';
}

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

    // (Re-)Draw the chart.
    var config = {
        type: JSON.parse(response.chartsettings).charttype,
        data: {
            labels: JSON.parse(response.labels),
            datasets: [{
                label: response.question,
                data: JSON.parse(response.values),
                backgroundColor: JSON.parse(response.chartsettings).backgroundColor,
                borderRadius: JSON.parse(response.chartsettings).borderRadius,
                pointStyle: JSON.parse(response.chartsettings).pointStyle,
                pointRadius: JSON.parse(response.chartsettings).pointRadius,
                pointHoverRadius: JSON.parse(response.chartsettings).pointHoverRadius,
            }],
        },
        options: JSON.parse(response.chartsettings).options
    };

    // MBS-10584: Wrap long Y-axis labels for horizontal bar charts to prevent text cutoff.
    if (config.options?.indexAxis === 'y') {
        config.data.labels = config.data.labels.map(label => {
            const wrapped = wrapLabel(label, 50);
            if (Array.isArray(wrapped) && wrapped.length > 3) {
                const truncated = wrapped.slice(0, 3);
                truncated[2] = truncated[2] + '…';
                return truncated;
            }
            return wrapped;
        });
    }

    // Truncate legend labels for pie charts.
    if (config.type === 'pie') {
        config.data.labels = config.data.labels.map(label => truncateLabel(label, 50));
    }

    // Wrap title text for all charts with a title plugin.
    if (config.options?.plugins?.title?.text) {
        config.options.plugins.title.text = wrapLabel(config.options.plugins.title.text, 60);
    }

    let chartStatus = ChartJS.getChart(id); // <canvas> id
    if (chartStatus != undefined) {
        chartStatus.destroy();
    }

    ChartJS.defaults.font.size = 16;
    ChartJS.defaults.stepSize = 1;

    new ChartJS(document.getElementById(id), config);

    // Set quizlastupdated.
    mtmstate.setAttribute('data-quizlastupdated', mtmstate.dataset.contentchangedat);
};
