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
 * Settings of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/settings
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React from 'react';
import {AnonymousModeSetting, CheckboxSetting, Loading, NumberSetting} from '@moodle/lms/mod_mootimeter/lib/components';
import {useStrings, useWebservice} from '@moodle/lms/mod_mootimeter/lib/hooks';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';

type WordcloudData = {
    maxinputsperuser: number;
    allowduplicateanswers: boolean;
};

/**
 * Maximum number of answers, duplicates and anonymous mode.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function Settings({page, settingsVersion, refresh}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mootimetertool_wordcloud', ['answers_max_number', 'allowduplicateanswers']);
    const c = useStrings('core', ['settings']);
    const data = useWebservice<WordcloudData>(
        'mootimetertool_wordcloud_get_page_data',
        {pageid: page.id},
        [page.id, settingsVersion]
    );

    if (!data) {
        return <Loading />;
    }

    return (
        <>
            <p className="text-bold">{c.settings}</p>
            <NumberSetting
                pageid={page.id}
                name="maxinputsperuser"
                value={data.maxinputsperuser}
                label={s.answers_max_number}
                onStored={refresh}
            />
            <CheckboxSetting
                pageid={page.id}
                name="allowduplicateanswers"
                value={data.allowduplicateanswers}
                label={s.allowduplicateanswers}
                onStored={refresh}
            />
            <AnonymousModeSetting page={page} onStored={refresh} />
        </>
    );
}
