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
const FREQUENCY_EXPONENT = 0.8;
const CHAR_WIDTH = 0.6;
const FIT_CHAR_WIDTH = 0.65;
const DENSITY_DIVISOR = 3;
const MAX_SHRINK_ATTEMPTS = 3;
const SHRINK_STEP = 0.85;
const COLORS = [
    '#c0580a',
    '#1a5fa8',
    '#b33000',
    '#2e7d32',
    '#6b3fa0',
    '#c2185b',
];

/**
 * Helper function to create svg elements
 *
 * @param {String} tag
 * @param {Object} attrs
 */
const createSvgElement = (tag, attrs) => {
    const element = document.createElementNS(SVG_NS, tag);
    Object.entries(attrs).forEach(([key, value]) =>
        element.setAttribute(key, value));
    return element;
};

/**
 * Comparator to sort wordcloud answer arrays by count descending.
 *
 * @param {Array} a
 * @param {Array} b
 * @returns {number}
 */
const compareByCount = (a, b) => {
    return b[1] - a[1];
};

/**
 * Ensure MathJax is triggered when the wordcloud mutates.
 *
 * @param {HTMLElement} container
 */
const ensureObserver = (container) => {
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
};

/**
 * Redraw the wordcloud.
 * @param {HTMLElement} container
*/
const redrawwordcloud = async(container) => {
    const answers = JSON.parse(container.dataset.answers);

    const contWidth = container.clientWidth;
    const contHeight = container.clientHeight;

    // Parse count from string to number.
    const sortedAnswers = answers.map(item => [item[0], Number(item[1])]);

    // Order the array by answer count desc, so most frequent answers are drawn first.
    sortedAnswers.sort(compareByCount);

    // Ceiling so one word cannot fill the whole canvas; binds mainly when there are few words
    // (prevent baseFontSize from exploding). For many words baseFontSize and the per-word
    // length cap in fontSize() govern, so the most frequent word is no longer artificially shrunk.
    const densityCap = Math.min(contWidth, contHeight) / DENSITY_DIVISOR;

    const fontFamily = 'Lexend, sans-serif';

    /**
     * Renders the wordcloud as an SVG element inside the container.
     *
     * @param {array} words
     */
    const renderSvg = async(words) => {
        // Remove previous render (SVG wordcloud + sr-only accessibility list) before drawing the new one.
        const previousSvg = container.querySelector('svg');
        const previousList = container.querySelector('ul');
        if (previousSvg) {
            container.removeChild(previousSvg);
        }
        if (previousList) {
            container.removeChild(previousList);
        }
        if (!words || words.length === 0) {
            return;
        }

        const svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('width', '100%');
        svg.setAttribute('viewBox', `${-contWidth / 2} ${-contHeight / 2} ${contWidth} ${contHeight}`);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-describedby', `wordlist_${container.id}`);
        const ariaLabel = await getString('wordcloud_aria_label', 'mootimetertool_wordcloud', words.length);
        svg.setAttribute('aria-label', ariaLabel);

        const g = document.createElementNS(SVG_NS, 'g');
        svg.appendChild(g);
        words.forEach((word, index) => {

            const title = createSvgElement('title', {});
            title.textContent = `${word.text}: ${word.count}`;

            const text = createSvgElement('text', {
                transform: `translate(${word.x}, ${word.y})rotate(${word.rotate})`,
                'font-family': fontFamily,
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
    };

    if (sortedAnswers.length === 0) {
        renderSvg([]);
        return;
    }

    // D3-cloud measures text in the real font; wait for it before layout.
    if (document.fonts?.ready) {
        await document.fonts.ready;
    }

    const maxCount = sortedAnswers[0][1];
    const minCount = sortedAnswers[sortedAnswers.length - 1][1];
    const countSpread = maxCount - minCount;

    const words = sortedAnswers.map(([text, count]) => {
        // How frequent is this word relative to the others? 0 = least, 1 = most.
        const normalizedFrequency = countSpread > 0 ? (count - minCount) / countSpread : 0;
        // Size factor for this word. The exponent shapes the curve: < 1 = fuller mid-range, 1 = linear.
        const multiplier = 1 + (RATIO - 1) * Math.pow(normalizedFrequency, FREQUENCY_EXPONENT);
        // Set rotation so the fontSize cap below knows the word's orientation.
        const rotate = Math.random() > 0.8 ? -90 : 0;
        return {text, count, multiplier, rotate};
    });

    // Calculate how much space all words would need at a given base size.
    const estimatedSpace = words.reduce((sum, word) => sum + word.text.length * CHAR_WIDTH * word.multiplier * word.multiplier, 0);
    // Pick the largest base size that keeps total word area within the container budget.
    const baseFontSize = Math.max(MIN_FONT_SIZE, Math.sqrt(FILL_TARGET * contWidth * contHeight / estimatedSpace));

    // Stop any previous layout for this container.
    const prev = activeRenders.get(container.id);
    if (prev) {
        prev.layout.stop();
    }

    // Assign a unique render ID for this call.
    const myRenderId = (prev?.renderId ?? 0) + 1;

    // Retry/shrink state for this render.
    let shrinkFactor = 1;
    let attempt = 0;

    // Build a fresh d3-cloud layout. A factory because each shrink round needs its own layout.
    const buildLayout = () => cloud()
        .size([contWidth, contHeight])
        .words(words)
        .padding(4)
        .rotate(d => d.rotate)
        .font(fontFamily)
        .fontSize(d => {
            // A word spans `contWidth` horizontally, but `contHeight` when rotated 90°. Cap so it never overflows its axis.
            const spanAxis = d.rotate === 0 ? contWidth : contHeight;
            // Largest size at which the full word still fits along the given axis. Shrink long words instead of dropping them.
            const lengthCap = spanAxis / (d.text.length * FIT_CHAR_WIDTH);
            // Take the smallest of three values: the size we want (baseFontSize x frequency multiplier)
            // and two upper limits. densityCap stops a single word from filling the canvas, lengthCap stops a long
            // word from overflowing its edge. shrinkFactor scales everything down on a retry round.
            return Math.min(baseFontSize * d.multiplier, densityCap, lengthCap) * shrinkFactor;
        })
        .on('end', placed => {
            // D3-cloud calls step() synchronously on start(), so the end event can fire before layout.start() returns.
            const active = activeRenders.get(container.id);
            // Ignore callbacks from a superseded render.
            if (active?.renderId !== myRenderId) {
                return;
            }
            // D3-cloud drops unplaceable words silently. Shrink everything and re-layout
            // until all words fit or the attempt budget is exhausted.
            if (placed.length < words.length && attempt < MAX_SHRINK_ATTEMPTS) {
                attempt++;
                shrinkFactor *= SHRINK_STEP;
                active.layout.stop();
                const retry = buildLayout();
                activeRenders.set(container.id, {renderId: myRenderId, layout: retry});
                retry.start();
                return;
            }
            renderSvg(placed);
        });

    // Register token before start().
    const layout = buildLayout();
    activeRenders.set(container.id, {renderId: myRenderId, layout});
    layout.start();
};

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
    await redrawwordcloud(container);

    return;
};

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    // Initially getAnswers.
    getAnswers(id);

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
