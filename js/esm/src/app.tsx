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
import {getString} from '@moodle/lms/core/stringUtils';
import AddPage from './addpage';
import ContentMenu from './contentmenu';
import {ApiError, get, getAll, patch, post} from './lib/api';
import {Loading} from './lib/components';
import {useStrings} from './lib/hooks';
import {addToast, displayError, enableEditMode} from './lib/moodle';
import type {AppProps, PageInfo, PageListItem, ToolInfo, ToolViewProps, Updates, View} from './lib/types';
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
 * Whether two update revisions are equal.
 *
 * @param {Updates} a
 * @param {Updates} b
 * @returns {boolean}
 */
const sameUpdates = (a: Updates, b: Updates): boolean =>
    a.pageList === b.pageList && a.settings === b.settings && a.answers === b.answers;

/**
 * Poll the revisions of the data of the activity while the browser tab is visible.
 *
 * @param {number} cmId
 * @param {number} pageId the page shown
 * @param {number} refreshInterval polling interval in milliseconds
 * @returns {Updates}
 */
function useUpdates(cmId: number, pageId: number, refreshInterval: number): Updates {
    const [updates, setUpdates] = useState<Updates>({pageList: 0, settings: 0, answers: 0});

    useEffect(() => {
        let active = true;
        let timer: number | undefined;
        const interval = Math.max(MIN_REFRESH_INTERVAL, refreshInterval || 0);
        const poll = async() => {
            if (!document.hidden) {
                try {
                    const latest = await get<Updates>('mod_mootimeter', `/instances/${cmId}/updates?pageid=${pageId}`);
                    if (active) {
                        setUpdates((old) => (sameUpdates(old, latest) ? old : latest));
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
    }, [cmId, pageId, refreshInterval]);

    return updates;
}

/**
 * Load a page whenever its version changes.
 *
 * @param {number} pageId the page, 0 for none
 * @param {string} version changes whenever the page has to be reloaded
 * @param {Function} onMissing called if the page has been deleted or hidden meanwhile
 * @returns {PageInfo|null}
 */
function usePage(pageId: number, version: string, onMissing: () => void): PageInfo | null {
    const [page, setPage] = useState<PageInfo | null>(null);

    useEffect(() => {
        if (!pageId) {
            setPage(null);
            return undefined;
        }
        let active = true;
        get<PageInfo>('mod_mootimeter', `/pages/${pageId}`)
            .then((result) => active && setPage(result))
            .catch((error) => {
                // Most likely the page has been deleted or hidden, the reloaded page list selects another one.
                if (active) {
                    if (!(error instanceof ApiError && [403, 404].includes(error.status))) {
                        displayError(error);
                    }
                    setPage(null);
                    onMissing();
                }
            });
        return () => {
            active = false;
        };
    }, [pageId, version]);

    return page?.id === pageId ? page : null;
}

/**
 * Whether the selected page has been hidden from a participant.
 *
 * @param {boolean} isModerator
 * @param {number} pageId
 * @param {PageListItem[]|null} pages the pages visible to the user
 * @returns {boolean}
 */
const isLocked = (isModerator: boolean, pageId: number, pages: PageListItem[] | null): boolean =>
    !isModerator && pageId > 0 && pages !== null && !pages.some((p) => p.id === pageId);

/**
 * Whether the settings column and the strip to unfold it are shown.
 *
 * @param {boolean} canSettings whether the current view has settings for the user
 * @param {boolean} isEditing
 * @param {boolean} settingsOpen whether the settings were unfolded outside the editing mode
 * @param {boolean} fullscreen
 * @returns {{show: boolean, toggle: boolean}}
 */
const settingsVisibility = (canSettings: boolean, isEditing: boolean, settingsOpen: boolean, fullscreen: boolean) => ({
    show: canSettings && (isEditing || settingsOpen),
    toggle: canSettings && !isEditing && !fullscreen,
});

type ContentProps = {
    pages: PageListItem[] | null;
    tools: ToolInfo[];
    pageId: number;
    view: View;
    isModerator: boolean;
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
function Content({pages, tools, pageId, view, isModerator, locked, toolprops, onAddPage}: ContentProps): React.JSX.Element {
    const s = useStrings('mod_mootimeter', ['please_select_a_page', 'no_pages_header', 'pagelocked']);

    if (!pages) {
        return <Loading />;
    }
    if (isModerator && (view === 'addpage' || !pageId)) {
        return <AddPage tools={tools} onAdd={onAddPage} />;
    }
    if (!pageId || locked) {
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
    } else if (view === 'overview' && isModerator) {
        specifier = toolprops.page.components.overview;
    }
    return <ToolComponent key={`${specifier}-${pageId}`} specifier={specifier} {...toolprops} />;
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
 * Root component, rendered by mod_mootimeter\output\app.
 *
 * @param {AppProps} props
 * @returns {React.JSX.Element}
 */
export default function App(props: AppProps): React.JSX.Element {
    const {cmId, isModerator} = props;

    const [pages, setPages] = useState<PageListItem[] | null>(null);
    const [tools, setTools] = useState<ToolInfo[]>([]);
    const [isEditing, setIsEditing] = useState(props.isEditing);
    const [pageId, setPageId] = useState(props.pageId);
    const [view, setView] = useState<View>(props.view);
    const [fullscreen, setFullscreen] = useState(props.fullscreen);
    // Outside the editing mode moderators can unfold the settings without switching the mode.
    const [settingsOpen, setSettingsOpen] = useState(false);
    // Incremented by own actions so that the data is reloaded immediately.
    const [localVersion, setLocalVersion] = useState(0);
    const [pagesReload, setPagesReload] = useState(0);

    const refresh = useCallback(() => setLocalVersion((version) => version + 1), []);

    useEffect(() => updateUrl(pageId, view, fullscreen), [pageId, view, fullscreen]);

    const updates = useUpdates(cmId, pageId, props.refreshInterval);

    useEffect(() => {
        let active = true;
        getAll<PageListItem>('mod_mootimeter', `/instances/${cmId}/pages?sort=number`)
            .then((list) => active && setPages(list))
            .catch(displayError);
        return () => {
            active = false;
        };
    }, [cmId, updates.pageList, localVersion, pagesReload]);

    useEffect(() => {
        if (isModerator) {
            getAll<ToolInfo>('mod_mootimeter', '/tools?sort=name').then(setTools).catch(displayError);
        }
    }, [isModerator]);

    // Select another page if the selected one disappeared, or the only one if nothing is selected.
    // Participants keep a page the teacher has hidden and see a notice until it is shown again.
    useEffect(() => {
        if (!pages) {
            return;
        }
        if (isModerator && pageId && !pages.some((p) => p.id === pageId)) {
            setPageId(pages.length ? pages[0].id : 0);
            setView('question');
        } else if (!pageId && pages.length === 1 && view !== 'addpage') {
            setPageId(pages[0].id);
        }
    }, [pages, pageId, view, isModerator]);

    const locked = isLocked(isModerator, pageId, pages);

    const pageVersion = `${updates.settings}:${isModerator ? updates.answers : 0}:${localVersion}`;
    const page = usePage(locked ? 0 : pageId, pageVersion, () => setPagesReload((count) => count + 1));

    const selectPage = (id: number) => {
        setPageId(id);
        setView('question');
    };

    const addPage = async(tool: string) => {
        try {
            const created = await post<PageInfo>('mod_mootimeter', `/instances/${cmId}/pages`, {tool});
            setPages(await getAll<PageListItem>('mod_mootimeter', `/instances/${cmId}/pages?sort=number`));
            // A new page has to be configured.
            await enableEditMode().catch(displayError);
            setIsEditing(true);
            setPageId(created.id);
            setView('question');
        } catch (error) {
            displayError(error);
        }
    };

    const movePage = (id: number, position: number) => {
        if (pages) {
            const reordered = pages.filter((p) => p.id !== id);
            reordered.splice(position, 0, pages.find((p) => p.id === id) as PageListItem);
            setPages(reordered.map((p, index) => ({...p, number: index + 1})));
        }
        patch('mod_mootimeter', `/pages/${id}`, {position}).then(refresh).catch(displayError);
    };

    const toggleVisibility = async(item: PageListItem) => {
        try {
            const changed = await patch<PageInfo>('mod_mootimeter', `/pages/${item.id}`, {visible: !item.visible});
            refresh();
            addToast(await getString(changed.visible ? 'page_shown' : 'page_hidden', 'mod_mootimeter', item.number));
        } catch (error) {
            displayError(error);
        }
    };

    const currentPage = locked ? null : page;
    const toolprops: ToolViewProps | null = currentPage ? {
        page: currentPage,
        isModerator,
        isEditing,
        settingsVersion: `${updates.settings}:${localVersion}`,
        answersVersion: `${updates.answers}:${localVersion}`,
        refresh,
    } : null;

    const canSettings = isModerator && toolprops !== null && view !== 'addpage';
    const settings = settingsVisibility(canSettings, isEditing, settingsOpen, fullscreen);
    const showContentMenu = toolprops && !(isModerator && view === 'addpage');

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
                                selected={pageId}
                                isEditing={isModerator && isEditing}
                                onSelect={selectPage}
                                onAddPage={() => setView('addpage')}
                                onMove={movePage}
                                onToggleVisibility={isModerator ? toggleVisibility : undefined}
                            />
                        ) : <Loading />}
                    </div>
                    {settings.show && toolprops && (
                        <div className="mootimetercol mootimetercoledit" id="mootimeter-col-settings">
                            <SettingsPanel
                                key={`settings-${pageId}`}
                                {...toolprops}
                                onDeleted={() => {
                                    const remaining = (pages ?? []).filter((p) => p.id !== pageId);
                                    setPages(remaining);
                                    setPageId(remaining.length ? remaining[0].id : 0);
                                    setView('question');
                                    setPagesReload((count) => count + 1);
                                }}
                            />
                        </div>
                    )}
                    {settings.toggle && (
                        <SettingsToggle open={settingsOpen} onToggle={() => setSettingsOpen(!settingsOpen)} />
                    )}
                </div>
                <div className="mootimetercol mootimetercolcontent">
                    {showContentMenu && toolprops && (
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
                            pageId={pageId}
                            view={view}
                            isModerator={isModerator}
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
