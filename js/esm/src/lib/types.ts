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
 * Types shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * The resource types mirror the REST API, see mod_mootimeter\route\schema.
 *
 * @module     mod_mootimeter/lib/types
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export type View = 'question' | 'results' | 'overview' | 'addpage';

/** Props of the application, see mod_mootimeter\output\app. */
export type AppProps = {
    cmId: number;
    pageId: number;
    view: View;
    fullscreen: boolean;
    isModerator: boolean;
    isEditing: boolean;
    refreshInterval: number;
};

export type Pagination = {
    page: number;
    pageSize: number;
    totalItems: number;
};

export type Collection<T> = {
    items: T[];
    pagination: Pagination;
};

export type PageListItem = {
    id: number;
    tool: string;
    visible: boolean;
    number: number;
    title: string;
    iconUrl: string;
};

export type ToolInfo = {
    name: string;
    displayName: string;
    description: string;
    iconUrl: string;
};

export type ToolComponents = {
    content: string;
    settings: string;
    results: string;
    overview: string;
    menu: string;
};

export type PageInfo = {
    id: number;
    cmId: number;
    tool: string;
    toolName: string;
    visible: boolean;
    question: string;
    questionHtml: string;
    resultsPublished: boolean;
    canViewResults: boolean;
    anonymousMode: boolean;
    hasAnswers: boolean;
    components: ToolComponents;
};

export type Updates = {
    pageList: number;
    settings: number;
    answers: number;
};

/**
 * Props every tool component receives.
 *
 * The versions change whenever the settings or the answers of the page changed, tools reload their data then.
 */
export type ToolViewProps = {
    page: PageInfo;
    isModerator: boolean;
    isEditing: boolean;
    settingsVersion: string;
    answersVersion: string;
    refresh: () => void;
};
