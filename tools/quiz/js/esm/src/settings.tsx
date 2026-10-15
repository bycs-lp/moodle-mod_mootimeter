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
 * Settings of the quiz and poll tool.
 *
 * @module     mootimetertool_quiz/settings
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useState} from 'react';
import {
    AnonymousModeSetting,
    Divider,
    Loading,
    Notification,
    NumberSetting,
    TransparentButton,
} from '@moodle/lms/mod_mootimeter/lib/components';
import {useStrings, useWebservice} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {callWs, displayException, storeSetting, wwwroot} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {VISUALIZATION_BAR, VISUALIZATION_PIE, VISUALIZATION_PILLAR} from './types';
import type {QuizData, QuizOption} from './types';

const VISUALIZATIONS = [
    {id: VISUALIZATION_PILLAR, icon: 'chart-pillar', string: 'visualization_pillar'},
    {id: VISUALIZATION_BAR, icon: 'chart-bar', string: 'visualization_bar'},
    {id: VISUALIZATION_PIE, icon: 'chart-pie', string: 'visualization_pie'},
];

type OptionEditorProps = {
    pageid: number;
    option: QuizOption;
    supportscorrect: boolean;
    placeholder: string;
    deletelabel: string;
    correctlabel: string;
    onChanged: () => void;
};

/**
 * Editor of a single answer option.
 *
 * @param {OptionEditorProps} props
 * @returns {React.JSX.Element}
 */
function OptionEditor(props: OptionEditorProps): React.JSX.Element {
    const {pageid, option, supportscorrect, placeholder, deletelabel, correctlabel, onChanged} = props;
    const [text, setText] = useState(option.text);
    const [iscorrect, setIscorrect] = useState(option.iscorrect);

    useEffect(() => setText(option.text), [option.text]);
    useEffect(() => setIscorrect(option.iscorrect), [option.iscorrect]);

    const update = (newtext: string, newiscorrect: boolean) => {
        callWs('mootimetertool_quiz_update_option', {pageid, optionid: option.id, text: newtext, iscorrect: newiscorrect})
            .then(onChanged)
            .catch(displayException);
    };

    return (
        <div className="input-with-checkbox-icon">
            {supportscorrect && (
                <div className="mootimeter-checkbox">
                    <label>
                        <input
                            type="checkbox"
                            className="mootimeter-checkbox-switch"
                            aria-label={correctlabel}
                            checked={iscorrect}
                            onChange={(e) => {
                                setIscorrect(e.target.checked);
                                update(text, e.target.checked);
                            }}
                        />
                        <span className="checkbox-icon-wrapper">
                            <i className="icon fa fa-check fa-light checkbox-icon" aria-hidden="true"></i>
                        </span>
                    </label>
                </div>
            )}
            <input
                type="text"
                className="mootimeter-input mootimeterfullwidth mootimeter-answer-options mootimeter_settings_selector"
                aria-label={placeholder}
                placeholder={placeholder}
                value={text}
                onChange={(e) => setText(e.target.value)}
                onBlur={() => text !== option.text && update(text, iscorrect)}
                onKeyDown={(e) => e.key === 'Enter' && (e.target as HTMLInputElement).blur()}
            />
            <TransparentButton
                icon="fa-close"
                title={deletelabel}
                className="mtmt-remove-answeroption"
                onClick={() => {
                    callWs('mootimetertool_quiz_delete_option', {pageid, optionid: option.id})
                        .then(onChanged)
                        .catch(displayException);
                }}
            />
        </div>
    );
}

/**
 * Answer options, visualization and further settings.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function Settings({page, settingsVersion, refresh}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mootimetertool_quiz', [
        'answeroptions',
        'mark_correct_answer',
        'enter_answeroption',
        'add_question_option',
        'visualization',
        'visualization_pillar',
        'visualization_bar',
        'visualization_pie',
        'answers_max_number',
        'correct_answer',
    ]);
    const c = useStrings('core', ['settings', 'delete']);
    const data = useWebservice<QuizData>('mootimetertool_quiz_get_page_data', {pageid: page.id}, [page.id, settingsVersion]);

    if (!data) {
        return <Loading />;
    }

    const addOption = () => {
        callWs('mootimetertool_quiz_add_option', {pageid: page.id}).then(refresh).catch(displayException);
    };

    const setVisualization = (id: number) => {
        storeSetting(page.id, 'visualizationtype', id).then(refresh).catch(displayException);
    };

    return (
        <>
            <p className="text-bold">{s.answeroptions}</p>

            {data.supportscorrect && (
                <Notification type="info" icon="fa-share fa-flip-vertical" iconclass="mootimeter_rotate_110"
                    text={s.mark_correct_answer} />
            )}

            <div className="input-with-checkbox-icon_wrapper" id="mtmt-quiz-ao-wrapper">
                {data.options.map((option) => (
                    <OptionEditor
                        key={option.id}
                        pageid={page.id}
                        option={option}
                        supportscorrect={data.supportscorrect}
                        placeholder={s.enter_answeroption}
                        deletelabel={c.delete}
                        correctlabel={s.correct_answer}
                        onChanged={refresh}
                    />
                ))}
            </div>

            <div className="button-icon-with-text">
                <TransparentButton icon="fa-plus" title={s.add_question_option} onClick={addOption} />
                <p>{s.add_question_option}</p>
            </div>

            <Divider />

            <p className="text-bold">{s.visualization}</p>
            <div className="mootimeter-visualization-options-wrapper">
                {VISUALIZATIONS.map((visualization) => (
                    <div className="mootimeter-settings-visualization-option" key={visualization.id}>
                        <button
                            type="button"
                            className={`mootimeter-btn mootimeter-btn-icon mtmt_visualization_selector${
                                data.visualization === visualization.id ? ' active' : ''}`}
                            title={s[visualization.string]}
                            aria-label={s[visualization.string]}
                            aria-pressed={data.visualization === visualization.id}
                            onClick={() => setVisualization(visualization.id)}
                        >
                            <img
                                src={`${wwwroot}/mod/mootimeter/tools/quiz/pix/${visualization.icon}.svg`}
                                width="24px"
                                className="m-1"
                                alt=""
                            />
                        </button>
                    </div>
                ))}
            </div>

            <Divider />

            <p className="text-bold">{c.settings}</p>
            <NumberSetting
                pageid={page.id}
                name="maxanswersperuser"
                value={data.maxanswers}
                label={s.answers_max_number}
                onStored={refresh}
            />
            <AnonymousModeSetting page={page} onStored={refresh} />
        </>
    );
}
