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
 * Question view of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/content
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useState} from 'react';
import {Loading, Notification, QuestionHeader} from '@moodle/lms/mod_mootimeter/lib/components';
import {useStrings, useWebservice} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {callWs, confirmDelete, displayException} from '@moodle/lms/mod_mootimeter/lib/moodle';
import {CODE_OK} from '@moodle/lms/mod_mootimeter/lib/types';
import type {StoreResult, ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';

type WordcloudData = {
    answers: {id: number; text: string}[];
    maxinputsperuser: number;
    allowduplicateanswers: boolean;
};

/**
 * Lets participants submit words and shows their own submissions.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function Content({page, settingsVersion, answersVersion, refresh}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mootimetertool_wordcloud', ['submit', 'type_answer']);
    const m = useStrings('mod_mootimeter', ['delete_single_answer_dialog_title', 'delete_single_answer_dialog_question']);
    const c = useStrings('core', ['delete']);
    const data = useWebservice<WordcloudData>(
        'mootimetertool_wordcloud_get_page_data',
        {pageid: page.id},
        [page.id, settingsVersion, answersVersion]
    );
    const [answer, setAnswer] = useState('');
    const [result, setResult] = useState<StoreResult | null>(null);

    if (!data) {
        return <Loading />;
    }

    const submit = async(e: React.FormEvent) => {
        e.preventDefault();
        try {
            const response = await callWs<StoreResult>('mootimetertool_wordcloud_store_answer', {pageid: page.id, answer});
            setResult(response);
            if (response.code === CODE_OK) {
                setAnswer('');
                refresh();
            }
        } catch (error) {
            displayException(error);
        }
    };

    const remove = async(answerid: number) => {
        if (await confirmDelete(m.delete_single_answer_dialog_title, m.delete_single_answer_dialog_question)) {
            callWs('mod_mootimeter_delete_answers', {pageid: page.id, scope: 'single', answerid})
                .then(refresh)
                .catch(displayException);
        }
    };

    return (
        <div className="mootimeter-colcontent-preview mootimeter-width-s">
            <QuestionHeader page={page}>
                {result && result.code !== CODE_OK && (
                    <Notification type="warning" icon="fa-exclamation" text={result.message} />
                )}
            </QuestionHeader>

            <div className="filter_mathjaxloader_equation" id="mtmt_wordcloud_pills">
                {data.answers.map((item) => (
                    <div className="mootimeter-pill mootimeter-pill-inline" key={item.id}>
                        <span>
                            {item.text}
                            <button
                                type="button"
                                className="btn btn-link p-0 ms-1"
                                title={c.delete}
                                aria-label={`${c.delete}: ${item.text}`}
                                onClick={() => remove(item.id)}
                            >
                                <i className="fa fa-times fa-light" aria-hidden="true"></i>
                            </button>
                        </span>
                    </div>
                ))}
            </div>

            <form onSubmit={submit} className="d-flex flex-column gap-3">
                <input
                    type="text"
                    className="mootimeter-input mootimeterfullwidth mtmt-wc-answerinput"
                    aria-label={s.type_answer}
                    placeholder={s.type_answer}
                    value={answer}
                    autoFocus
                    onChange={(e) => {
                        setAnswer(e.target.value);
                        setResult(null);
                    }}
                />
                <button type="submit" className="mootimeter-btn mootimeter-btn-full">{s.submit}</button>
            </form>
        </div>
    );
}
