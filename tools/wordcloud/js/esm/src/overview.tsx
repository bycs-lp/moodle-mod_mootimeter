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
 * Answer overview of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/overview
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useState} from 'react';
import {patch, remove} from '@moodle/lms/mod_mootimeter/lib/api';
import {Loading, OverviewTable, TransparentButton} from '@moodle/lms/mod_mootimeter/lib/components';
import {useCollection, useStrings} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {displayError} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {COMPONENT, type Answer} from './types';

type AnswerEditorProps = {
    pageid: number;
    row: Answer;
    onChanged: () => void;
};

/**
 * Shows an answer and lets moderators change it.
 *
 * @param {AnswerEditorProps} props
 * @returns {React.JSX.Element}
 */
function AnswerEditor({pageid, row, onChanged}: AnswerEditorProps): React.JSX.Element {
    const s = useStrings('core', ['edit', 'save', 'cancel']);
    const [editing, setEditing] = useState(false);
    const [text, setText] = useState(row.text);

    if (!editing) {
        return (
            <span>
                {row.text}
                <TransparentButton
                    icon="fa-pencil"
                    title={s.edit}
                    className="text-body"
                    onClick={() => {
                        setText(row.text);
                        setEditing(true);
                    }}
                />
            </span>
        );
    }

    const save = async(e: React.FormEvent) => {
        e.preventDefault();
        try {
            await patch(COMPONENT, `/pages/${pageid}/answers/${row.id}`, {text});
            setEditing(false);
            onChanged();
        } catch (error) {
            displayError(error);
        }
    };

    return (
        <form onSubmit={save} className="d-flex">
            <input type="text" className="form-control form-control-sm me-1" aria-label={s.edit} value={text}
                autoFocus onChange={(e) => setText(e.target.value)} />
            <button type="submit" className="btn btn-primary btn-sm me-1">{s.save}</button>
            <button type="button" className="btn btn-secondary btn-sm" onClick={() => setEditing(false)}>{s.cancel}</button>
        </form>
    );
}

/**
 * Table of all answers.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function OverviewView({page, answersVersion, refresh}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', ['delete_single_answer_dialog_title', 'delete_single_answer_dialog_question']);
    const answers = useCollection<Answer>(COMPONENT, `/pages/${page.id}/answers?sort=id`, [answersVersion]);

    if (!answers) {
        return <Loading />;
    }

    return (
        <OverviewTable<Answer>
            rows={answers.items}
            offset={(answers.page - 1) * answers.pageSize}
            totalPages={answers.totalPages}
            currentPage={answers.page}
            onPageChange={answers.setPage}
            deleteTitle={s.delete_single_answer_dialog_title}
            deleteQuestion={s.delete_single_answer_dialog_question}
            onDelete={(row) => remove(COMPONENT, `/pages/${page.id}/answers/${row.id}`).then(refresh)}
            renderAnswer={(row) => <AnswerEditor key={row.id} pageid={page.id} row={row} onChanged={refresh} />}
        />
    );
}
