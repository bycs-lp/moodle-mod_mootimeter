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
 * Content menu above the page content.
 *
 * @module     mod_mootimeter/contentmenu
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React from 'react';
import {IconButton} from './lib/components';
import {useStrings} from './lib/hooks';
import {callWs, confirmDelete, displayException, storeSetting} from './lib/moodle';
import type {ToolViewProps, View} from './lib/types';
import ToolComponent from './toolcomponent';

/* eslint-disable no-unused-vars -- the core rule reports the parameter names of TypeScript function types. */
type ContentMenuProps = ToolViewProps & {
    view: View;
    fullscreen: boolean;
    onView: (view: View) => void;
    onFullscreen: (fullscreen: boolean) => void;
};

/**
 * Buttons to switch between question, results and answer overview, and the moderator actions.
 *
 * @param {ContentMenuProps} props
 * @returns {React.JSX.Element}
 */
export default function ContentMenu(props: ContentMenuProps): React.JSX.Element {
    const {page, ismoderator, view, fullscreen, onView, onFullscreen, refresh} = props;
    const s = useStrings('mod_mootimeter', [
        'tooltip_content_menu_teacherpermission',
        'tooltip_content_menu_teacherpermission_disabled',
        'tooltip_delete_all_answers',
        'delete_all_answers_dialog_title',
        'delete_all_answers_dialog_question',
        'show_answer_overview',
        'tooltip_show_question_page',
        'tooltip_show_results_page',
        'tooltip_fullscreen_expand',
        'tooltip_fullscreen_compress',
    ]);

    const toggleResultsVisibility = () => {
        storeSetting(page.id, 'showonteacherpermission', !page.showonteacherpermission).then(refresh).catch(displayException);
    };

    const deleteAllAnswers = async() => {
        if (await confirmDelete(s.delete_all_answers_dialog_title, s.delete_all_answers_dialog_question)) {
            callWs('mod_mootimeter_delete_answers', {pageid: page.id, scope: 'all'}).then(refresh).catch(displayException);
        }
    };

    return (
        <div className="mootimeter-content-menu" id="mootimeter-pagecontentmenu">
            {ismoderator && (
                <>
                    <IconButton
                        icon={page.showonteacherpermission ? 'fa-eye' : 'fa-eye-slash'}
                        title={page.showonteacherpermission
                            ? s.tooltip_content_menu_teacherpermission_disabled
                            : s.tooltip_content_menu_teacherpermission}
                        active={page.showonteacherpermission}
                        onClick={toggleResultsVisibility}
                    />
                    <ToolComponent specifier={page.components.menu} {...props} />
                    <IconButton icon="fa-trash" title={s.tooltip_delete_all_answers} onClick={deleteAllAnswers} />
                    <IconButton
                        icon={view === 'overview' ? 'fa-pencil-square-o' : 'fa-table'}
                        title={view === 'overview' ? s.tooltip_show_question_page : s.show_answer_overview}
                        onClick={() => onView(view === 'overview' ? 'question' : 'overview')}
                    />
                    <div className="vertical-divider"></div>
                </>
            )}
            <IconButton
                icon={view === 'results' ? 'fa-pencil-square-o' : 'fa-bar-chart'}
                title={view === 'results' ? s.tooltip_show_question_page : s.tooltip_show_results_page}
                onClick={() => onView(view === 'results' ? 'question' : 'results')}
            />
            <IconButton
                icon={fullscreen ? 'fa-compress' : 'fa-expand'}
                title={fullscreen ? s.tooltip_fullscreen_compress : s.tooltip_fullscreen_expand}
                active={fullscreen}
                onClick={() => onFullscreen(!fullscreen)}
            />
        </div>
    );
}
