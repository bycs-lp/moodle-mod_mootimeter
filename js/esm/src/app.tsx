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
 * The mootimeter application.
 *
 * @module     mod_mootimeter/app
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, {useCallback, useEffect, useState} from 'react';
import AddPage from './addpage';
import ContentMenu from './contentmenu';
import {Loading} from './lib/components';
import {useStrings} from './lib/hooks';
import {addToast, callWs, displayException, getString} from './lib/moodle';
import type {AppProps, ChangeState, PageInfo, PageListItem, ToolInfo, ToolViewProps, View} from './lib/types';
import PageList from './pagelist';
import SettingsPanel from './settingspanel';
import ToolComponent from './toolcomponent';

const MIN_REFRESH_INTERVAL = 1000;

/**
 * Write the navigation state to the URL, so that a reload shows the same view.
 *
 * @param {number} pageid
 * @param {View} view
 * @param {boolean} fullscreen
 */
const updateUrl = (pageid: number, view: View, fullscreen: boolean) => {
    const url = new URL(window.location.href);
    const params: Record<string, string | null> = {
        pageid: pageid ? String(pageid) : null,
        r: view === 'results' ? '1' : null,
        o: view === 'overview' ? '1' : null,
        a: view === 'addpage' ? 'addpage' : null,
        f: fullscreen ? '1' : null,
    };
    Object.entries(params).forEach(([name, value]) => {
        if (value === null) {
            url.searchParams.delete(name);
        } else {
            url.searchParams.set(name, value);
        }
    });
    window.history.replaceState(null, '', url);
};

/**
 * Whether two change states are equal.
 *
 * @param {ChangeState} a
 * @param {ChangeState} b
 * @returns {boolean}
 */
const sameState = (a: ChangeState, b: ChangeState): boolean =>
    a.pagelist === b.pagelist && a.settings === b.settings && a.answers === b.answers;

/**
 * Whether the selected page has been hidden from a participant.
 *
 * @param {boolean} ismoderator
 * @param {number} pageid
 * @param {PageListItem[]|null} pages
 * @returns {boolean}
 */
const isLocked = (ismoderator: boolean, pageid: number, pages: PageListItem[] | null): boolean =>
    !ismoderator && pageid > 0 && pages !== null && !pages.some((p) => p.id === pageid);

/**
 * Whether the settings column and the strip to unfold it are shown.
 *
 * @param {boolean} cansettings whether the current view has settings for the user
 * @param {boolean} isediting
 * @param {boolean} settingsopen whether the settings were unfolded outside the editing mode
 * @param {boolean} fullscreen
 * @returns {{show: boolean, toggle: boolean}}
 */
const settingsVisibility = (cansettings: boolean, isediting: boolean, settingsopen: boolean, fullscreen: boolean) => ({
    show: cansettings && (isediting || settingsopen),
    toggle: cansettings && !isediting && !fullscreen,
});

/* eslint-disable no-unused-vars -- the core rule reports the parameter names of TypeScript function types. */
type ContentProps = {
    pages: PageListItem[] | null;
    tools: ToolInfo[];
    pageid: number;
    view: View;
    ismoderator: boolean;
    locked: boolean;
    toolprops: ToolViewProps | null;
    onAddPage: (tool: string) => void;
};

/**
 * The content column below the content menu.
 *
 * @param {ContentProps} props
 * @returns {React.JSX.Element}
 */
function Content({pages, tools, pageid, view, ismoderator, locked, toolprops, onAddPage}: ContentProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', ['please_select_a_page', 'no_pages_header', 'pagelocked']);

    if (!pages) {
        return <Loading />;
    }
    if (ismoderator && (view === 'addpage' || !pageid)) {
        return <AddPage tools={tools} onAdd={onAddPage} />;
    }
    if (!pageid || locked) {
        let message = pages.length ? s.please_select_a_page : s.no_pages_header;
        if (locked) {
            message = s.pagelocked;
        }
        return (
            <div className="mootimeter-colcontent-preview mootimeter-width-s">
                <h4>{message}</h4>
            </div>
        );
    }
    if (!toolprops) {
        return <Loading />;
    }
    let specifier = toolprops.page.components.content;
    if (view === 'results') {
        specifier = toolprops.page.components.results;
    } else if (view === 'overview' && ismoderator) {
        specifier = toolprops.page.components.overview;
    }
    return <ToolComponent key={`${specifier}-${pageid}`} specifier={specifier} {...toolprops} />;
}

type SettingsToggleProps = {
    open: boolean;
    onToggle: () => void;
};

/**
 * Narrow strip that unfolds the settings column outside the editing mode.
 *
 * @param {SettingsToggleProps} props
 * @returns {React.JSX.Element}
 */
function SettingsToggle({open, onToggle}: SettingsToggleProps): React.JSX.Element {
    const s = useStrings('core', ['settings', 'showsettings', 'hidesettings']);
    const title = open ? s.hidesettings : s.showsettings;

    return (
        <button
            type="button"
            className="mootimeter-settings-toggle"
            title={title}
            aria-label={title}
            aria-expanded={open}
            aria-controls="mootimeter-col-settings"
            onClick={onToggle}
        >
            <i className="icon fa fa-gear fa-light" aria-hidden="true"></i>
            <span className="mootimeter-settings-toggle-label">{s.settings}</span>
            <i className={`icon fa ${open ? 'fa-angle-double-left' : 'fa-angle-double-right'} fa-light`} aria-hidden="true"></i>
        </button>
    );
}


/**
 * Root component, mounted by view.php.
 *
 * @param {AppProps} props
 * @returns {React.JSX.Element}
 */
export default function App(props: AppProps): React.JSX.Element {
    const {cmid, ismoderator} = props;

    const [pages, setPages] = useState<PageListItem[] | null>(null);
    const [tools, setTools] = useState<ToolInfo[]>([]);
    const [isediting, setIsediting] = useState(props.isediting);
    const [pageid, setPageid] = useState(props.pageid);
    const [view, setView] = useState<View>(props.view);
    const [fullscreen, setFullscreen] = useState(props.fullscreen);
    // Outside the editing mode moderators can unfold the settings without switching the mode.
    const [settingsopen, setSettingsopen] = useState(false);
    const [page, setPage] = useState<PageInfo | null>(null);
    const [state, setState] = useState<ChangeState>({pagelist: 0, settings: 0, answers: 0});
    // Incremented by own actions so that the data is reloaded immediately.
    const [localversion, setLocalversion] = useState(0);
    const [pagesreload, setPagesreload] = useState(0);

    const refresh = useCallback(() => setLocalversion((version) => version + 1), []);

    useEffect(() => updateUrl(pageid, view, fullscreen), [pageid, view, fullscreen]);

    useEffect(() => {
        let active = true;
        let timer: number | undefined;
        const interval = Math.max(MIN_REFRESH_INTERVAL, props.refreshinterval || 0);
        const poll = async() => {
            if (!document.hidden) {
                try {
                    const newstate = await callWs<ChangeState>('mod_mootimeter_get_state', {cmid, pageid});
                    if (active) {
                        setState((old) => (sameState(old, newstate) ? old : newstate));
                    }
                } catch (error) {
                    window.console.error(error);
                }
            }
            if (active) {
                timer = window.setTimeout(poll, interval);
            }
        };
        poll();
        return () => {
            active = false;
            window.clearTimeout(timer);
        };
    }, [cmid, pageid, props.refreshinterval]);

    useEffect(() => {
        let active = true;
        const load = async() => {
            try {
                const result = await callWs<{pages: PageListItem[]; tools: ToolInfo[]}>('mod_mootimeter_get_pages', {cmid});
                if (active) {
                    setPages(result.pages);
                    setTools(result.tools);
                }
            } catch (error) {
                displayException(error);
            }
        };
        load();
        return () => {
            active = false;
        };
    }, [cmid, state.pagelist, localversion, pagesreload]);

    // Select another page if the selected one disappeared, or the only one if nothing is selected.
    // Participants keep a page the teacher has hidden and see a notice until it is shown again.
    useEffect(() => {
        if (!pages) {
            return;
        }
        if (ismoderator && pageid && !pages.some((p) => p.id === pageid)) {
            setPageid(pages.length ? pages[0].id : 0);
            setView('question');
        } else if (!pageid && pages.length === 1 && view !== 'addpage') {
            setPageid(pages[0].id);
        }
    }, [pages, pageid, view, ismoderator]);

    const locked = isLocked(ismoderator, pageid, pages);

    const pageversion = `${state.settings}:${ismoderator ? state.answers : 0}:${localversion}`;
    useEffect(() => {
        if (!pageid || locked) {
            setPage(null);
            return undefined;
        }
        let active = true;
        callWs<PageInfo>('mod_mootimeter_get_page', {pageid})
            .then((result) => active && setPage(result))
            .catch((error) => {
                // Most likely the page has been deleted or hidden, the reloaded page list selects another one.
                if (active) {
                    if (error?.errorcode !== 'pagelocked') {
                        window.console.error(error);
                    }
                    setPage(null);
                    setPagesreload((count) => count + 1);
                }
            });
        return () => {
            active = false;
        };
    }, [pageid, pageversion, locked]);

    const selectPage = (id: number) => {
        setPageid(id);
        setView('question');
    };

    const addPage = async(tool: string) => {
        try {
            const {pageid: newpageid} = await callWs<{pageid: number}>('mod_mootimeter_add_page', {cmid, tool});
            const result = await callWs<{pages: PageListItem[]; tools: ToolInfo[]}>('mod_mootimeter_get_pages', {cmid});
            setPages(result.pages);
            setTools(result.tools);
            // The server switched into the editing mode, so that the new page can be configured.
            setIsediting(true);
            const editswitch = document.querySelector<HTMLInputElement>('.editmode-switch-form input[name="setmode"]');
            if (editswitch) {
                editswitch.checked = true;
            }
            setPageid(newpageid);
            setView('question');
        } catch (error) {
            displayException(error);
        }
    };

    const movePage = (id: number, position: number) => {
        if (pages) {
            const reordered = pages.filter((p) => p.id !== id);
            reordered.splice(position, 0, pages.find((p) => p.id === id) as PageListItem);
            setPages(reordered.map((p, index) => ({...p, number: index + 1})));
        }
        callWs('mod_mootimeter_reorder_page', {pageid: id, position}).then(refresh).catch(displayException);
    };

    const toggleVisibility = async(item: PageListItem) => {
        try {
            const {visible} = await callWs<{visible: boolean}>(
                'mod_mootimeter_set_page_visibility',
                {pageid: item.id, visible: !item.visible}
            );
            refresh();
            addToast(await getString(visible ? 'page_shown' : 'page_hidden', 'mod_mootimeter', item.number));
        } catch (error) {
            displayException(error);
        }
    };

    const currentpage = page?.id === pageid && !locked ? page : null;
    const toolprops: ToolViewProps | null = currentpage ? {
        page: currentpage,
        ismoderator,
        isediting,
        settingsVersion: `${state.settings}:${localversion}`,
        answersVersion: `${state.answers}:${localversion}`,
        refresh,
    } : null;

    const cansettings = ismoderator && toolprops !== null && view !== 'addpage';
    const settings = settingsVisibility(cansettings, isediting, settingsopen, fullscreen);
    const showcontentmenu = toolprops && !(ismoderator && view === 'addpage');

    return (
        <div
            className={`container-fluid px-0 mootimetercontainer isNotNewPage${fullscreen ? ' fullscreen' : ''}`}
            id="mootimeter-main-container"
        >
            <div className="row g-0">
                <div id="mootimeter-col-left" className={settings.show ? '' : 'mootimeter-pagesonly'}>
                    <div className="mootimetercol mootimetercolpages" id="mootimeter-pages-col">
                        {pages ? (
                            <PageList
                                pages={pages}
                                selected={pageid}
                                isediting={ismoderator && isediting}
                                onSelect={selectPage}
                                onAddPage={() => setView('addpage')}
                                onMove={movePage}
                                onToggleVisibility={ismoderator ? toggleVisibility : undefined}
                            />
                        ) : <Loading />}
                    </div>
                    {settings.show && toolprops && (
                        <div className="mootimetercol mootimetercoledit" id="mootimeter-col-settings">
                            <SettingsPanel
                                key={`settings-${pageid}`}
                                {...toolprops}
                                onDeleted={() => {
                                    const remaining = (pages ?? []).filter((p) => p.id !== pageid);
                                    setPages(remaining);
                                    setPageid(remaining.length ? remaining[0].id : 0);
                                    setView('question');
                                    setPagesreload((count) => count + 1);
                                }}
                            />
                        </div>
                    )}
                    {settings.toggle && (
                        <SettingsToggle open={settingsopen} onToggle={() => setSettingsopen(!settingsopen)} />
                    )}
                </div>
                <div className="mootimetercol mootimetercolcontent">
                    {showcontentmenu && toolprops && (
                        <ContentMenu
                            {...toolprops}
                            view={view}
                            fullscreen={fullscreen}
                            onView={setView}
                            onFullscreen={setFullscreen}
                        />
                    )}
                    <div className="mootimeter-content-wrapper" id="mootimeter-pagecontent">
                        <Content
                            pages={pages}
                            tools={tools}
                            pageid={pageid}
                            view={view}
                            ismoderator={ismoderator}
                            locked={locked}
                            toolprops={toolprops}
                            onAddPage={addPage}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
