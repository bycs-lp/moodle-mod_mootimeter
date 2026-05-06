import {call as fetchMany} from 'core/ajax';
import cloud from 'mootimetertool_wordcloud/d3_cloud';
import {notifyFilterContentUpdated} from 'core_filters/events';

const observerRegistry = new Map();

const MIN_FONT_PX = 16;
const SVG_NS = 'http://www.w3.org/2000/svg';

const COLORS = [
    '#c0580a', // BYCS Orange (dunkel, ausreichend Kontrast)
    '#1a5fa8', // BYCS Blau
    '#b33000', // Tiefes Rot-Orange
    '#2e7d32', // Dunkelgrün
    '#6b3fa0', // Violett
    '#c2185b', // Tief-Pink
];

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
const getAnswers = async(id) => {

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

    const w = container.clientWidth;
    const h = container.clientHeight;

    // Parse count from string to number.
    const sortedAnswers = answers.map(item => [item[0], Number(item[1])]);

    // Order the array by answer count desc, so most frequent answers are drawn first.
    sortedAnswers.sort(compareByCount);

    // Scale largest word inversely with sqrt of total word count.
    const maxFontSize = Math.min(w, h) / Math.max(4, Math.ceil(Math.sqrt(sortedAnswers.length)));
    const weightFactor = maxFontSize / sortedAnswers[0][1];

     /**
      *
      * @param {array} words
      */
    function renderSvg(words) {
         const map = container.querySelector('svg');
         const oldsr = container.querySelector('ul');
         if (map) {
             container.removeChild(map);
         }
         if (oldsr) {
             container.removeChild(oldsr);
         }
         if (!words || words.length === 0) {
             return;
         }

         const svg = document.createElementNS(SVG_NS, 'svg');
         svg.setAttribute('width', '100%');
         svg.setAttribute('viewBox', `${-w / 2} ${-h / 2} ${w} ${h}`);
         svg.setAttribute('aria-label', `Wortwolke mit ${words.length} Begriffen`);
         svg.setAttribute('role', 'img');
         svg.setAttribute('aria-describedby', `Wortliste_${container.id}`);

         const g = document.createElementNS(SVG_NS, 'g');
         svg.appendChild(g);
         words.forEach((word, index) => {

             const title = svgEl('title', {});
             title.textContent = `${word.text}: ${word.count}`;

             const text = svgEl('text', {
                 transform: `translate(${word.x}, ${word.y})rotate(${word.rotate})`,
                 'font-family': 'Lexend',
                 'font-size': `${word.size}px`,
                 'text-anchor': 'middle',
                 'fill': COLORS[index % COLORS.length]
             });
             text.replaceChildren(title, document.createTextNode(word.text));
             g.appendChild(text);
         });
         container.appendChild(svg);

         // Accessability
         const srOnly = document.createElement('ul');
         srOnly.setAttribute('id', `Wortliste_${container.id}`);
         srOnly.setAttribute('class', 'sr-only');

         words.forEach(word => {
             const title = document.createElement('li');
             title.textContent = `${word.text}: ${word.count}`;
             srOnly.appendChild(title);
         });
         container.appendChild(srOnly);
    }

    const layout = cloud()
        .size([w, h])
        .words(sortedAnswers.map(([text, count]) => ({text, count})))
        .padding(4)
        .rotate(() => (Math.random() > 0.2 ? 0 : -90))
        .font('Lexend')
        .fontSize(d => Math.min(Math.max(weightFactor * d.count, MIN_FONT_PX), maxFontSize));
    layout.on('end', renderSvg);
    layout.start();

}

/**
 * Helper function to create svg elements
 *
 * @param {String} tag
 * @param {Object} attrs
 */
function svgEl(tag, attrs) {
    const element = document.createElementNS(SVG_NS, tag);
    Object.entries(attrs).forEach(([key, value]) =>
        element.setAttribute(key, value));
    return element;
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
