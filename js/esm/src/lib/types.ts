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
 * @module     mod_mootimeter/lib/types
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export type View = 'question' | 'results' | 'overview' | 'addpage';

/** Props of the application, rendered by view.php. */
export type AppProps = {
    cmid: number;
    pageid: number;
    view: View;
    fullscreen: boolean;
    ismoderator: boolean;
    isediting: boolean;
    refreshinterval: number;
};

export type PageListItem = {
    id: number;
    tool: string;
    visible: boolean;
    number: number;
    title: string;
    iconurl: string;
};

export type ToolInfo = {
    name: string;
    displayname: string;
    description: string;
    iconurl: string;
};

export type ToolComponents = {
    content: string;
    settings: string;
    results: string;
    overview: string;
    menu: string;
};

/** Common page data, see mod_mootimeter\local\exporter::export_page(). */
export type PageInfo = {
    id: number;
    cmid: number;
    tool: string;
    toolname: string;
    visible: boolean;
    question: string;
    questionhtml: string;
    showonteacherpermission: boolean;
    resultsvisible: boolean;
    anonymousmode: boolean;
    hasanswers: boolean;
    components: ToolComponents;
};

export type ChangeState = {
    pagelist: number;
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
    ismoderator: boolean;
    isediting: boolean;
    settingsVersion: string;
    answersVersion: string;
    refresh: () => void;
};

/** Result of the answer storing web services. */
export type StoreResult = {
    code: number;
    message: string;
};

export const CODE_OK = 200;
