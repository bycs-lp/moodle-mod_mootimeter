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
 * Answer overview of the quiz and poll tool.
 *
 * @module     mootimetertool_quiz/overview
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useState} from 'react';
import {put, remove} from '@moodle/lms/mod_mootimeter/lib/api';
import {Choice, FormattedHtml, Loading, OverviewTable, TransparentButton} from '@moodle/lms/mod_mootimeter/lib/components';
import {useCollection, useResource, useStrings} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {displayError} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {PageInfo, ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {componentOf, type QuizData, type QuizResponse} from './types';

type AnswerEditorProps = {
    page: PageInfo;
    row: QuizResponse;
    data: QuizData;
    onChanged: () => void;
};

/**
 * Shows the selected options of a participant and lets moderators change them.
 *
 * @param {AnswerEditorProps} props
 * @returns {React.JSX.Element}
 */
function AnswerEditor({page, row, data, onChanged}: AnswerEditorProps): React.JSX.Element {
    const s = useStrings('core', ['edit', 'save', 'cancel']);
    const [editing, setEditing] = useState(false);
    const [selected, setSelected] = useState<number[]>(row.optionIds);
    const single = data.maxAnswers === 1;

    if (!editing) {
        const texts = data.options.filter((option) => row.optionIds.includes(option.id));
        return (
            <span>
                {texts.map((option, index) => (
                    <React.Fragment key={option.id}>
                        {index > 0 && ' | '}
                        <FormattedHtml html={option.textHtml} />
                    </React.Fragment>
                ))}
                <TransparentButton
                    icon="fa-pencil"
                    title={s.edit}
                    className="text-body"
                    onClick={() => {
                        setSelected(row.optionIds);
                        setEditing(true);
                    }}
                />
            </span>
        );
    }

    const save = async() => {
        try {
            await put(componentOf(page), `/pages/${page.id}/responses/${row.id}`, {optionIds: selected});
            setEditing(false);
            onChanged();
        } catch (error) {
            displayError(error);
        }
    };

    return (
        <div>
            {data.options.map((option) => (
                <Choice
                    key={option.id}
                    type={single ? 'radio' : 'checkbox'}
                    name={`mtmt_quiz_overview_${row.id}`}
                    checked={selected.includes(option.id)}
                    label={<FormattedHtml html={option.textHtml} />}
                    onChange={(checked) => setSelected((old) => {
                        if (single) {
                            return checked ? [option.id] : [];
                        }
                        return checked ? [...old, option.id] : old.filter((id) => id !== option.id);
                    })}
                />
            ))}
            <div className="d-flex gap-1 mt-2">
                <button type="button" className="btn btn-primary btn-sm" onClick={save}>{s.save}</button>
                <button type="button" className="btn btn-secondary btn-sm" onClick={() => setEditing(false)}>{s.cancel}</button>
            </div>
        </div>
    );
}

/**
 * Table of the answers of all participants.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function OverviewView({page, settingsVersion, answersVersion, refresh}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', ['delete_answers_of_user_dialog_title', 'delete_answers_of_user_dialog_question']);
    const component = componentOf(page);
    const data = useResource<QuizData>(component, `/pages/${page.id}`, [settingsVersion]);
    const responses = useCollection<QuizResponse>(
        component,
        `/pages/${page.id}/responses?sort=id`,
        [settingsVersion, answersVersion]
    );

    if (!data || !responses) {
        return <Loading />;
    }

    return (
        <OverviewTable<QuizResponse>
            rows={responses.items}
            offset={(responses.page - 1) * responses.pageSize}
            totalPages={responses.totalPages}
            currentPage={responses.page}
            onPageChange={responses.setPage}
            deleteTitle={s.delete_answers_of_user_dialog_title}
            deleteQuestion={s.delete_answers_of_user_dialog_question}
            onDelete={(row) => remove(component, `/pages/${page.id}/responses/${row.id}`).then(refresh)}
            renderAnswer={(row) => <AnswerEditor key={row.id} page={page} row={row} data={data} onChanged={refresh} />}
        />
    );
}
