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
import {requireAsync} from '@moodle/lms/core/amd';
import config from '@moodle/lms/core/config';
import {getString} from '@moodle/lms/core/stringUtils';
import {patch, post, remove} from '@moodle/lms/mod_mootimeter/lib/api';
import {
    AnonymousModeSetting,
    Divider,
    Loading,
    Notification,
    NumberSetting,
    TransparentButton,
} from '@moodle/lms/mod_mootimeter/lib/components';
import {useResource, useStrings} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {addToast, displayError} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {PageInfo, ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {componentOf, type QuizData, type QuizOption, type Visualization} from './types';

const VISUALIZATIONS: {id: Visualization; icon: string; string: string}[] = [
    {id: 'pillar', icon: 'chart-pillar', string: 'visualization_pillar'},
    {id: 'bar', icon: 'chart-bar', string: 'visualization_bar'},
    {id: 'pie', icon: 'chart-pie', string: 'visualization_pie'},
];

type ModalForm = {
    events: {FORM_SUBMITTED: string};
    addEventListener: (type: string, listener: (e: CustomEvent<{count: number}>) => void) => void;
    show: () => void;
};

type ModalFormModule = new (config: Record<string, unknown>) => ModalForm;

type OptionEditorProps = {
    page: PageInfo;
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
    const {page, option, supportscorrect, placeholder, deletelabel, correctlabel, onChanged} = props;
    const [text, setText] = useState(option.text);
    const [iscorrect, setIscorrect] = useState(option.isCorrect);
    const path = `/pages/${page.id}/options/${option.id}`;

    useEffect(() => setText(option.text), [option.text]);
    useEffect(() => setIscorrect(option.isCorrect), [option.isCorrect]);

    const update = (body: {text?: string; isCorrect?: boolean}) => {
        patch(componentOf(page), path, body).then(onChanged).catch(displayError);
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
                                update({isCorrect: e.target.checked});
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
                onBlur={() => text !== option.text && update({text})}
                onKeyDown={(e) => e.key === 'Enter' && (e.target as HTMLInputElement).blur()}
            />
            <TransparentButton
                icon="fa-close"
                title={deletelabel}
                className="mtmt-remove-answeroption"
                onClick={() => {
                    remove(componentOf(page), path).then(onChanged).catch(displayError);
                }}
            />
        </div>
    );
}

type ImportOptionsProps = {
    page: PageInfo;
    onImported: () => void;
};

/**
 * Opens the modal form that imports answer options from a CSV file.
 *
 * @param {ImportOptionsProps} props
 * @returns {React.JSX.Element}
 */
function ImportOptions({page, onImported}: ImportOptionsProps): React.JSX.Element {
    const s = useStrings('mootimetertool_quiz', ['import_options', 'import_options_submit']);

    const openForm = async() => {
        try {
            const ModalFormClass = await requireAsync<ModalFormModule>('core_form/modalform');
            const form = new ModalFormClass({
                formClass: 'mootimetertool_quiz\\form\\import_options',
                args: {pageid: page.id},
                modalConfig: {title: s.import_options},
                saveButtonText: s.import_options_submit,
            });
            form.addEventListener(form.events.FORM_SUBMITTED, async(e: CustomEvent<{count: number}>) => {
                addToast(await getString('import_options_success', 'mootimetertool_quiz', e.detail.count));
                onImported();
            });
            form.show();
        } catch (error) {
            displayError(error);
        }
    };

    return (
        <div className="button-icon-with-text" onClick={openForm}>
            <TransparentButton icon="fa-file-csv" title={s.import_options} className="mtmt-import-options" />
            <p>{s.import_options}</p>
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
    const data = useResource<QuizData>(componentOf(page), `/pages/${page.id}`, [settingsVersion]);

    if (!data) {
        return <Loading />;
    }

    const save = (body: Record<string, unknown>) => patch(componentOf(page), `/pages/${page.id}`, body).then(refresh);

    const addOption = () => {
        post(componentOf(page), `/pages/${page.id}/options`, {}).then(refresh).catch(displayError);
    };

    const setVisualization = (visualization: Visualization) => {
        save({visualization}).catch(displayError);
    };

    return (
        <>
            <p className="text-bold">{s.answeroptions}</p>

            {data.supportsCorrectOptions && (
                <Notification type="info" icon="fa-share fa-flip-vertical" iconclass="mootimeter_rotate_110"
                    text={s.mark_correct_answer} />
            )}

            <div className="input-with-checkbox-icon_wrapper" id="mtmt-quiz-ao-wrapper">
                {data.options.map((option) => (
                    <OptionEditor
                        key={option.id}
                        page={page}
                        option={option}
                        supportscorrect={data.supportsCorrectOptions}
                        placeholder={s.enter_answeroption}
                        deletelabel={c.delete}
                        correctlabel={s.correct_answer}
                        onChanged={refresh}
                    />
                ))}
            </div>

            <div className="button-icon-with-text" onClick={addOption}>
                <TransparentButton icon="fa-plus" title={s.add_question_option} />
                <p>{s.add_question_option}</p>
            </div>
            <ImportOptions page={page} onImported={refresh} />

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
                                src={`${config.wwwroot}/mod/mootimeter/tools/quiz/pix/${visualization.icon}.svg`}
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
                value={data.maxAnswers}
                label={s.answers_max_number}
                onSave={(maxAnswers) => save({maxAnswers})}
            />
            <AnonymousModeSetting page={page} onStored={refresh} />
        </>
    );
}
