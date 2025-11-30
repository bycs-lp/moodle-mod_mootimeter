import ChartJS from 'mootimetertool_quiz/chart.umd';
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
            options: chartsettings.options
        };

        new ChartJS(document.getElementById(id), config);
        ChartJS.defaults.font.size = 25;
        ChartJS.defaults.stepSize = 1;
    }

    // Set quizlastupdated.
    mtmstate.setAttribute('data-quizlastupdated', mtmstate.dataset.contentchangedat);
};
