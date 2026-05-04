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
 * Wire the per-answer visibility toggle in the moderator overview.
 *
 * @module     mootimetertool_openended/toggle_visibility
 * @copyright  2026, ISB Bayern
 * @author     Benedikt Blumenfelder
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {call as fetchMany} from 'core/ajax';
import {exception as displayException} from 'core/notification';

export const init = (tableid) => {
    const table = document.getElementById(tableid);
    if (!table) {
        return;
    }
    table.addEventListener('click', onClick);
};

/**
 * Webservice call to flip the visibility flag.
 *
 * @param {int} answerid
 * @returns {Promise}
 */
const execToggle = (answerid) => fetchMany([{
    methodname: 'mootimetertool_openended_toggle_answer_visibility',
    args: {answerid},
}])[0];

/**
 * Handle a click on the visibility-toggle button.
 *
 * @param {Event} event
 */
async function onClick(event) {
    const button = event.target.closest('.mtmt-oe-visibility-toggle');
    if (!button) {
        return;
    }
    event.preventDefault();
    if (button.disabled) {
        return;
    }
    const answerid = parseInt(button.dataset.answerid, 10);
    if (!answerid) {
        return;
    }
    button.disabled = true;
    try {
        const response = await execToggle(answerid);
        const icon = button.querySelector('i.fa');
        if (icon) {
            icon.classList.toggle('fa-eye', !!response.visible);
            icon.classList.toggle('fa-eye-slash', !response.visible);
        }
        const row = button.closest('tr');
        if (row) {
            row.classList.toggle('mootimeter-oe-overview-row-hidden', !response.visible);
        }
    } catch (err) {
        displayException(err);
    } finally {
        button.disabled = false;
    }
}
