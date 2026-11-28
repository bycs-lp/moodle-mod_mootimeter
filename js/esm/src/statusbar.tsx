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
 * Status bar above the page content: countdown and live participation.
 *
 * @module     mod_mootimeter/statusbar
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React from 'react';
import {useCountdown, useStrings} from './lib/hooks';
import type {GroupInfo, PageInfo, Participation} from './lib/types';

// Remaining seconds at which screen readers announce the countdown.
const ANNOUNCEMENTS = [60, 30, 10, 0];

/**
 * Replace the placeholders {$a} and {$a->name} of a language string.
 *
 * @param {string} text
 * @param {Record<string, number|string>|number|string} values
 * @returns {string}
 */
const fill = (text: string, values: Record<string, number | string> | number | string): string => {
    if (typeof values !== 'object') {
        return text.replace('{$a}', String(values));
    }
    return Object.entries(values).reduce((result, [name, value]) => result.replace(`{$a->${name}}`, String(value)), text);
};

/**
 * Format seconds as m:ss or h:mm:ss.
 *
 * @param {number} seconds
 * @returns {string}
 */
export const formatDuration = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const rest = String(seconds % 60).padStart(2, '0');
    return hours ? `${hours}:${String(minutes).padStart(2, '0')}:${rest}` : `${minutes}:${rest}`;
};

type CountdownProps = {
    closesAt: string | null;
    serverOffset: number;
    onExpired: () => void;
};

/**
 * Remaining time of the submission. Screen readers announce it at a few thresholds only.
 *
 * @param {CountdownProps} props
 * @returns {React.JSX.Element|null}
 */
function Countdown({closesAt, serverOffset, onExpired}: CountdownProps): React.JSX.Element | null {
    const s = useStrings('mod_mootimeter', ['countdown', 'countdown_ended', 'countdown_remaining']);
    const remaining = useCountdown(closesAt, serverOffset, onExpired);

    if (remaining === null) {
        return null;
    }
    let announcement = '';
    if (ANNOUNCEMENTS.includes(remaining)) {
        announcement = remaining === 0 ? s.countdown_ended : fill(s.countdown_remaining, formatDuration(remaining));
    }

    return (
        <div className={`mootimeter-countdown${remaining <= 10 ? ' mootimeter-countdown-ending' : ''}`}>
            <i className="icon fa fa-clock-o fa-light" aria-hidden="true"></i>
            <span className="visually-hidden">{s.countdown}</span>
            <span aria-hidden="true">{formatDuration(remaining)}</span>
            <span className="visually-hidden" aria-live="polite">{announcement}</span>
        </div>
    );
}

type CounterProps = {
    participation: Participation;
    groups: GroupInfo[];
    allowAllGroups: boolean;
    onGroup: (groupId: number) => void;
};

/**
 * Live counter of the participants who answered, with the choice of the group.
 *
 * @param {CounterProps} props
 * @returns {React.JSX.Element}
 */
function ParticipationCounter({participation, groups, allowAllGroups, onGroup}: CounterProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', [
        'participation', 'participation_answered', 'participation_enrolled', 'participation_enrolled_group',
    ]);
    const c = useStrings('core', ['allparticipants', 'group']);
    const {answered, active, enrolled, groupId} = participation;
    const percent = active ? Math.min(100, Math.round((answered / active) * 100)) : 0;
    const options = [...(allowAllGroups ? [{id: 0, name: c.allparticipants}] : []), ...groups];

    return (
        <div className="mootimeter-participation" role="status" aria-label={s.participation}>
            {options.length > 1 && (
                <select
                    className="form-select form-select-sm w-auto"
                    aria-label={c.group}
                    value={groupId}
                    onChange={(e) => onGroup(Number(e.target.value))}
                >
                    {options.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}
                </select>
            )}
            <span>{fill(s.participation_answered, {answered, active})}</span>
            <span className="mootimeter-participation-enrolled">
                · {fill(groupId ? s.participation_enrolled_group : s.participation_enrolled, enrolled)}
            </span>
            <div className="progress mootimeter-participation-progress" aria-hidden="true">
                <div className="progress-bar" style={{width: `${percent}%`}}></div>
            </div>
        </div>
    );
}

type StatusBarProps = {
    page: PageInfo;
    isModerator: boolean;
    serverOffset: number;
    participation: Participation | null;
    groupMode: number;
    groups: GroupInfo[];
    allowAllGroups: boolean;
    onGroup: (groupId: number) => void;
    onExpired: () => void;
};

// Moodle group mode with separate groups.
const SEPARATEGROUPS = 1;

/**
 * Countdown and participation of the page shown.
 *
 * @param {StatusBarProps} props
 * @returns {React.JSX.Element|null}
 */
export default function StatusBar(props: StatusBarProps): React.JSX.Element | null {
    const {page, isModerator, serverOffset, participation, groupMode, groups, allowAllGroups, onGroup, onExpired} = props;
    const s = useStrings('mod_mootimeter', ['participation_nogroup']);
    const withoutgroup = !isModerator && page.participationVisible && groupMode === SEPARATEGROUPS
        && !allowAllGroups && groups.length === 0;
    const showcounter = Boolean(participation?.available);

    if (!page.closesAt && !showcounter && !withoutgroup) {
        return null;
    }
    return (
        <div className="mootimeter-statusbar">
            <Countdown closesAt={page.closesAt} serverOffset={serverOffset} onExpired={onExpired} />
            {showcounter && participation && (
                <ParticipationCounter
                    participation={participation}
                    groups={groups}
                    allowAllGroups={allowAllGroups}
                    onGroup={onGroup}
                />
            )}
            {withoutgroup && <span className="mootimeter-participation">{s.participation_nogroup}</span>}
        </div>
    );
}
