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
 * React hooks shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * @module     mod_mootimeter/lib/hooks
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useState, DependencyList} from 'react';
import {callWs, displayException, loadStrings} from './moodle';

/**
 * Load language strings of a component. Until they are loaded, every string is empty.
 *
 * @param {string} component
 * @param {string[]} keys
 * @returns {Record<string, string>}
 */
export function useStrings(component: string, keys: readonly string[]): Record<string, string> {
    const [strings, setStrings] = useState<Record<string, string>>({});
    const keylist = keys.join(',');

    useEffect(() => {
        let active = true;
        loadStrings(component, keys)
            .then((loaded) => active && setStrings(loaded))
            .catch(displayException);
        return () => {
            active = false;
        };
    }, [component, keylist]);

    return new Proxy(strings, {get: (target, key: string) => target[key] ?? ''});
}

/**
 * Call a web service whenever one of the dependencies changes.
 *
 * Responses of outdated requests are ignored.
 *
 * @param {string} methodname
 * @param {object} args
 * @param {DependencyList} deps
 * @returns {T|null} the latest response, null until the first response arrived
 */
export function useWebservice<T>(methodname: string, args: Record<string, unknown>, deps: DependencyList): T | null {
    const [data, setData] = useState<T | null>(null);

    useEffect(() => {
        let active = true;
        callWs<T>(methodname, args)
            .then((response) => active && setData(response))
            .catch(displayException);
        return () => {
            active = false;
        };
    }, deps);

    return data;
}
