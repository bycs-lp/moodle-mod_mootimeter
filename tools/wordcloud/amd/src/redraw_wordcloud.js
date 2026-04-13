import { call as fetchMany } from 'core/ajax';
import WordCloud from 'mootimetertool_wordcloud/wordcloud2';
import {notifyFilterContentUpdated} from 'core_filters/events';

const observerRegistry = new Map();

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    // Initially getAnswers.
    getAnswersAsync(id);

    setTimeout(() => {
        const intervalms = document.getElementById('mootimeterstate').dataset.refreshinterval;
        const interval = setInterval(() => {
            if (!document.getElementById(id)) {
                clearInterval(interval);
                return;
            }
            getAnswers(id);
        }, intervalms);
    }, 2000);

    const mtmstate = document.getElementById('mootimeterstate');
    mtmstate.setAttribute('data-wclastupdated', 0);
};

/**
 * This is because the execution should be finished befor proceeding.
 * @param {string} id
 */
async function getAnswersAsync(id) {
    await getAnswers(id);
}

/**
 * Call to get all answers
 * @param {int} pageid
 * @param {int} lastupdated
 * @returns {array}
 */
const execGetAnswers = (
    pageid,
    lastupdated
) => fetchMany([{
    methodname: 'mootimetertool_wordcloud_get_answers',
    args: {
        pageid,
        lastupdated
    },
}])[0];

/**
 * Executes the call to get all answers.
 *
 * @param {string} id
 * @returns {mixed}
 */
const getAnswers = async (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    var pageid = document.getElementById(id).dataset.pageid;

    const mtmstate = document.getElementById('mootimeterstate');

    // Early exit if there are no changes.
    if (mtmstate.dataset.wclastupdated && mtmstate.dataset.wclastupdated == mtmstate.dataset.contentchangedat) {
        return;
    }

    // Get the answer list.
    const response = await execGetAnswers(pageid);

    // Set wclastupdated.
    mtmstate.setAttribute('data-wclastupdated', mtmstate.dataset.contentchangedat);

    // Redraw wordcloud.
    const container = document.getElementById(id);
    if (!container) {
        return;
    }
    container.setAttribute('data-answers', JSON.stringify(response.answerlist));
    ensureObserver(container);
    redrawwordcloud(container);

    return;
};

/**
 * Redraw the wordcloud.
 * @param {HTMLElement} container
 */
function redrawwordcloud(container) {
    let answers = JSON.parse(container.dataset.answers);

    // Parse count from string to number.
    const sortedAnswers = answers.map(item => [item[0], Number(item[1])]);

    // Order the array by answer count desc, so most frequent answers are drawn first.
    sortedAnswers.sort(compareByCount);

    // Calculate dynamic weightFactor based on container dimension and maxCount.
    const weightFactor = (Math.min(container.clientHeight, container.clientWidth) / 3) / sortedAnswers[0][1];

    // Render wordcloud with a function that calculates the fontSize per answer.
    // 20px minimum font size to ensure readability of infrequent answers.
    // shrinkToFit prevents answers from being silently dropped if they don't fit.
    WordCloud(container, {
        list: sortedAnswers,
        weightFactor: function(count) {
            return Math.max(weightFactor * count, 20);
        },
        color: '#f98012',
        fontFamily: 'OpenSans',
        shrinkToFit: true,
    });
}

/**
 * Comparator to sort wordcloud answer arrays by count descending.
 *
 * @param {Array} a
 * @param {Array} b
 * @returns {number}
 */
function compareByCount(a, b) {
    return b[1] - a[1];
}

/**
 * Ensure MathJax is triggered when the wordcloud mutates.
 *
 * @param {HTMLElement} container
 */
function ensureObserver(container) {
    if (!container || observerRegistry.has(container.id)) {
        return;
    }

    const observer = new MutationObserver((mutations) => {
        if (!document.body.contains(container)) {
            observer.disconnect();
            observerRegistry.delete(container.id);
            return;
        }
        for (const mutation of mutations) {
            if (mutation.addedNodes.length > 0) {
                for (const node of mutation.addedNodes) {
                    if (node.tagName === 'SPAN') {
                        node.classList.add('filter_mathjaxloader_equation');
                    }
                }
            }
        }
        notifyFilterContentUpdated([container]);
    });

    observer.observe(container, {childList: true});
    observerRegistry.set(container.id, observer);
}
