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
import {Choice, FormattedHtml, Loading, OverviewTable, TransparentButton} from '@moodle/lms/mod_mootimeter/lib/components';
import type {OverviewRow} from '@moodle/lms/mod_mootimeter/lib/components';
import {useStrings, useWebservice} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {callWs, displayException} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';

type Row = OverviewRow & {
    optionids: number[];
};

type Overview = {
    anonymous: boolean;
    maxanswers: number;
    options: {id: number; texthtml: string}[];
    rows: Row[];
};

type AnswerEditorProps = {
    pageid: number;
    row: Row;
    overview: Overview;
    onChanged: () => void;
};

/**
 * Shows the selected options of a participant and lets moderators change them.
 *
 * @param {AnswerEditorProps} props
 * @returns {React.JSX.Element}
 */
function AnswerEditor({pageid, row, overview, onChanged}: AnswerEditorProps): React.JSX.Element {
    const s = useStrings('core', ['edit', 'save', 'cancel']);
    const [editing, setEditing] = useState(false);
    const [selected, setSelected] = useState<number[]>(row.optionids);
    const single = overview.maxanswers === 1;

    if (!editing) {
        const texts = overview.options.filter((option) => row.optionids.includes(option.id));
        return (
            <span>
                {texts.map((option, index) => (
                    <React.Fragment key={option.id}>
                        {index > 0 && ' | '}
                        <FormattedHtml html={option.texthtml} />
                    </React.Fragment>
                ))}
                <TransparentButton
                    icon="fa-pencil"
                    title={s.edit}
                    className="text-body"
                    onClick={() => {
                        setSelected(row.optionids);
                        setEditing(true);
                    }}
                />
            </span>
        );
    }

    const save = async() => {
        try {
            await callWs('mootimetertool_quiz_update_user_answer', {pageid, answerid: row.answerid, optionids: selected});
            setEditing(false);
            onChanged();
        } catch (error) {
            displayException(error);
        }
    };

    return (
        <div>
            {overview.options.map((option) => (
                <Choice
                    key={option.id}
                    type={single ? 'radio' : 'checkbox'}
                    name={`mtmt_quiz_overview_${row.answerid}`}
                    checked={selected.includes(option.id)}
                    label={<FormattedHtml html={option.texthtml} />}
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
    const overview = useWebservice<Overview>(
        'mootimetertool_quiz_get_overview',
        {pageid: page.id},
        [page.id, settingsVersion, answersVersion]
    );

    if (!overview) {
        return <Loading />;
    }

    return (
        <OverviewTable<Row>
            rows={overview.rows}
            deleteTitle={s.delete_answers_of_user_dialog_title}
            deleteQuestion={s.delete_answers_of_user_dialog_question}
            onDelete={(row) => callWs('mod_mootimeter_delete_answers', {pageid: page.id, scope: 'user', answerid: row.answerid})
                .then(refresh)}
            renderAnswer={(row) => <AnswerEditor pageid={page.id} row={row} overview={overview} onChanged={refresh} />}
        />
    );
}
