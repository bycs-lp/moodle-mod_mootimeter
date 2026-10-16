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

import {useEffect, useState, type DependencyList} from 'react';
import {getStrings} from '@moodle/lms/core/stringUtils';
import {ApiError, get} from './api';
import {displayError} from './moodle';
import type {Collection} from './types';

/**
 * Load language strings of a component. Until they are loaded, every string is empty.
 *
 * @param component
 * @param keys
 * @returns The strings keyed by their identifier.
 */
export function useStrings(component: string, keys: readonly string[]): Record<string, string> {
    const [strings, setStrings] = useState<Record<string, string>>({});
    const keylist = keys.join(',');

    useEffect(() => {
        let active = true;
        getStrings(keys.map((key) => ({key, component})))
            .then((values) => active && setStrings(Object.fromEntries(keys.map((key, index) => [key, values[index]]))))
            .catch(displayError);
        return () => {
            active = false;
        };
    }, [component, keylist]);

    return new Proxy(strings, {get: (target, key: string) => target[key] ?? ''});
}

/**
 * Get a resource of the REST API whenever one of the dependencies changes.
 *
 * Responses of outdated requests are ignored. Hidden pages are reported by the app, not by each view.
 *
 * @param component
 * @param path
 * @param deps
 * @returns The latest resource, null until the first response arrived.
 */
export function useResource<T>(component: string, path: string, deps: DependencyList): T | null {
    const [data, setData] = useState<T | null>(null);

    useEffect(() => {
        let active = true;
        get<T>(component, path)
            .then((resource) => active && setData(resource))
            .catch((error) => {
                if (active && !(error instanceof ApiError && error.code === 'pagelocked')) {
                    displayError(error);
                }
            });
        return () => {
            active = false;
        };
    }, [component, path, ...deps]);

    return data;
}

/** A page of a collection with the means to switch the page. */
export type PagedCollection<T> = {
    items: T[];
    page: number;
    pageSize: number;
    totalPages: number;
    setPage: (page: number) => void;
};

/**
 * Get a page of a collection of the REST API whenever the page or one of the dependencies changes.
 *
 * @param component
 * @param path Path of the collection, optionally with query parameters.
 * @param deps
 * @returns The items of the current page, null until the first response arrived.
 */
export function useCollection<T>(component: string, path: string, deps: DependencyList): PagedCollection<T> | null {
    const [page, setPage] = useState(1);
    const separator = path.includes('?') ? '&' : '?';
    const collection = useResource<Collection<T>>(component, `${path}${separator}page=${page}`, deps);

    if (!collection) {
        return null;
    }
    const {pageSize, totalItems} = collection.pagination;
    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
    if (page > totalPages) {
        // The last items of the last page have been deleted.
        setPage(totalPages);
    }
    return {items: collection.items, page, pageSize, totalPages, setPage};
}
