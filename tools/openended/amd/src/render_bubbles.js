// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Polls the bubble grid and applies incremental updates when the server
 * reports a change. Existing bubble nodes are patched in place rather than
 * thrown away, so in-flight animations (picker reveal, emoji rain) survive
 * the refresh cycle.
 *
 * On every poll we diff each bubble's reaction counts against the previously
 * seen counts and let emoji_rain spawn particles for positive deltas. The
 * very first render only baselines the counts so the user doesn't see
 * historical reactions raining on page load.
 *
 * @module     mootimetertool_openended/render_bubbles
 * @copyright  2026, ISB Bayern
 * @author     Benedikt Blumenfelder
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {call as fetchMany} from 'core/ajax';
import Templates from 'core/templates';
import {exception as displayException} from 'core/notification';
import {init as initToggleReaction} from 'mootimetertool_openended/toggle_reaction';
import {spawn as spawnRain} from 'mootimetertool_openended/emoji_rain';

const INITIAL_DELAY_MS = 1500;

// Per-grid state: { lastSeen: Map<answerId, Map<emojiKey, count>>, baselined: bool }.
const gridState = new WeakMap();

export const init = (wrapperid, gridid, emptyid) => {

    const wrapper = document.getElementById(wrapperid);
    const grid = document.getElementById(gridid);
    if (!wrapper || !grid) {
        return;
    }

    // Seed the per-grid delta tracker. baselined=false means: skip rain on
    // the very first refresh, just record the current counts.
    if (!gridState.has(grid)) {
        gridState.set(grid, {lastSeen: new Map(), baselined: false});
        // Record the counts of bubbles that the server already rendered.
        baselineExistingBubbles(grid);
    }

    initToggleReaction(grid);

    setTimeout(() => {
        const state = document.getElementById('mootimeterstate');
        const intervalms = (state && state.dataset.refreshinterval) || 1000;
        const interval = setInterval(() => {
            if (!document.getElementById(wrapperid)) {
                clearInterval(interval);
                return;
            }
            refresh(wrapperid, gridid, emptyid);
        }, intervalms);
    }, INITIAL_DELAY_MS);
};

/**
 * Pull the count out of every reaction button the server sent us so the very
 * first poll has something to diff against.
 *
 * @param {HTMLElement} grid
 */
function baselineExistingBubbles(grid) {
    const state = gridState.get(grid);
    grid.querySelectorAll('.mootimeter-oe-bubble').forEach((bubble) => {
        const id = bubble.dataset.answerid;
        if (!id) {
            return;
        }
        const counts = new Map();
        bubble.querySelectorAll('.mootimeter-oe-reaction[data-emoji]').forEach((btn) => {
            const emoji = btn.dataset.emoji;
            const countEl = btn.querySelector('.oe-count');
            const n = countEl ? parseInt(countEl.textContent, 10) || 0 : 0;
            counts.set(emoji, n);
        });
        state.lastSeen.set(id, counts);
    });
    state.baselined = true;
}

const execGetAnswers = (pageid) => fetchMany([{
    methodname: 'mootimetertool_openended_get_answers',
    args: {pageid},
}])[0];

const execGetMootimeterState = (pageid, cmid, dataset) => fetchMany([{
    methodname: 'mod_mootimeter_get_mootimeterstate',
    args: {
        pageid,
        cmid,
        dataset,
    },
}])[0];

const updateMootimeterState = async(wrapper) => {
    const mtmstate = document.getElementById('mootimeterstate');
    if (!mtmstate) {
        return null;
    }

    const dataset = mtmstate.dataset;
    const urlParams = new URLSearchParams(window.location.search);
    const resultview = urlParams.get('r');
    const overview = urlParams.get('o');
    if (resultview) {
        dataset.r = resultview;
    }
    if (overview) {
        dataset.o = overview;
    }

    const pageid = parseInt(wrapper.dataset.pageid || dataset.pageid || urlParams.get('pageid') || '0', 10);
    const cmid = parseInt(urlParams.get('id') || dataset.cmid || '0', 10);
    if (!pageid || !cmid) {
        return null;
    }

    const response = await execGetMootimeterState(pageid, cmid, JSON.stringify(dataset));
    if (parseInt(response.code, 10) !== 200) {
        return null;
    }

    const states = JSON.parse(response.state);
    Object.keys(states).forEach((name) => {
        mtmstate.setAttribute('data-' + name, states[name]);
    });
    return mtmstate.dataset;
};

const refresh = async(wrapperid, gridid, emptyid) => {
    const wrapper = document.getElementById(wrapperid);
    if (!wrapper) {
        return;
    }
    const pageid = parseInt(wrapper.dataset.pageid, 10);
    const enableReactions = wrapper.dataset.enablereactions === '1';
    let stateDataset = null;
    try {
        stateDataset = await updateMootimeterState(wrapper);
    } catch (err) {
        displayException(err);
        return;
    }

    const previousLastupdated = parseInt(wrapper.dataset.lastupdated || '0', 10);
    const answerschangedat = stateDataset ? parseInt(stateDataset.answerschangedat || '0', 10) : 0;
    if (answerschangedat > 0 && answerschangedat === previousLastupdated) {
        return;
    }

    let response = null;
    try {
        response = await execGetAnswers(pageid);
    } catch (err) {
        displayException(err);
        return;
    }

    if (response.lastupdated && response.lastupdated === previousLastupdated) {
        return;
    }
    wrapper.dataset.lastupdated = response.lastupdated || answerschangedat;

    const grid = document.getElementById(gridid);
    if (!grid) {
        return;
    }

    const empty = document.getElementById(emptyid);
    if (!response.bubbles || response.bubbles.length === 0) {
        // Remove all bubble nodes, but in a stable way (no innerHTML).
        grid.querySelectorAll('.mootimeter-oe-bubble').forEach((n) => n.remove());
        const state = gridState.get(grid);
        if (state) {
            state.lastSeen.clear();
        }
        if (empty) {
            empty.style.display = '';
        }
        return;
    }

    if (empty) {
        empty.style.display = 'none';
    }

    await syncGrid(grid, response.bubbles, enableReactions);
    initToggleReaction(grid);
};

/**
 * Diff the incoming bubble payload against the live DOM and apply the
 * minimum number of mutations: patch existing bubbles, append new ones,
 * remove ones that disappeared. Order in the source data is preserved so
 * the server's chronological sort wins.
 *
 * @param {HTMLElement} grid
 * @param {Array} bubbles    Server-side bubble payload.
 * @param {boolean} enableReactions
 */
async function syncGrid(grid, bubbles, enableReactions) {
    const state = gridState.get(grid) || {lastSeen: new Map(), baselined: true};
    const existing = new Map();
    grid.querySelectorAll('.mootimeter-oe-bubble').forEach((node) => {
        if (node.dataset.answerid) {
            existing.set(node.dataset.answerid, node);
        }
    });

    const incomingIds = new Set(bubbles.map((b) => String(b.id)));
    // Drop bubbles the server no longer reports.
    existing.forEach((node, id) => {
        if (!incomingIds.has(id)) {
            node.remove();
            state.lastSeen.delete(id);
        }
    });

    let previousNode = null;
    for (const bubble of bubbles) {
        const id = String(bubble.id);
        let node = existing.get(id);
        if (!node) {
            // Render the new bubble through Mustache and insert it in the
            // current iteration position.
            try {
                const ctx = enableReactions
                    ? bubble
                    : {
                        id: bubble.id,
                        answer: bubble.answer,
                        isown: bubble.isown,
                        textsize: bubble.textsize,
                        reactions: [],
                    };
                const rendered = await Templates.renderForPromise(
                    'mootimetertool_openended/bubble', ctx
                );
                const tmp = document.createElement('div');
                tmp.innerHTML = rendered.html.trim();
                node = tmp.firstChild;
                if (!node) {
                    continue;
                }
            } catch (err) {
                displayException(err);
                continue;
            }
            // Brand-new bubble -> baseline its counts so we don't rain on
            // first appearance.
            const counts = new Map();
            for (const r of bubble.reactions || []) {
                counts.set(r.key, r.count);
            }
            state.lastSeen.set(id, counts);
        } else if (state.baselined) {
            // Existing bubble: compute reaction deltas before patching counts.
            applyReactionDeltas(node, bubble, state);
            patchBubbleInPlace(node, bubble);
        } else {
            patchBubbleInPlace(node, bubble);
        }

        // Insert / move into the right slot. If the node is already in the
        // correct position we skip the DOM mutation.
        const expectedAfter = previousNode ? previousNode.nextSibling : grid.firstChild;
        if (node !== expectedAfter && node.parentNode === grid) {
            grid.insertBefore(node, expectedAfter);
        } else if (node.parentNode !== grid) {
            grid.insertBefore(node, expectedAfter);
        }
        previousNode = node;
    }

    state.baselined = true;
}

/**
 * Patch a bubble node from the server payload without replacing it.
 *
 * @param {HTMLElement} node
 * @param {object} bubble
 */
function patchBubbleInPlace(node, bubble) {
    // Update text + size class if the answer was edited server-side.
    const textEl = node.querySelector('.mootimeter-oe-bubble-text');
    if (textEl) {
        if (textEl.textContent !== bubble.answer) {
            textEl.textContent = bubble.answer;
        }
        ['oe-text-l', 'oe-text-m', 'oe-text-s'].forEach((c) => textEl.classList.remove(c));
        if (bubble.textsize) {
            textEl.classList.add('oe-text-' + bubble.textsize);
        }
    }

    // Update each reaction button's count + active/has-reactions classes.
    for (const r of bubble.reactions || []) {
        const btn = node.querySelector(
            '.mootimeter-oe-reaction[data-emoji="' + r.key + '"]'
        );
        if (!btn) {
            continue;
        }
        btn.classList.toggle('active', !!r.mine);
        btn.classList.toggle('has-reactions', r.count > 0);
        btn.setAttribute('aria-pressed', String(!!r.mine));
        const countEl = btn.querySelector('.oe-count');
        if (countEl && countEl.textContent !== String(r.count)) {
            countEl.textContent = r.count;
        }
    }
}

/**
 * Compare the new reaction counts against the recorded ones and spawn rain
 * for any positive deltas. Updates the lastSeen record afterwards so the
 * next poll diffs from this point.
 *
 * @param {HTMLElement} node
 * @param {object} bubble
 * @param {object} state    The per-grid state from gridState.
 */
function applyReactionDeltas(node, bubble, state) {
    const id = String(bubble.id);
    const previous = state.lastSeen.get(id) || new Map();
    const next = new Map();
    for (const r of bubble.reactions || []) {
        const prev = previous.get(r.key) || 0;
        const delta = r.count - prev;
        if (delta > 0) {
            spawnRain(node, r.symbol, delta);
        }
        next.set(r.key, r.count);
    }
    state.lastSeen.set(id, next);
}
