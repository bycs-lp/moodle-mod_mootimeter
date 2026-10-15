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
 * Sortable list of the pages of a mootimeter instance.
 *
 * @module     mod_mootimeter/pagelist
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useEffect, useRef, useState} from 'react';
import {useStrings} from './lib/hooks';
import type {PageListItem} from './lib/types';

const LONGPRESS_MS = 600;

/* eslint-disable no-unused-vars -- the core rule reports the parameter names of TypeScript function types. */
type PageListProps = {
    pages: PageListItem[];
    selected: number;
    isediting: boolean;
    onSelect: (pageid: number) => void;
    onAddPage: () => void;
    onMove: (pageid: number, position: number) => void;
    // Only passed for moderators.
    onToggleVisibility?: (page: PageListItem) => void;
};

/**
 * The page list in the left column.
 *
 * Pages can be moved by drag and drop or with the arrow keys on the move handle while editing.
 * Moderators toggle the visibility of a page with a long press on it.
 *
 * @param {PageListProps} props
 * @returns {React.JSX.Element}
 */
export default function PageList(props: PageListProps): React.JSX.Element {
    const {pages, selected, isediting, onSelect, onAddPage, onMove, onToggleVisibility} = props;
    const s = useStrings('mod_mootimeter', ['addpage', 'dialogue_move_pagelistelement', 'longpress_toggle_visibility']);
    const [dragged, setDragged] = useState<number | null>(null);
    const [pressing, setPressing] = useState<number | null>(null);
    const presstimer = useRef<number | undefined>(undefined);
    // Suppresses the click that follows a long press.
    const longpressed = useRef(false);

    useEffect(() => () => window.clearTimeout(presstimer.current), []);

    const startPress = (e: React.PointerEvent, page: PageListItem) => {
        if (!onToggleVisibility || e.button !== 0) {
            return;
        }
        longpressed.current = false;
        setPressing(page.id);
        presstimer.current = window.setTimeout(() => {
            longpressed.current = true;
            setPressing(null);
            onToggleVisibility(page);
        }, LONGPRESS_MS);
    };

    const cancelPress = () => {
        window.clearTimeout(presstimer.current);
        setPressing(null);
    };

    const select = (pageid: number) => {
        if (longpressed.current) {
            longpressed.current = false;
            return;
        }
        onSelect(pageid);
    };

    const moveByKey = (e: React.KeyboardEvent, page: PageListItem, index: number) => {
        e.stopPropagation();
        if (e.key === 'ArrowUp' && index > 0) {
            e.preventDefault();
            onMove(page.id, index - 1);
        } else if (e.key === 'ArrowDown' && index < pages.length - 1) {
            e.preventDefault();
            onMove(page.id, index + 1);
        }
    };

    const drop = (index: number) => {
        if (dragged !== null && pages[index]?.id !== dragged) {
            onMove(dragged, index);
        }
        setDragged(null);
    };

    return (
        <>
            <ul id="mootimeter-pages-list">
                {pages.map((page, index) => (
                    <li
                        key={page.id}
                        className={
                            'mootimeter_pages_li d-flex'
                            + (page.id === selected ? ' active' : '')
                            + (page.visible ? '' : ' pageunvisible')
                            + (page.id === pressing ? ' mootimeter-longpress' : '')
                        }
                        title={onToggleVisibility ? [page.title, s.longpress_toggle_visibility].filter(Boolean).join(' – ')
                            : page.title}
                        data-pageid={page.id}
                        draggable={isediting}
                        onDragStart={() => {
                            cancelPress();
                            setDragged(page.id);
                        }}
                        onDragOver={(e) => isediting && e.preventDefault()}
                        onDrop={(e) => {
                            e.preventDefault();
                            drop(index);
                        }}
                        onDragEnd={() => setDragged(null)}
                    >
                        {isediting && (
                            <button
                                type="button"
                                className="mootimeter_page_move"
                                title={s.dialogue_move_pagelistelement}
                                aria-label={`${s.dialogue_move_pagelistelement} ${page.number}`}
                                onKeyDown={(e) => moveByKey(e, page, index)}
                            >
                                <i className="fa fa-arrows" aria-hidden="true"></i>
                            </button>
                        )}
                        <button
                            type="button"
                            className="mootimeter_page_select"
                            aria-label={`${page.number} ${page.title}`}
                            aria-current={page.id === selected ? 'page' : undefined}
                            onClick={() => select(page.id)}
                            onPointerDown={(e) => startPress(e, page)}
                            onPointerUp={cancelPress}
                            onPointerLeave={cancelPress}
                            onPointerCancel={cancelPress}
                            onContextMenu={(e) => onToggleVisibility && e.preventDefault()}
                        >
                            <span className="mootimeter_pages_count">{page.number}</span>
                            <img src={page.iconurl} width="35px" className="m-1" alt="" />
                        </button>
                    </li>
                ))}
            </ul>
            {isediting && (
                <ul id="mootimeter-addpage-button">
                    <li className="d-flex mootimeter_addpage" title={s.addpage}>
                        <button type="button" className="mootimeter_page_select" aria-label={s.addpage} onClick={onAddPage}>
                            <i className="fa fa-plus p-1 mx-1 my-auto" aria-hidden="true"></i>
                        </button>
                    </li>
                </ul>
            )}
        </>
    );
}
