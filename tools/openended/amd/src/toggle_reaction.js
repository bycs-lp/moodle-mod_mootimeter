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
 * Click handler for emoji reaction buttons.
 *
 * Each user has at most one active reaction per bubble; clicking a different emoji
 * replaces the previous one. Reacting to one's own contribution is forbidden and
 * the buttons are rendered disabled by the template.
 *
 * @module     mootimetertool_openended/toggle_reaction
 * @copyright  2026, ISB Bayern
 * @author     Benedikt Blumenfelder
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {call as fetchMany} from 'core/ajax';
import {exception as displayException} from 'core/notification';

const HANDLED_FLAG = 'oeReactionHandlerBound';

/**
 * Wire all .mootimeter-oe-reaction inside the given grid container.
 *
 * @param {HTMLElement} grid
 */
export const init = (grid) => {
    if (!grid || grid.dataset[HANDLED_FLAG] === '1') {
        return;
    }
    grid.dataset[HANDLED_FLAG] = '1';
    grid.addEventListener('click', onClick);
};

/**
 * Webservice call.
 *
 * @param {int} answerid
 * @param {string} emoji
 * @returns {Promise}
 */
const execToggleReaction = (answerid, emoji) => fetchMany([{
    methodname: 'mootimetertool_openended_toggle_reaction',
    args: {answerid, emoji},
}])[0];

/**
 * Apply server-authoritative reaction state to all buttons inside a bubble.
 *
 * @param {HTMLElement} bubble
 * @param {Array<{key: string, count: number, mine: boolean}>} reactions
 */
function syncBubble(bubble, reactions) {
    if (!bubble || !Array.isArray(reactions)) {
        return;
    }
    for (const r of reactions) {
        const btn = bubble.querySelector(
            '.mootimeter-oe-reaction[data-emoji="' + r.key + '"]'
        );
        if (!btn) {
            continue;
        }
        btn.classList.toggle('active', !!r.mine);
        btn.setAttribute('aria-pressed', String(!!r.mine));
        const countEl = btn.querySelector('.oe-count');
        if (countEl) {
            countEl.textContent = r.count;
        }
    }
}

/**
 * Click handler bound to the grid via event delegation.
 *
 * @param {Event} event
 */
async function onClick(event) {
    const button = event.target.closest('.mootimeter-oe-reaction');
    if (!button) {
        return;
    }
    event.preventDefault();
    if (button.disabled) {
        return;
    }

    const bubble = button.closest('.mootimeter-oe-bubble');
    if (!bubble || bubble.dataset.isown === '1') {
        return;
    }

    const answerid = parseInt(bubble.dataset.answerid, 10);
    const emoji = button.dataset.emoji;
    if (!answerid || !emoji) {
        return;
    }

    // Disable every button on the bubble while the WS call is in flight to prevent
    // racing clicks that would conflict with the single-reaction-per-bubble rule.
    const allButtons = bubble.querySelectorAll('.mootimeter-oe-reaction');
    allButtons.forEach((b) => { b.disabled = true; });

    let response = null;
    try {
        response = await execToggleReaction(answerid, emoji);
    } catch (err) {
        allButtons.forEach((b) => { b.disabled = false; });
        displayException(err);
        return;
    }

    syncBubble(bubble, response.reactions);
    allButtons.forEach((b) => { b.disabled = false; });
}
