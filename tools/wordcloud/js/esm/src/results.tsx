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
 * Wordcloud of the counted answers, laid out with d3-cloud.
 *
 * @module     mootimetertool_wordcloud/results
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useRef, useState} from 'react';
import {getString} from '@moodle/lms/core/stringUtils';
import {Loading} from '@moodle/lms/mod_mootimeter/lib/components';
import {useResource, useStrings} from '@moodle/lms/mod_mootimeter/lib/hooks';
import {notifyFilterContentUpdated} from '@moodle/lms/mod_mootimeter/lib/moodle';
import type {ToolViewProps} from '@moodle/lms/mod_mootimeter/lib/types';
import {layoutCloud, type PlacedWord as PlacedCloudWord} from './cloudlayout';
import {COMPONENT, type Word, type WordcloudResults} from './types';

const MIN_FONT_SIZE = 16;
const RATIO = 4;
const FILL_TARGET = 0.65;
const FREQUENCY_EXPONENT = 0.8;
const CHAR_WIDTH = 0.6;
const FIT_CHAR_WIDTH = 0.65;
const DENSITY_DIVISOR = 3;
const MAX_SHRINK_ATTEMPTS = 3;
const SHRINK_STEP = 0.85;
const FONT_FAMILY = 'Lexend, sans-serif';
const COLORS = ['#c0580a', '#1a5fa8', '#b33000', '#2e7d32', '#6b3fa0', '#c2185b'];

type PlacedWord = PlacedCloudWord<Word & {size: number; rotate: number}>;

type Layout = {width: number; height: number; words: PlacedWord[]};

/**
 * Lay out the words. Words that do not fit are retried with smaller font sizes.
 *
 * @param {Word[]} answers
 * @param {number} width
 * @param {number} height
 * @returns {{promise: Promise<PlacedWord[]>, stop: Function}}
 */
const layoutWords = (answers: Word[], width: number, height: number) => {
    const sorted = [...answers].sort((a, b) => b.count - a.count);
    const maxcount = sorted[0].count;
    const mincount = sorted[sorted.length - 1].count;
    const spread = maxcount - mincount;
    // One word must not fill the whole canvas when there are only a few words.
    const densitycap = Math.min(width, height) / DENSITY_DIVISOR;

    const words = sorted.map(({text, count}) => {
        const frequency = spread > 0 ? (count - mincount) / spread : 0;
        return {
            text,
            count,
            multiplier: 1 + (RATIO - 1) * Math.pow(frequency, FREQUENCY_EXPONENT),
            rotate: Math.random() > 0.8 ? -90 : 0,
        };
    });
    const space = words.reduce((sum, word) => sum + word.text.length * CHAR_WIDTH * word.multiplier * word.multiplier, 0);
    const basesize = Math.max(MIN_FONT_SIZE, Math.sqrt(FILL_TARGET * width * height / space));

    let running: {stop: () => void} | null = null;
    const state = {stopped: false};
    const promise = (async() => {
        let placed: PlacedWord[] = [];
        for (let attempt = 0, shrink = 1; attempt <= MAX_SHRINK_ATTEMPTS && !state.stopped; attempt++, shrink *= SHRINK_STEP) {
            const cloudwords = words.map(({text, count, multiplier, rotate}) => {
                // A rotated word spans the height, so it must fit along that axis.
                const lengthcap = (rotate === 0 ? width : height) / (text.length * FIT_CHAR_WIDTH);
                return {text, count, rotate, size: Math.min(basesize * multiplier, densitycap, lengthcap) * shrink};
            });
            const layout = layoutCloud(cloudwords, {width, height, font: FONT_FAMILY, padding: 4});
            running = layout;
            placed = await layout.promise;
            // Words that do not fit are dropped by the layout.
            if (placed.length === words.length) {
                break;
            }
        }
        return placed;
    })();

    return {
        promise,
        stop: () => {
            state.stopped = true;
            running?.stop();
        },
    };
};

/**
 * The wordcloud, updated live.
 *
 * @param {ToolViewProps} props
 * @returns {React.JSX.Element}
 */
export default function ResultsView({page, settingsVersion, answersVersion}: ToolViewProps): React.JSX.Element {
    const s = useStrings('mootimetertool_wordcloud', ['no_answer_due_to_showteacherpermission', 'no_answer']);
    const results = useResource<WordcloudResults>(COMPONENT, `/pages/${page.id}/results`, [settingsVersion, answersVersion]);
    const container = useRef<HTMLDivElement>(null);
    const [layout, setLayout] = useState<Layout | null>(null);
    const [arialabel, setArialabel] = useState('');

    const words = results?.available ? results.words : null;
    const wordskey = JSON.stringify(words);

    useEffect(() => {
        if (!words || !words.length || !container.current) {
            setLayout(null);
            return undefined;
        }
        const width = container.current.clientWidth;
        const height = container.current.clientHeight;
        let active = true;
        let running: {stop: () => void} | null = null;
        const draw = async() => {
            // The layout measures text in the real font, so wait for it.
            await document.fonts?.ready;
            if (!active) {
                return;
            }
            const current = layoutWords(words, width, height);
            running = current;
            const placed = await current.promise;
            if (active) {
                setLayout({width, height, words: placed});
                setArialabel(await getString('wordcloud_aria_label', 'mootimetertool_wordcloud', words.length));
            }
        };
        draw();
        return () => {
            active = false;
            running?.stop();
        };
    }, [wordskey]);

    useEffect(() => {
        notifyFilterContentUpdated(container.current);
    }, [layout]);

    if (!results) {
        return <Loading />;
    }

    if (!results.available) {
        return (
            <div className="mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl mootimeter-vertical-align">
                {s.no_answer_due_to_showteacherpermission}
            </div>
        );
    }

    const listid = `mtmt_wordlist_${page.id}`;
    return (
        <div className="mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl" ref={container}
            style={{display: 'block'}}>
            {!results.words.length && <p>{s.no_answer}</p>}
            {layout && (
                <>
                    <svg
                        width="100%"
                        viewBox={`${-layout.width / 2} ${-layout.height / 2} ${layout.width} ${layout.height}`}
                        role="img"
                        aria-label={arialabel}
                        aria-describedby={listid}
                    >
                        <g>
                            {layout.words.map((word, index) => (
                                <text
                                    key={word.text}
                                    transform={`translate(${word.x}, ${word.y})rotate(${word.rotate})`}
                                    fontFamily={FONT_FAMILY}
                                    fontSize={`${word.size}px`}
                                    textAnchor="middle"
                                    fill={COLORS[index % COLORS.length]}
                                >
                                    <title>{`${word.text}: ${word.count}`}</title>
                                    {word.text}
                                </text>
                            ))}
                        </g>
                    </svg>
                    <ul id={listid} className="sr-only">
                        {layout.words.map((word) => <li key={word.text}>{`${word.text}: ${word.count}`}</li>)}
                    </ul>
                </>
            )}
        </div>
    );
}
