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
 * Settings column of a page, shown to moderators in editing mode.
 *
 * @module     mod_mootimeter/settingspanel
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React from 'react';
import {Divider, TextSetting, TransparentButton} from './lib/components';
import {useStrings} from './lib/hooks';
import {callWs, confirmDelete, displayException} from './lib/moodle';
import type {ToolViewProps} from './lib/types';
import ToolComponent from './toolcomponent';

type SettingsPanelProps = ToolViewProps & {
    onDeleted: () => void;
};

/**
 * Common page settings followed by the settings of the tool.
 *
 * @param {SettingsPanelProps} props
 * @returns {React.JSX.Element}
 */
export default function SettingsPanel(props: SettingsPanelProps): React.JSX.Element {
    const {page, refresh, onDeleted} = props;
    const s = useStrings('mod_mootimeter', [
        'tooltip_enable_page',
        'tooltip_disable_page',
        'delete_page',
        'enter_question',
        'question',
        'settings',
    ]);
    const c = useStrings('core', ['areyousure']);

    const toggleVisibility = () => {
        callWs('mod_mootimeter_set_page_visibility', {pageid: page.id, visible: !page.visible})
            .then(refresh)
            .catch(displayException);
    };

    const deletePage = async() => {
        if (await confirmDelete(s.delete_page, c.areyousure)) {
            callWs('mod_mootimeter_delete_page', {pageid: page.id}).then(onDeleted).catch(displayException);
        }
    };

    return (
        <div className="mootimetersettings mootimeterfullwidth">
            <div className="mootimeter-header-wrapper">
                <h4>{page.toolname}</h4>
                <div className="d-flex">
                    <TransparentButton
                        icon={page.visible ? 'fa-eye' : 'fa-eye-slash'}
                        title={page.visible ? s.tooltip_enable_page : s.tooltip_disable_page}
                        onClick={toggleVisibility}
                    />
                    <TransparentButton icon="fa-trash" title={s.delete_page} onClick={deletePage} />
                </div>
            </div>

            <TextSetting
                pageid={page.id}
                name="question"
                value={page.question}
                label={s.question}
                placeholder={s.enter_question}
                onStored={refresh}
            />

            <Divider />

            <ToolComponent specifier={page.components.settings} {...props} />
        </div>
    );
}
