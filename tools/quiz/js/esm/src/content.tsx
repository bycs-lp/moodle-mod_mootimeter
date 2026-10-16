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
 * Question view of the quiz and poll tool.
 *
 * @module     mootimetertool_quiz/content
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useRef, useState} from 'react';
import {ApiError, post} from '@moodle/lms/mod_mootimeter/lib/api';
import {Choice, FormattedHtml, Loading, Notification, QuestionHeader} from '@moodle/lms/mod_mootimeter/lib/components';
import {useResource, useStrings} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {displayError} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {componentOf, type QuizData} from './types';

type Result = {success: boolean; message: string};

/**
 * Lets participants select and submit answer options.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function Content({page, isModerator, settingsVersion, refresh}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mootimetertool_quiz', [
        'submit_answer',
        'sendbutton_context_one_answers_possible',
        'sendbutton_context_more_answers_possible',
        'enter_answeroption',
    ]);
    const m = useStrings('mod_mootimeter', ['notification_success_store_answer']);
    const data = useResource<QuizData>(componentOf(page), `/pages/${page.id}`, [settingsVersion]);
    const [selected, setSelected] = useState<number[]>([]);
    const [result, setResult] = useState<Result | null>(null);
    const initialised = useRef(false);

    // Take over the stored selection once, later reloads must not discard what the user is selecting.
    useEffect(() => {
        if (data && !initialised.current) {
            setSelected(data.selectedOptionIds);
            initialised.current = true;
        }
    }, [data]);

    if (!data) {
        return <Loading />;
    }

    const single = data.maxAnswers === 1;
    const optionids = data.options.map((option) => option.id);

    const toggle = (optionid: number, checked: boolean) => {
        setResult(null);
        if (single) {
            setSelected(checked ? [optionid] : []);
        } else {
            setSelected((old) => (checked ? [...old, optionid] : old.filter((id) => id !== optionid)));
        }
    };

    const submit = async() => {
        try {
            await post(componentOf(page), `/pages/${page.id}/responses`, {
                optionIds: selected.filter((id) => optionids.includes(id)),
            });
            setResult({success: true, message: m.notification_success_store_answer});
            refresh();
        } catch (error) {
            if (error instanceof ApiError && [409, 422].includes(error.status)) {
                setResult({success: false, message: error.message});
            } else {
                displayError(error);
            }
        }
    };

    return (
        <div className="mootimeter-colcontent-preview mootimeter-width-s">
            <QuestionHeader page={page}>
                {result && (
                    <Notification
                        type={result.success ? 'success' : 'warning'}
                        icon={result.success ? 'fa-check' : 'fa-exclamation'}
                        text={result.message}
                    />
                )}
            </QuestionHeader>

            <div className="mootimeter-colcontent-preview-options filter_mathjaxloader_equation">
                {data.options.map((option) => (
                    <Choice
                        key={option.id}
                        type={single ? 'radio' : 'checkbox'}
                        name={`mtmt_quiz_${page.id}`}
                        checked={selected.includes(option.id)}
                        disabled={data.correctionShown}
                        wrapperclass={
                            (data.correctionShown ? 'mootimeter-highlighter' : '')
                            + (data.correctionShown && option.isCorrect ? ' mootimeter-success' : '')
                        }
                        label={option.textHtml || !isModerator
                            ? <FormattedHtml html={option.textHtml} />
                            : s.enter_answeroption}
                        onChange={(checked) => toggle(option.id, checked)}
                    />
                ))}
            </div>

            {!data.correctionShown && (
                <div className="mootimeter-colcontent-preview-send">
                    <button type="button" className="mootimeter-btn mootimeter-btn-full" onClick={submit}>
                        {s.submit_answer}
                    </button>
                    <small>
                        {single ? s.sendbutton_context_one_answers_possible : s.sendbutton_context_more_answers_possible}
                    </small>
                </div>
            )}
        </div>
    );
}
