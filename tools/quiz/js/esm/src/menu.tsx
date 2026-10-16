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
 * Content menu button of the quiz to reveal the correct answers.
 *
 * @module     mootimetertool_quiz/menu
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React from 'react';
import {patch} from '@moodle/lms/mod_mootimeter/lib/api';
import {IconButton} from '@moodle/lms/mod_mootimeter/lib/components';
import {useResource, useStrings} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {displayError} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {componentOf, type QuizData} from './types';

/**
 * Toggle of the answer correction.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element|null}
 */
export default function Menu({page, settingsVersion, refresh}: ToolViewProps): React.JSX.Element | null {
    const s = useStrings('mootimetertool_quiz', [
        'tooltip_content_menu_answercorrection',
        'tooltip_content_menu_answercorrection_disabled',
    ]);
    const data = useResource<QuizData>(componentOf(page), `/pages/${page.id}`, [settingsVersion]);

    if (!data) {
        return null;
    }

    return (
        <IconButton
            icon={data.correctionShown ? 'fa-check-square-o' : 'fa-square-o'}
            title={data.correctionShown
                ? s.tooltip_content_menu_answercorrection_disabled
                : s.tooltip_content_menu_answercorrection}
            active={data.correctionShown}
            onClick={() => {
                patch(componentOf(page), `/pages/${page.id}`, {correctionShown: !data.correctionShown})
                    .then(refresh)
                    .catch(displayError);
            }}
        />
    );
}
