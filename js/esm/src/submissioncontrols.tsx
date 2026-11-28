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
 * Moderator controls of the submission: open, close and countdown.
 *
 * @module     mod_mootimeter/submissioncontrols
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useRef, useState} from 'react';
import {patch} from './lib/api';
import {IconButton} from './lib/components';
import {useStrings} from './lib/hooks';
import {displayError} from './lib/moodle';
import type {PageInfo} from './lib/types';
import {formatDuration} from './statusbar';

const PRESETS = [30, 60, 120, 300];
const EXTENSIONS = [30, 60];

type SubmissionControlsProps = {
    page: PageInfo;
    refresh: () => void;
};

/**
 * Button to open or close the submission and a menu to start, extend or stop a countdown.
 *
 * @param {SubmissionControlsProps} props
 * @returns {React.JSX.Element}
 */
export default function SubmissionControls({page, refresh}: SubmissionControlsProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', [
        'submission_open', 'submission_close', 'countdown', 'countdown_cancel', 'countdown_custom', 'countdown_extend',
        'countdown_start',
        'countdown_start_default',
    ]);
    const [open, setOpen] = useState(false);
    // The default countdown of the page is preset, the moderator can still choose another duration.
    const defaultseconds = page.countdownDefaultSeconds;
    const [custom, setCustom] = useState(defaultseconds ? String(defaultseconds) : '');

    useEffect(() => setCustom(defaultseconds ? String(defaultseconds) : ''), [defaultseconds]);
    const menu = useRef<HTMLDivElement>(null);
    const running = page.closesAt !== null;

    useEffect(() => {
        if (!open) {
            return undefined;
        }
        menu.current?.querySelector<HTMLButtonElement>('button')?.focus();
        const close = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
        document.addEventListener('keydown', close);
        return () => document.removeEventListener('keydown', close);
    }, [open]);

    const save = (body: Record<string, unknown>) => {
        setOpen(false);
        patch('mod_mootimeter', `/pages/${page.id}`, body).then(refresh).catch(displayError);
    };

    const startCustom = (e: React.FormEvent) => {
        e.preventDefault();
        const seconds = Number(custom);
        if (seconds > 0) {
            save({countdownSeconds: seconds});
        }
    };

    return (
        <>
            <IconButton
                icon={page.submissionOpen ? 'fa-unlock' : 'fa-lock'}
                title={page.submissionOpen ? s.submission_close : s.submission_open}
                active={!page.submissionOpen}
                onClick={() => save({submissionOpen: !page.submissionOpen})}
            />
            <div className="mootimeter-countdown-control">
                <IconButton icon="fa-clock-o" title={s.countdown} active={running} onClick={() => setOpen(!open)} />
                {open && (
                    <div className="mootimeter-countdown-menu" ref={menu} role="group" aria-label={s.countdown}>
                        {running ? (
                            <>
                                {EXTENSIONS.map((seconds) => (
                                    <button key={seconds} type="button" className="btn btn-sm btn-secondary"
                                        onClick={() => save({countdownExtendSeconds: seconds})}>
                                        {s.countdown_extend.replace('{$a}', formatDuration(seconds))}
                                    </button>
                                ))}
                                <button type="button" className="btn btn-sm btn-outline-danger"
                                    onClick={() => save({countdownSeconds: 0})}>
                                    {s.countdown_cancel}
                                </button>
                            </>
                        ) : (
                            <>
                                {defaultseconds > 0 && (
                                    <button type="button" className="btn btn-sm btn-primary"
                                        onClick={() => save({countdownSeconds: defaultseconds})}>
                                        {s.countdown_start_default.replace('{$a}', formatDuration(defaultseconds))}
                                    </button>
                                )}
                                {PRESETS.filter((seconds) => seconds !== defaultseconds).map((seconds) => (
                                    <button key={seconds} type="button" className="btn btn-sm btn-secondary"
                                        onClick={() => save({countdownSeconds: seconds})}>
                                        {formatDuration(seconds)}
                                    </button>
                                ))}
                                <form className="d-flex gap-1" onSubmit={startCustom}>
                                    <input type="number" min="5" className="form-control form-control-sm"
                                        aria-label={s.countdown_custom} placeholder={s.countdown_custom} value={custom}
                                        onChange={(e) => setCustom(e.target.value)} />
                                    <button type="submit" className="btn btn-sm btn-primary">{s.countdown_start}</button>
                                </form>
                            </>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
