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
 * Client of the REST API of mod_mootimeter and the mootimetertool subplugins.
 *
 * core/fetch rejects failed requests with the status text only, this client exposes the RFC 9457 problem details.
 *
 * @module     mod_mootimeter/lib/api
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import config from '@moodle/lms/core/config';
import Pending from '@moodle/lms/core/pending';
import type {Collection} from './types';

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

/** Field level detail of a validation problem. */
export type ProblemDetail = {
    field: string;
    message: string;
};

/** A failed request, carrying the RFC 9457 problem details of the response. */
export class ApiError extends Error {
    readonly status: number;
    readonly code: string;
    readonly details: ProblemDetail[];

    constructor(status: number, problem: Record<string, unknown> | null, fallback: string) {
        super(String(problem?.detail ?? problem?.message ?? fallback));
        this.name = 'ApiError';
        this.status = status;
        this.code = String(problem?.code ?? problem?.errorcode ?? '');
        this.details = Array.isArray(problem?.details) ? problem.details as ProblemDetail[] : [];
    }
}

/**
 * Send a request to the REST API.
 *
 * State changing requests carry the session key, the API requires it for cookie authenticated requests.
 *
 * @param method HTTP method.
 * @param component Frankenstyle component owning the resource.
 * @param path Path of the resource below the component.
 * @param body JSON body.
 * @returns The JSON response, undefined for 204 responses.
 */
export async function request<T>(method: Method, component: string, path: string, body?: unknown): Promise<T> {
    const pending = new Pending(`${component}:${method} ${path}`);
    try {
        const headers: Record<string, string> = {'Accept': 'application/json, application/problem+json'};
        if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
        }
        if (method !== 'GET') {
            headers['X-Moodle-Sesskey'] = config.sesskey;
        }
        const response = await fetch(`${config.apibase}/rest/v2/${component}${path}`, {
            method,
            headers,
            credentials: 'same-origin',
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        if (response.status === 204) {
            return undefined as T;
        }
        const data = await response.json().catch(() => null);
        if (!response.ok) {
            throw new ApiError(response.status, data, response.statusText);
        }
        return data as T;
    } finally {
        pending.resolve();
    }
}

/**
 * Get a resource.
 *
 * @param component
 * @param path
 * @returns The resource.
 */
export const get = <T>(component: string, path: string): Promise<T> => request<T>('GET', component, path);

/**
 * Get all items of a collection, page by page.
 *
 * @param component
 * @param path Path of the collection, optionally with query parameters.
 * @returns All items.
 */
export async function getAll<T>(component: string, path: string): Promise<T[]> {
    const separator = path.includes('?') ? '&' : '?';
    const items: T[] = [];
    for (let page = 1; ; page++) {
        const collection = await get<Collection<T>>(component, `${path}${separator}page=${page}&pagesize=100`);
        items.push(...collection.items);
        if (!collection.items.length || items.length >= collection.pagination.totalItems) {
            return items;
        }
    }
}

/**
 * Create a resource.
 *
 * @param component
 * @param path
 * @param body
 * @returns The created resource.
 */
export const post = <T>(component: string, path: string, body: unknown = {}): Promise<T> =>
    request<T>('POST', component, path, body);

/**
 * Replace a resource.
 *
 * @param component
 * @param path
 * @param body
 * @returns The new representation, if the API returns one.
 */
export const put = <T>(component: string, path: string, body: unknown): Promise<T> =>
    request<T>('PUT', component, path, body);

/**
 * Change a resource partially.
 *
 * @param component
 * @param path
 * @param body
 * @returns The changed resource.
 */
export const patch = <T>(component: string, path: string, body: unknown): Promise<T> =>
    request<T>('PATCH', component, path, body);

/**
 * Delete a resource.
 *
 * @param component
 * @param path
 * @returns Nothing.
 */
export const remove = (component: string, path: string): Promise<void> => request<void>('DELETE', component, path);
