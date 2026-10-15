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
 * Bridge from the ESM/React world to the Moodle AMD APIs.
 *
 * @module     mod_mootimeter/lib/moodle
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable no-unused-vars -- the core rule reports the parameter names of TypeScript function types. */
declare const require: (deps: string[], resolve: (...modules: any[]) => void, reject: (error: unknown) => void) => void;

/**
 * Load an AMD module.
 *
 * @param {string} name AMD module name, e.g. core/ajax
 * @returns {Promise<any>}
 */
export function requireAmd<T = any>(name: string): Promise<T> {
    return new Promise<T>((resolve, reject) => {
        require([name], resolve, reject);
    });
}

const [Ajax, Str, Notification, Toast, Config, FilterEvents] = await Promise.all([
    requireAmd('core/ajax'),
    requireAmd('core/str'),
    requireAmd('core/notification'),
    requireAmd('core/toast'),
    requireAmd('core/config'),
    requireAmd('core_filters/events'),
]);

export const wwwroot: string = Config.wwwroot;

/**
 * Call an external function.
 *
 * @param {string} methodname
 * @param {object} args
 * @returns {Promise<any>}
 */
export function callWs<T = any>(methodname: string, args: Record<string, unknown>): Promise<T> {
    return Promise.resolve(Ajax.call([{methodname, args}])[0]);
}

const stringCache = new Map<string, Promise<string>>();

/**
 * Load language strings of one component.
 *
 * @param {string} component
 * @param {string[]} keys
 * @returns {Promise<Record<string, string>>} strings keyed by their identifier
 */
export async function loadStrings(component: string, keys: readonly string[]): Promise<Record<string, string>> {
    const missing = keys.filter((key) => !stringCache.has(`${component}/${key}`));
    if (missing.length) {
        const request: Promise<string[]> = Promise.resolve(Str.get_strings(missing.map((key) => ({key, component}))));
        missing.forEach((key, index) => {
            stringCache.set(`${component}/${key}`, request.then((strings) => strings[index]));
        });
    }
    const values = await Promise.all(keys.map((key) => stringCache.get(`${component}/${key}`) as Promise<string>));
    return Object.fromEntries(keys.map((key, index) => [key, values[index]]));
}

/**
 * Get a single language string.
 *
 * @param {string} key
 * @param {string} component
 * @param {any} param
 * @returns {Promise<string>}
 */
export function getString(key: string, component: string, param?: unknown): Promise<string> {
    return Promise.resolve(Str.get_string(key, component, param));
}

/**
 * Show an exception in the Moodle way.
 *
 * @param {unknown} error
 */
export function displayException(error: unknown): void {
    Notification.exception(error);
}

/**
 * Ask for confirmation of a deletion.
 *
 * @param {string} title
 * @param {string} question
 * @returns {Promise<boolean>} true if the user confirmed
 */
export async function confirmDelete(title: string, question: string): Promise<boolean> {
    try {
        await Notification.deleteCancelPromise(title, question, getString('delete', 'core'));
        return true;
    } catch {
        return false;
    }
}

/**
 * Show a toast.
 *
 * @param {string} message
 * @param {string} type success, info, warning or danger
 */
export function addToast(message: string, type = 'success'): void {
    Toast.add(message, {type});
}

/**
 * Tell the filters (e.g. MathJax) that content changed.
 *
 * @param {HTMLElement|null} node
 */
export function notifyFilterContentUpdated(node: HTMLElement | null): void {
    if (node) {
        FilterEvents.notifyFilterContentUpdated([node]);
    }
}

/**
 * Store a page setting.
 *
 * @param {number} pageid
 * @param {string} name
 * @param {string|number|boolean} value
 * @returns {Promise<string>} the stored value
 */
export async function storeSetting(pageid: number, name: string, value: string | number | boolean): Promise<string> {
    let stored = String(value);
    if (typeof value === 'boolean') {
        stored = value ? '1' : '0';
    }
    const result = await callWs<{value: string}>('mod_mootimeter_store_setting', {pageid, name, value: stored});
    return result.value;
}
