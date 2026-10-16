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
 * UI components shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * The markup reuses the CSS classes of styles.scss.
 *
 * @module     mod_mootimeter/lib/components
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useRef, useState, ReactNode} from 'react';
import {Pagination} from '@moodlehq/design-system';
import {patch} from './api';
import {useStrings} from './hooks';
import {confirmDelete, displayError, formatUserDates, notifyFilterContentUpdated} from './moodle';
import type {PageInfo} from './types';

type IconButtonProps = {
    icon: string;
    title: string;
    onClick: () => void;
    active?: boolean;
    disabled?: boolean;
};

/**
 * Round button with an icon only, used in the content menu.
 *
 * @param {IconButtonProps} props
 * @returns {React.JSX.Element}
 */
export function IconButton({icon, title, onClick, active = false, disabled = false}: IconButtonProps): React.JSX.Element {
    return (
        <button
            type="button"
            className={`mootimeter-btn-icon-only-rounded${active ? ' active' : ''}`}
            title={title}
            aria-label={title}
            aria-pressed={active}
            disabled={disabled}
            onClick={onClick}
        >
            <i className={`icon fa ${icon} fa-light`} aria-hidden="true"></i>
        </button>
    );
}

type TransparentButtonProps = {
    icon: string;
    title: string;
    // Omitted when a surrounding element handles the click.
    onClick?: () => void;
    className?: string;
};

/**
 * Transparent button with an icon only.
 *
 * @param {TransparentButtonProps} props
 * @returns {React.JSX.Element}
 */
export function TransparentButton({icon, title, onClick, className = ''}: TransparentButtonProps): React.JSX.Element {
    return (
        <button
            type="button"
            className={`mootimeter-btn-icon-only-transparent ${className}`}
            title={title}
            aria-label={title}
            onClick={onClick}
        >
            <i className={`icon fa ${icon} fa-light`} aria-hidden="true"></i>
        </button>
    );
}

type NotificationProps = {
    type: 'success' | 'info' | 'warning' | 'danger';
    text: string;
    icon?: string;
    iconclass?: string;
};

/**
 * Inline notification.
 *
 * @param {NotificationProps} props
 * @returns {React.JSX.Element}
 */
export function Notification({type, text, icon = '', iconclass = ''}: NotificationProps): React.JSX.Element {
    return (
        <div className={`mootimeter-colcontent-preview-notification notification-${type}`} role="status">
            <span className={iconclass}><i className={`icon fa ${icon} fa-light`} aria-hidden="true"></i></span>
            <p>{text}</p>
        </div>
    );
}

type FormattedHtmlProps = {
    html: string;
    tag?: 'span' | 'div' | 'h4';
    className?: string;
};

/**
 * Render HTML that was formatted by format_string() on the server and notify the filters (e.g. MathJax).
 *
 * @param {FormattedHtmlProps} props
 * @returns {React.JSX.Element}
 */
export function FormattedHtml({html, tag = 'span', className = ''}: FormattedHtmlProps): React.JSX.Element {
    const ref = useRef<HTMLElement>(null);
    useEffect(() => {
        notifyFilterContentUpdated(ref.current);
    }, [html]);
    return React.createElement(tag, {ref, className, dangerouslySetInnerHTML: {__html: html}});
}

type QuestionHeaderProps = {
    page: PageInfo;
    children?: ReactNode;
};

/**
 * Header of a question view with the tool name and the question.
 *
 * @param {QuestionHeaderProps} props
 * @returns {React.JSX.Element}
 */
export function QuestionHeader({page, children}: QuestionHeaderProps): React.JSX.Element {
    return (
        <div className="mootimeter-colcontent-preview-header filter_mathjaxloader_equation">
            <div className="mootimeter-pill"><span>{page.toolName}</span></div>
            <FormattedHtml tag="h4" html={page.questionHtml} />
            {children}
        </div>
    );
}

type ChoiceProps = {
    type: 'checkbox' | 'radio';
    checked: boolean;
    label: ReactNode;
    onChange: (checked: boolean) => void;
    name?: string;
    disabled?: boolean;
    wrapperclass?: string;
};

/**
 * Checkbox or radio button with label.
 *
 * @param {ChoiceProps} props
 * @returns {React.JSX.Element}
 */
export function Choice(
    {type, checked, label, onChange, name, disabled = false, wrapperclass = ''}: ChoiceProps
): React.JSX.Element {
    const prefix = type === 'checkbox' ? 'mootimeter-checkbox' : 'mootimeter-radio-btn';
    const iconprefix = type === 'checkbox' ? 'checkbox' : 'radio-btn';
    return (
        <div className={`${prefix} ${wrapperclass}`}>
            <label>
                <input
                    type={type}
                    className={type === 'checkbox' ? 'mootimeter-checkbox-switch' : 'mootimeter-radio-btn'}
                    name={name}
                    checked={checked}
                    disabled={disabled}
                    onChange={(e) => onChange(e.target.checked)}
                />
                <span className={`${iconprefix}-icon-wrapper`}>
                    <i className={`icon fa fa-check fa-light ${iconprefix}-icon`} aria-hidden="true"></i>
                </span>
                <span>{label}</span>
            </label>
        </div>
    );
}

type SettingProps<T> = {
    onSave: (value: T) => Promise<unknown>;
};

type TextSettingProps = SettingProps<string> & {
    value: string;
    placeholder?: string;
    label: string;
};

/**
 * Text input that saves a setting when it loses the focus.
 *
 * @param {TextSettingProps} props
 * @returns {React.JSX.Element}
 */
export function TextSetting({value, placeholder, label, onSave}: TextSettingProps): React.JSX.Element {
    const [current, setCurrent] = useState(value);
    const focused = useRef(false);

    useEffect(() => {
        if (!focused.current) {
            setCurrent(value);
        }
    }, [value]);

    const store = () => {
        focused.current = false;
        if (current === value) {
            return;
        }
        onSave(current).catch(displayError);
    };

    return (
        <input
            type="text"
            className="mootimeter-input mootimeterfullwidth mootimeter_settings_selector"
            aria-label={label}
            placeholder={placeholder}
            value={current}
            onFocus={() => {
                focused.current = true;
            }}
            onChange={(e) => setCurrent(e.target.value)}
            onBlur={store}
            onKeyDown={(e) => e.key === 'Enter' && (e.target as HTMLInputElement).blur()}
        />
    );
}

type NumberSettingProps = SettingProps<number> & {
    value: number;
    label: string;
    min?: number;
};

/**
 * Number input with up and down buttons that saves a setting.
 *
 * @param {NumberSettingProps} props
 * @returns {React.JSX.Element}
 */
export function NumberSetting({value, label, min = 0, onSave}: NumberSettingProps): React.JSX.Element {
    const [current, setCurrent] = useState(String(value));

    useEffect(() => setCurrent(String(value)), [value]);

    const store = (newvalue: number) => {
        const cleaned = Math.max(min, Number.isFinite(newvalue) ? Math.trunc(newvalue) : min);
        setCurrent(String(cleaned));
        if (cleaned === value) {
            return;
        }
        onSave(cleaned).catch(displayError);
    };

    return (
        <div className="mootimeter-number-input">
            <label>
                <span className="mootimeter-number-input-wrapper">
                    <input
                        type="number"
                        className="mootimeter_settings_selector"
                        min={min}
                        value={current}
                        onChange={(e) => setCurrent(e.target.value)}
                        onBlur={() => store(Number(current))}
                        onKeyDown={(e) => e.key === 'Enter' && (e.target as HTMLInputElement).blur()}
                    />
                    <button
                        type="button"
                        className="mootimeter-btn-icon-only-transparent mootimeter-number-input-btn-up"
                        aria-hidden="true"
                        tabIndex={-1}
                        onMouseDown={(e) => e.preventDefault()}
                        onClick={() => store(Number(current) + 1)}
                    >
                        <i className="icon fa fa-sort-up fa-light" aria-hidden="true"></i>
                    </button>
                    <button
                        type="button"
                        className="mootimeter-btn-icon-only-transparent mootimeter-number-input-btn-down"
                        aria-hidden="true"
                        tabIndex={-1}
                        onMouseDown={(e) => e.preventDefault()}
                        onClick={() => store(Number(current) - 1)}
                    >
                        <i className="icon fa fa-sort-down fa-light" aria-hidden="true"></i>
                    </button>
                </span>
                {label}
            </label>
        </div>
    );
}

type CheckboxSettingProps = SettingProps<boolean> & {
    value: boolean;
    label: string;
    disabled?: boolean;
};

/**
 * Checkbox that saves a setting.
 *
 * @param {CheckboxSettingProps} props
 * @returns {React.JSX.Element}
 */
export function CheckboxSetting({value, label, disabled = false, onSave}: CheckboxSettingProps): React.JSX.Element {
    const [current, setCurrent] = useState(value);

    useEffect(() => setCurrent(value), [value]);

    const change = (checked: boolean) => {
        setCurrent(checked);
        onSave(checked).catch((error) => {
            setCurrent(value);
            displayError(error);
        });
    };

    return <Choice type="checkbox" checked={current} label={label} disabled={disabled} onChange={change} />;
}

type AnonymousModeSettingProps = {
    page: PageInfo;
    onStored?: () => void;
};

/**
 * The anonymous mode setting, it can not be changed once answers exist.
 *
 * @param {AnonymousModeSettingProps} props
 * @returns {React.JSX.Element}
 */
export function AnonymousModeSetting({page, onStored}: AnonymousModeSettingProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', ['anonymousmode', 'anonymousmode_desc']);
    return (
        <CheckboxSetting
            value={page.anonymousMode}
            label={`${s.anonymousmode} ${s.anonymousmode_desc}`}
            disabled={page.hasAnswers}
            onSave={(anonymousMode) => patch('mod_mootimeter', `/pages/${page.id}`, {anonymousMode}).then(() => onStored?.())}
        />
    );
}

/**
 * Horizontal divider of the settings column.
 *
 * @returns {React.JSX.Element}
 */
export function Divider(): React.JSX.Element {
    return <hr className="divider" />;
}

/**
 * Loading indicator.
 *
 * @returns {React.JSX.Element}
 */
export function Loading(): React.JSX.Element {
    const s = useStrings('mod_mootimeter', ['loading']);
    return (
        <div className="mootimeter-render-loading" role="status">
            <span className="spinner-border spinner-border-sm" aria-hidden="true"></span> {s.loading}
        </div>
    );
}

export type OverviewRow = {
    id: number;
    fullName: string;
    createdAt: string;
};

type OverviewTableProps<T extends OverviewRow> = {
    rows: T[];
    // Number of the first row, the rows of later pages continue the numbering.
    offset: number;
    totalPages: number;
    currentPage: number;
    onPageChange: (page: number) => void;
    renderAnswer: (row: T) => ReactNode;
    onDelete: (row: T) => Promise<unknown>;
    deleteTitle: string;
    deleteQuestion: string;
    className?: string;
};

/**
 * Format the creation times of the rows like the rest of Moodle.
 *
 * @param {OverviewRow[]} rows
 * @returns {Record<number, string>} formatted time by row id
 */
function useRowTimes(rows: OverviewRow[]): Record<number, string> {
    const [times, setTimes] = useState<Record<number, string>>({});
    const key = rows.map((row) => `${row.id}:${row.createdAt}`).join(',');

    useEffect(() => {
        let active = true;
        formatUserDates(rows.map((row) => Math.floor(Date.parse(row.createdAt) / 1000)))
            .then((formatted) => active && setTimes(Object.fromEntries(rows.map((row, index) => [row.id, formatted[index]]))))
            .catch(displayError);
        return () => {
            active = false;
        };
    }, [key]);

    return times;
}

/**
 * Table of the answer overview.
 *
 * @param {OverviewTableProps} props
 * @returns {React.JSX.Element}
 */
export function OverviewTable<T extends OverviewRow>(props: OverviewTableProps<T>): React.JSX.Element {
    const {rows, offset, totalPages, currentPage, onPageChange, renderAnswer, onDelete, deleteTitle, deleteQuestion} = props;
    const s = useStrings('core', ['name', 'answer', 'date', 'time', 'options', 'delete', 'nothingtodisplay', 'previous', 'next']);
    const m = useStrings('mod_mootimeter', ['show_answer_overview']);
    const times = useRowTimes(rows);

    const remove = async(row: T) => {
        if (await confirmDelete(deleteTitle, deleteQuestion)) {
            onDelete(row).catch(displayError);
        }
    };

    return (
        <div className={`mootimeter-colcontent-preview mootimeter-width-xl mootimeter-height-auto ${props.className ?? ''}`}>
            <table className="table">
                <caption className="sr-only">{m.show_answer_overview}</caption>
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{s.name}</th>
                        <th scope="col">{s.answer}</th>
                        <th scope="col">{s.date} / {s.time}</th>
                        <th scope="col">{s.options}</th>
                    </tr>
                </thead>
                <tbody>
                    {!rows.length && (
                        <tr>
                            <td colSpan={5}>{s.nothingtodisplay}</td>
                        </tr>
                    )}
                    {rows.map((row, index) => (
                        <tr key={row.id}>
                            <td>{offset + index + 1}</td>
                            <td>{row.fullName}</td>
                            <td>{renderAnswer(row)}</td>
                            <td>{times[row.id] ?? ''}</td>
                            <td>
                                <IconButton icon="fa-trash" title={s.delete} onClick={() => remove(row)} />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            {totalPages > 1 && (
                <Pagination
                    totalPages={totalPages}
                    currentPage={currentPage}
                    onPageChange={onPageChange}
                    ariaLabel={m.show_answer_overview}
                    previousPageLabel={s.previous}
                    nextPageLabel={s.next}
                />
            )}
        </div>
    );
}
