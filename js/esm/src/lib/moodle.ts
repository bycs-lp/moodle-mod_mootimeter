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
 * Moodle user interface services that are not available as ESM modules yet: dialogues, toasts, filters and the edit mode.
 *
 * @module     mod_mootimeter/lib/moodle
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {requireAsync} from '@moodle/lms/core/amd';
import {getString} from '@moodle/lms/core/stringUtils';
import {ApiError} from './api';

type NotificationModule = {
    exception: (error: unknown) => void;
    deleteCancelPromise: (title: string, question: string, deletelabel?: string) => Promise<unknown>;
};

type ToastModule = {
    add: (message: string, options?: {type?: string}) => Promise<void>;
};

type FilterEventsModule = {
    notifyFilterContentUpdated: (nodes: HTMLElement[]) => void;
};

type AjaxModule = {
    call: (requests: {methodname: string; args: Record<string, unknown>}[]) => Promise<unknown>[];
};

type UserDateModule = {
    get: (requests: {timestamp: number; format: string}[]) => Promise<string[]>;
};

/**
 * Format timestamps with the date and time format of the user, like userdate() on the server.
 *
 * @param timestamps
 * @returns The formatted dates.
 */
export async function formatUserDates(timestamps: number[]): Promise<string[]> {
    if (!timestamps.length) {
        return [];
    }
    const [UserDate, format] = await Promise.all([
        requireAsync<UserDateModule>('core/user_date'),
        getString('strftimedatetimeshortaccurate', 'core_langconfig'),
    ]);
    return UserDate.get(timestamps.map((timestamp) => ({timestamp, format})));
}

/**
 * Show an error. Problems reported by the API are shown as toast, unexpected errors as exception dialogue.
 *
 * @param error
 */
export async function displayError(error: unknown): Promise<void> {
    if (error instanceof ApiError) {
        await addToast(error.message, 'danger');
        return;
    }
    const Notification = await requireAsync<NotificationModule>('core/notification');
    Notification.exception(error);
}

/**
 * Ask for the confirmation of a deletion.
 *
 * @param title
 * @param question
 * @returns Whether the user confirmed.
 */
export async function confirmDelete(title: string, question: string): Promise<boolean> {
    const Notification = await requireAsync<NotificationModule>('core/notification');
    try {
        await Notification.deleteCancelPromise(title, question, await getString('delete', 'core'));
        return true;
    } catch {
        return false;
    }
}

/**
 * Show a toast.
 *
 * @param message
 * @param type success, info, warning or danger
 */
export async function addToast(message: string, type = 'success'): Promise<void> {
    const Toast = await requireAsync<ToastModule>('core/toast');
    await Toast.add(message, {type});
}

/**
 * Switch the edit mode of Moodle on without reloading the page, like the edit mode switch of the navigation does.
 *
 * The edit mode is a setting of the user session owned by Moodle, so it is changed through the core API and the
 * switch in the navigation is updated to match.
 */
export async function enableEditMode(): Promise<void> {
    const editswitch = document.querySelector<HTMLInputElement>('input[name="setmode"][data-context]');
    if (!editswitch || editswitch.checked) {
        return;
    }
    const Ajax = await requireAsync<AjaxModule>('core/ajax');
    await Ajax.call([{
        methodname: 'core_change_editmode',
        args: {context: Number(editswitch.dataset.context), setmode: true},
    }])[0];
    editswitch.checked = true;
    editswitch.setAttribute('aria-checked', 'true');
}

/**
 * Tell the filters, e.g. MathJax, that content changed.
 *
 * @param node
 */
export async function notifyFilterContentUpdated(node: HTMLElement | null): Promise<void> {
    if (node) {
        const FilterEvents = await requireAsync<FilterEventsModule>('core_filters/events');
        FilterEvents.notifyFilterContentUpdated([node]);
    }
}
