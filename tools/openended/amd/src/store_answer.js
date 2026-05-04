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
 * Submit handler + live character counter for the open-ended student input.
 *
 * @module     mootimetertool_openended/store_answer
 * @copyright  2026, ISB Bayern
 * @author     Benedikt Blumenfelder
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {call as fetchMany} from 'core/ajax';
import {execReloadPage as reloadPage} from 'mod_mootimeter/reload_page';
import {renderInfoBox, removeInfoBox, delay} from 'mod_mootimeter/utils';
import {exception as displayException} from 'core/notification';

const INFOBOX_ID = 'mtmt_openended_warning';

export const init = (inputid, buttonid, charcountid) => {

    const input = document.getElementById(inputid);
    const button = document.getElementById(buttonid);
    const charcount = document.getElementById(charcountid);

    if (!input) {
        return;
    }

    // Live character counter.
    if (charcount) {
        const currentEl = charcount.querySelector('.oe-current');
        const maxchars = parseInt(input.dataset.maxchars || input.getAttribute('maxlength') || '200', 10);
        const update = () => {
            const len = input.value.length;
            if (currentEl) {
                currentEl.textContent = len;
            }
            charcount.classList.toggle('oe-charcount-near', len >= Math.floor(maxchars * 0.9));
            charcount.classList.toggle('oe-charcount-full', len >= maxchars);
        };
        input.addEventListener('input', update);
        update();
    }

    // Cmd/Ctrl+Enter submits.
    input.addEventListener('keydown', (event) => {
        if ((event.code === 'Enter' || event.code === 'NumpadEnter') && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            store(input);
        }
    });

    if (button) {
        button.addEventListener('click', () => store(input));
    }
};

/**
 * Read pageid + value from the input and dispatch.
 *
 * @param {HTMLElement} input
 */
function store(input) {
    const pageid = input.dataset.pageid;
    const answer = input.value;
    storeAnswer(pageid, answer, input);
}

/**
 * Webservice call.
 *
 * @param {int} pageid
 * @param {string} answer
 * @returns {Promise}
 */
const execStoreAnswer = (pageid, answer) => fetchMany([{
    methodname: 'mootimetertool_openended_store_answer',
    args: {pageid, answer},
}])[0];

/**
 * Submit and reload the page on success.
 *
 * @param {int} pageid
 * @param {string} answer
 * @param {HTMLElement} input
 */
const storeAnswer = async(pageid, answer, input) => {
    input.disabled = true;
    let response = null;
    try {
        response = await execStoreAnswer(pageid, answer);
    } catch (err) {
        displayException(err);
        input.disabled = false;
        return;
    }

    removeInfoBox(INFOBOX_ID);

    if (response.code !== 200) {
        renderInfoBox('mtmt_tool-colct-header', INFOBOX_ID, 'warning', response.string);
        input.disabled = false;
        input.focus();
        return;
    }

    // Success - clear, then refresh the page so the bubble grid updates.
    input.value = '';
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    await reloadPage(urlParams.get('pageid'), urlParams.get('id'));

    // The DOM has been replaced; re-focus the (new) textarea.
    await delay(100);
    const elements = document.getElementsByClassName('mootimeter-oe-textarea');
    if (elements.length > 0) {
        elements.item(0).focus();
    }
};
