import {call as fetchMany} from 'core/ajax';
import cloud from 'mootimetertool_wordcloud/d3_cloud';
import {getString} from 'core/str';
import {notifyFilterContentUpdated} from 'core_filters/events';

const observerRegistry = new Map();

// Tracks the active render per container.
// Needed because d3-cloud layout is async, so a newer redraw can start before an older one finishes.
// The token ensures only the latest render calls renderSvg.
const activeRenders = new Map();

const MIN_FONT_SIZE = 16;
const SVG_NS = 'http://www.w3.org/2000/svg';
const RATIO = 4;
const FILL_TARGET = 0.65;
const COLORS = [
    '#c0580a',
    '#1a5fa8',
    '#b33000',
    '#2e7d32',
    '#6b3fa0',
    '#c2185b',
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
    const answers = JSON.parse(container.dataset.answers);

    const w = container.clientWidth;
    const h = container.clientHeight;

    // Parse count from string to number.
    const sortedAnswers = answers.map(item => [item[0], Number(item[1])]);

    // Order the array by answer count desc, so most frequent answers are drawn first.
    sortedAnswers.sort(compareByCount);

    // Find the character length of the longest answer text.
    const maxWordLen = Math.max(...sortedAnswers.map(([text]) => text.length));
    // Cap font size so words fit the container: use the smaller of an area-based and a width-based limit.
    const maxFontSize = Math.min(
        Math.min(w, h) / Math.max(2, Math.ceil(Math.sqrt(sortedAnswers.length))),
        w / (maxWordLen * 0.65)
    );

     /**
      * Renders the wordcloud as an SVG element inside the container.
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
         svg.setAttribute('role', 'img');
         svg.setAttribute('aria-describedby', `wordlist_${container.id}`);
         getString('wordcloud_aria_label', 'mootimetertool_wordcloud', words.length)
             .then(label => svg.setAttribute('aria-label', label))
             .catch(() => svg.setAttribute('aria-label', `Word cloud with ${words.length} terms`));

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

         // Accessibility
         const srOnly = document.createElement('ul');
         srOnly.setAttribute('id', `wordlist_${container.id}`);
         srOnly.setAttribute('class', 'sr-only');

         words.forEach(word => {
             const title = document.createElement('li');
             title.textContent = `${word.text}: ${word.count}`;
             srOnly.appendChild(title);
         });
         container.appendChild(srOnly);
    }

    if (sortedAnswers.length === 0) {
        renderSvg([]);
        return;
    }

    const maxCount = sortedAnswers[0][1];
    const minCount = sortedAnswers[sortedAnswers.length - 1][1];
    const countSpread = maxCount - minCount;

    const words = sortedAnswers.map(([text, count]) => {
        // How frequent is this word relative to the others? 0 = least, 1 = most.
        const t = countSpread > 0 ? (count - minCount) / countSpread : 0;
        // Size factor for this word. sqrt gives mid-range words more visual weight.
        const multiplier = 1 + (RATIO - 1) * Math.sqrt(t);
        return {text, count, multiplier};
    });

    // Calculate how much space all words would need at a given base size.
    const S = words.reduce((sum, wd) => sum + wd.text.length * 0.6 * wd.multiplier * wd.multiplier, 0);
    // Pick the largest base size that keeps total word area within the container budget.
    const baseFontSize = Math.max(MIN_FONT_SIZE, Math.sqrt(FILL_TARGET * w * h / S));

    // Stop any previous layout for this container.
    const prev = activeRenders.get(container.id);
    if (prev) {
        prev.layout.stop();
    }

    // Assign a unique render ID for this call.
    const myRenderId = (prev?.renderId ?? 0) + 1;

    // Create layout
    const layout = cloud()
        .size([w, h])
        .words(words)
        .padding(4)
        .rotate(() => (Math.random() > 0.8 ? -90 : 0))
        .font('Lexend')
        .fontSize(d => Math.min(maxFontSize, baseFontSize * d.multiplier))
        .on('end', placed => {
            // D3-cloud calls step() synchronously on start(), so the end event can fire before layout.start() returns.
            if (activeRenders.get(container.id)?.renderId === myRenderId) {
                renderSvg(placed);
            }
        });

    // Register token before start().
    activeRenders.set(container.id, {renderId: myRenderId, layout});
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

    const observer = new MutationObserver(() => {
        if (!document.body.contains(container)) {
            observer.disconnect();
            observerRegistry.delete(container.id);
            return;
        }
        notifyFilterContentUpdated([container]);
    });

    observer.observe(container, {childList: true});
    observerRegistry.set(container.id, observer);
}
