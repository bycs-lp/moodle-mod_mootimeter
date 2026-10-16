var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * The mootimeter application.
 *
 * @module     mod_mootimeter/app
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCallback, useEffect, useState } from "react";
import { getString } from "@moodle/lms/core/stringUtils";
import AddPage from "./addpage";
import ContentMenu from "./contentmenu";
import { ApiError, get, getAll, patch, post } from "./lib/api";
import { Loading } from "./lib/components";
import { useStrings } from "./lib/hooks";
import { addToast, displayError, enableEditMode } from "./lib/moodle";
import PageList from "./pagelist";
import SettingsPanel from "./settingspanel";
import ToolComponent from "./toolcomponent";
const MIN_REFRESH_INTERVAL = 1e3;
const updateUrl = /* @__PURE__ */ __name((pageid, view, fullscreen) => {
  const url = new URL(window.location.href);
  const params = {
    pageid: pageid ? String(pageid) : null,
    r: view === "results" ? "1" : null,
    o: view === "overview" ? "1" : null,
    a: view === "addpage" ? "addpage" : null,
    f: fullscreen ? "1" : null
  };
  Object.entries(params).forEach(([name, value]) => {
    if (value === null) {
      url.searchParams.delete(name);
    } else {
      url.searchParams.set(name, value);
    }
  });
  window.history.replaceState(null, "", url);
}, "updateUrl");
const sameUpdates = /* @__PURE__ */ __name((a, b) => a.pageList === b.pageList && a.settings === b.settings && a.answers === b.answers, "sameUpdates");
function useUpdates(cmId, pageId, refreshInterval) {
  const [updates, setUpdates] = useState({ pageList: 0, settings: 0, answers: 0 });
  useEffect(() => {
    let active = true;
    let timer;
    const interval = Math.max(MIN_REFRESH_INTERVAL, refreshInterval || 0);
    const poll = /* @__PURE__ */ __name(async () => {
      if (!document.hidden) {
        try {
          const latest = await get("mod_mootimeter", `/instances/${cmId}/updates?pageid=${pageId}`);
          if (active) {
            setUpdates((old) => sameUpdates(old, latest) ? old : latest);
          }
        } catch (error) {
          window.console.error(error);
        }
      }
      if (active) {
        timer = window.setTimeout(poll, interval);
      }
    }, "poll");
    poll();
    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [cmId, pageId, refreshInterval]);
  return updates;
}
__name(useUpdates, "useUpdates");
function usePage(pageId, version, onMissing) {
  const [page, setPage] = useState(null);
  useEffect(() => {
    if (!pageId) {
      setPage(null);
      return void 0;
    }
    let active = true;
    get("mod_mootimeter", `/pages/${pageId}`).then((result) => active && setPage(result)).catch((error) => {
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
__name(usePage, "usePage");
const isLocked = /* @__PURE__ */ __name((isModerator, pageId, pages) => !isModerator && pageId > 0 && pages !== null && !pages.some((p) => p.id === pageId), "isLocked");
const settingsVisibility = /* @__PURE__ */ __name((canSettings, isEditing, settingsOpen, fullscreen) => ({
  show: canSettings && (isEditing || settingsOpen),
  toggle: canSettings && !isEditing && !fullscreen
}), "settingsVisibility");
function Content({ pages, tools, pageId, view, isModerator, locked, toolprops, onAddPage }) {
  const s = useStrings("mod_mootimeter", ["please_select_a_page", "no_pages_header", "pagelocked"]);
  if (!pages) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
      lineNumber: 199,
      columnNumber: 16
    }, this);
  }
  if (isModerator && (view === "addpage" || !pageId)) {
    return /* @__PURE__ */ jsxDEV(AddPage, { tools, onAdd: onAddPage }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
      lineNumber: 202,
      columnNumber: 16
    }, this);
  }
  if (!pageId || locked) {
    let message = pages.length ? s.please_select_a_page : s.no_pages_header;
    if (locked) {
      message = s.pagelocked;
    }
    return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-colcontent-preview mootimeter-width-s", children: /* @__PURE__ */ jsxDEV("h4", { children: message }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
      lineNumber: 211,
      columnNumber: 17
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
      lineNumber: 210,
      columnNumber: 13
    }, this);
  }
  if (!toolprops) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
      lineNumber: 216,
      columnNumber: 16
    }, this);
  }
  let specifier = toolprops.page.components.content;
  if (view === "results") {
    specifier = toolprops.page.components.results;
  } else if (view === "overview" && isModerator) {
    specifier = toolprops.page.components.overview;
  }
  return /* @__PURE__ */ jsxDEV(ToolComponent, { specifier, ...toolprops }, `${specifier}-${pageId}`, false, {
    fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
    lineNumber: 224,
    columnNumber: 12
  }, this);
}
__name(Content, "Content");
function SettingsToggle({ open, onToggle }) {
  const s = useStrings("core", ["settings", "showsettings", "hidesettings"]);
  const title = open ? s.hidesettings : s.showsettings;
  return /* @__PURE__ */ jsxDEV(
    "button",
    {
      type: "button",
      className: "mootimeter-settings-toggle",
      title,
      "aria-label": title,
      "aria-expanded": open,
      "aria-controls": "mootimeter-col-settings",
      onClick: onToggle,
      children: [
        /* @__PURE__ */ jsxDEV("i", { className: "icon fa fa-gear fa-light", "aria-hidden": "true" }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
          lineNumber: 252,
          columnNumber: 13
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "mootimeter-settings-toggle-label", children: s.settings }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
          lineNumber: 253,
          columnNumber: 13
        }, this),
        /* @__PURE__ */ jsxDEV("i", { className: `icon fa ${open ? "fa-angle-double-left" : "fa-angle-double-right"} fa-light`, "aria-hidden": "true" }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
          lineNumber: 254,
          columnNumber: 13
        }, this)
      ]
    },
    void 0,
    true,
    {
      fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
      lineNumber: 243,
      columnNumber: 9
    },
    this
  );
}
__name(SettingsToggle, "SettingsToggle");
function App(props) {
  const { cmId, isModerator } = props;
  const [pages, setPages] = useState(null);
  const [tools, setTools] = useState([]);
  const [isEditing, setIsEditing] = useState(props.isEditing);
  const [pageId, setPageId] = useState(props.pageId);
  const [view, setView] = useState(props.view);
  const [fullscreen, setFullscreen] = useState(props.fullscreen);
  const [settingsOpen, setSettingsOpen] = useState(false);
  const [localVersion, setLocalVersion] = useState(0);
  const [pagesReload, setPagesReload] = useState(0);
  const refresh = useCallback(() => setLocalVersion((version) => version + 1), []);
  useEffect(() => updateUrl(pageId, view, fullscreen), [pageId, view, fullscreen]);
  const updates = useUpdates(cmId, pageId, props.refreshInterval);
  useEffect(() => {
    let active = true;
    getAll("mod_mootimeter", `/instances/${cmId}/pages?sort=number`).then((list) => active && setPages(list)).catch(displayError);
    return () => {
      active = false;
    };
  }, [cmId, updates.pageList, localVersion, pagesReload]);
  useEffect(() => {
    if (isModerator) {
      getAll("mod_mootimeter", "/tools?sort=name").then(setTools).catch(displayError);
    }
  }, [isModerator]);
  useEffect(() => {
    if (!pages) {
      return;
    }
    if (isModerator && pageId && !pages.some((p) => p.id === pageId)) {
      setPageId(pages.length ? pages[0].id : 0);
      setView("question");
    } else if (!pageId && pages.length === 1 && view !== "addpage") {
      setPageId(pages[0].id);
    }
  }, [pages, pageId, view, isModerator]);
  const locked = isLocked(isModerator, pageId, pages);
  const pageVersion = `${updates.settings}:${isModerator ? updates.answers : 0}:${localVersion}`;
  const page = usePage(locked ? 0 : pageId, pageVersion, () => setPagesReload((count) => count + 1));
  const selectPage = /* @__PURE__ */ __name((id) => {
    setPageId(id);
    setView("question");
  }, "selectPage");
  const addPage = /* @__PURE__ */ __name(async (tool) => {
    try {
      const created = await post("mod_mootimeter", `/instances/${cmId}/pages`, { tool });
      setPages(await getAll("mod_mootimeter", `/instances/${cmId}/pages?sort=number`));
      await enableEditMode().catch(displayError);
      setIsEditing(true);
      setPageId(created.id);
      setView("question");
    } catch (error) {
      displayError(error);
    }
  }, "addPage");
  const movePage = /* @__PURE__ */ __name((id, position) => {
    if (pages) {
      const reordered = pages.filter((p) => p.id !== id);
      reordered.splice(position, 0, pages.find((p) => p.id === id));
      setPages(reordered.map((p, index) => ({ ...p, number: index + 1 })));
    }
    patch("mod_mootimeter", `/pages/${id}`, { position }).then(refresh).catch(displayError);
  }, "movePage");
  const toggleVisibility = /* @__PURE__ */ __name(async (item) => {
    try {
      const changed = await patch("mod_mootimeter", `/pages/${item.id}`, { visible: !item.visible });
      refresh();
      addToast(await getString(changed.visible ? "page_shown" : "page_hidden", "mod_mootimeter", item.number));
    } catch (error) {
      displayError(error);
    }
  }, "toggleVisibility");
  const currentPage = locked ? null : page;
  const toolprops = currentPage ? {
    page: currentPage,
    isModerator,
    isEditing,
    settingsVersion: `${updates.settings}:${localVersion}`,
    answersVersion: `${updates.answers}:${localVersion}`,
    refresh
  } : null;
  const canSettings = isModerator && toolprops !== null && view !== "addpage";
  const settings = settingsVisibility(canSettings, isEditing, settingsOpen, fullscreen);
  const showContentMenu = toolprops && !(isModerator && view === "addpage");
  return /* @__PURE__ */ jsxDEV(
    "div",
    {
      className: `container-fluid px-0 mootimetercontainer isNotNewPage${fullscreen ? " fullscreen" : ""}`,
      id: "mootimeter-main-container",
      children: /* @__PURE__ */ jsxDEV("div", { className: "row g-0", children: [
        /* @__PURE__ */ jsxDEV("div", { id: "mootimeter-col-left", className: settings.show ? "" : "mootimeter-pagesonly", children: [
          /* @__PURE__ */ jsxDEV("div", { className: "mootimetercol mootimetercolpages", id: "mootimeter-pages-col", children: pages ? /* @__PURE__ */ jsxDEV(
            PageList,
            {
              pages,
              selected: pageId,
              isEditing: isModerator && isEditing,
              onSelect: selectPage,
              onAddPage: () => setView("addpage"),
              onMove: movePage,
              onToggleVisibility: isModerator ? toggleVisibility : void 0
            },
            void 0,
            false,
            {
              fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
              lineNumber: 382,
              columnNumber: 29
            },
            this
          ) : /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
            lineNumber: 391,
            columnNumber: 29
          }, this) }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
            lineNumber: 380,
            columnNumber: 21
          }, this),
          settings.show && toolprops && /* @__PURE__ */ jsxDEV("div", { className: "mootimetercol mootimetercoledit", id: "mootimeter-col-settings", children: /* @__PURE__ */ jsxDEV(
            SettingsPanel,
            {
              ...toolprops,
              onDeleted: () => {
                const remaining = (pages ?? []).filter((p) => p.id !== pageId);
                setPages(remaining);
                setPageId(remaining.length ? remaining[0].id : 0);
                setView("question");
                setPagesReload((count) => count + 1);
              }
            },
            `settings-${pageId}`,
            false,
            {
              fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
              lineNumber: 395,
              columnNumber: 29
            },
            this
          ) }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
            lineNumber: 394,
            columnNumber: 25
          }, this),
          settings.toggle && /* @__PURE__ */ jsxDEV(SettingsToggle, { open: settingsOpen, onToggle: () => setSettingsOpen(!settingsOpen) }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
            lineNumber: 409,
            columnNumber: 25
          }, this)
        ] }, void 0, true, {
          fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
          lineNumber: 379,
          columnNumber: 17
        }, this),
        /* @__PURE__ */ jsxDEV("div", { className: "mootimetercol mootimetercolcontent", children: [
          showContentMenu && toolprops && /* @__PURE__ */ jsxDEV(
            ContentMenu,
            {
              ...toolprops,
              view,
              fullscreen,
              onView: setView,
              onFullscreen: setFullscreen
            },
            void 0,
            false,
            {
              fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
              lineNumber: 414,
              columnNumber: 25
            },
            this
          ),
          /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-content-wrapper", id: "mootimeter-pagecontent", children: /* @__PURE__ */ jsxDEV(
            Content,
            {
              pages,
              tools,
              pageId,
              view,
              isModerator,
              locked,
              toolprops,
              onAddPage: addPage
            },
            void 0,
            false,
            {
              fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
              lineNumber: 423,
              columnNumber: 25
            },
            this
          ) }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
            lineNumber: 422,
            columnNumber: 21
          }, this)
        ] }, void 0, true, {
          fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
          lineNumber: 412,
          columnNumber: 17
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
        lineNumber: 378,
        columnNumber: 13
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/js/esm/src/app.tsx",
      lineNumber: 374,
      columnNumber: 9
    },
    this
  );
}
__name(App, "App");
export {
  App as default
};
//# sourceMappingURL=app.dev.js.map
