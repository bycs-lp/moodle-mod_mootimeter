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
import {Loading, OverviewTable, TransparentButton} from '@moodle/lms/mod_mootimeter/lib/components';
import type {OverviewRow} from '@moodle/lms/mod_mootimeter/lib/components';
import {useStrings, useWebservice} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {callWs, displayException} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';

type Row = OverviewRow & {
    text: string;
};

type AnswerEditorProps = {
    pageid: number;
    row: Row;
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
            await callWs('mootimetertool_wordcloud_update_answer', {pageid, answerid: row.answerid, text});
            setEditing(false);
            onChanged();
        } catch (error) {
            displayException(error);
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
    const overview = useWebservice<{anonymous: boolean; rows: Row[]}>(
        'mootimetertool_wordcloud_get_overview',
        {pageid: page.id},
        [page.id, answersVersion]
    );

    if (!overview) {
        return <Loading />;
    }

    return (
        <OverviewTable<Row>
            rows={overview.rows}
            deleteTitle={s.delete_single_answer_dialog_title}
            deleteQuestion={s.delete_single_answer_dialog_question}
            onDelete={(row) => callWs('mod_mootimeter_delete_answers', {pageid: page.id, scope: 'single', answerid: row.answerid})
                .then(refresh)}
            renderAnswer={(row) => <AnswerEditor pageid={page.id} row={row} onChanged={refresh} />}
        />
    );
}
