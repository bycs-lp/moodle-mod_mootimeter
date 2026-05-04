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
 * Polls the bubble grid and re-renders when the server reports a change.
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

const INITIAL_DELAY_MS = 1500;

export const init = (wrapperid, gridid, emptyid) => {

    const wrapper = document.getElementById(wrapperid);
    const grid = document.getElementById(gridid);
    if (!wrapper || !grid) {
        return;
    }

    // Wire reactions for any server-rendered bubbles already on the page.
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
 * Webservice call.
 *
 * @param {int} pageid
 * @returns {Promise}
 */
const execGetAnswers = (pageid) => fetchMany([{
    methodname: 'mootimetertool_openended_get_answers',
    args: {pageid},
}])[0];

/**
 * Compare server lastupdated with our local copy and re-render if needed.
 *
 * @param {string} wrapperid
 * @param {string} gridid
 * @param {string} emptyid
 */
const refresh = async(wrapperid, gridid, emptyid) => {
    const wrapper = document.getElementById(wrapperid);
    if (!wrapper) {
        return;
    }
    const pageid = parseInt(wrapper.dataset.pageid, 10);
    const enableReactions = wrapper.dataset.enablereactions === '1';

    let response = null;
    try {
        response = await execGetAnswers(pageid);
    } catch (err) {
        displayException(err);
        return;
    }

    const previousLastupdated = parseInt(wrapper.dataset.lastupdated || '0', 10);
    if (response.lastupdated && response.lastupdated === previousLastupdated) {
        return;
    }
    wrapper.dataset.lastupdated = response.lastupdated;

    const grid = document.getElementById(gridid);
    if (!grid) {
        return;
    }

    if (!response.bubbles || response.bubbles.length === 0) {
        grid.innerHTML = '';
        const empty = document.getElementById(emptyid);
        if (empty) {
            empty.style.display = '';
        }
        return;
    }

    const empty = document.getElementById(emptyid);
    if (empty) {
        empty.style.display = 'none';
    }

    // Render each bubble through the partial template, then swap into the grid.
    try {
        const renders = await Promise.all(
            response.bubbles.map((bubble) => {
                const ctx = enableReactions
                    ? bubble
                    : {id: bubble.id, answer: bubble.answer, isown: bubble.isown, reactions: []};
                return Templates.renderForPromise('mootimetertool_openended/bubble', ctx);
            })
        );
        grid.innerHTML = '';
        for (const r of renders) {
            const tmp = document.createElement('div');
            tmp.innerHTML = r.html.trim();
            const node = tmp.firstChild;
            if (node) {
                grid.appendChild(node);
            }
            // The bubble template doesn't ship its own JS, so we don't run r.js.
        }
        initToggleReaction(grid);
    } catch (err) {
        displayException(err);
    }
};
