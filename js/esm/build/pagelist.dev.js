var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Sortable list of the pages of a mootimeter instance.
 *
 * @module     mod_mootimeter/pagelist
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { useStrings } from "./lib/hooks";
const LONGPRESS_MS = 600;
function PageList(props) {
  const { pages, selected, isEditing, onSelect, onAddPage, onMove, onToggleVisibility } = props;
  const s = useStrings("mod_mootimeter", ["addpage", "dialogue_move_pagelistelement", "longpress_toggle_visibility"]);
  const [dragged, setDragged] = useState(null);
  const [pressing, setPressing] = useState(null);
  const presstimer = useRef(void 0);
  const longpressed = useRef(false);
  useEffect(() => () => window.clearTimeout(presstimer.current), []);
  const startPress = /* @__PURE__ */ __name((e, page) => {
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
  }, "startPress");
  const cancelPress = /* @__PURE__ */ __name(() => {
    window.clearTimeout(presstimer.current);
    setPressing(null);
  }, "cancelPress");
  const select = /* @__PURE__ */ __name((pageid) => {
    if (longpressed.current) {
      longpressed.current = false;
      return;
    }
    onSelect(pageid);
  }, "select");
  const moveByKey = /* @__PURE__ */ __name((e, page, index) => {
    e.stopPropagation();
    if (e.key === "ArrowUp" && index > 0) {
      e.preventDefault();
      onMove(page.id, index - 1);
    } else if (e.key === "ArrowDown" && index < pages.length - 1) {
      e.preventDefault();
      onMove(page.id, index + 1);
    }
  }, "moveByKey");
  const drop = /* @__PURE__ */ __name((index) => {
    if (dragged !== null && pages[index]?.id !== dragged) {
      onMove(dragged, index);
    }
    setDragged(null);
  }, "drop");
  return /* @__PURE__ */ jsxDEV(Fragment, { children: [
    /* @__PURE__ */ jsxDEV("ul", { id: "mootimeter-pages-list", children: pages.map((page, index) => /* @__PURE__ */ jsxDEV(
      "li",
      {
        className: "mootimeter_pages_li d-flex" + (page.id === selected ? " active" : "") + (page.visible ? "" : " pageunvisible") + (page.id === pressing ? " mootimeter-longpress" : ""),
        title: onToggleVisibility ? [page.title, s.longpress_toggle_visibility].filter(Boolean).join(" \u2013 ") : page.title,
        "data-pageid": page.id,
        draggable: isEditing,
        onDragStart: () => {
          cancelPress();
          setDragged(page.id);
        },
        onDragOver: (e) => isEditing && e.preventDefault(),
        onDrop: (e) => {
          e.preventDefault();
          drop(index);
        },
        onDragEnd: () => setDragged(null),
        children: [
          isEditing && /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "mootimeter_page_move",
              title: s.dialogue_move_pagelistelement,
              "aria-label": `${s.dialogue_move_pagelistelement} ${page.number}`,
              onKeyDown: (e) => moveByKey(e, page, index),
              children: /* @__PURE__ */ jsxDEV("i", { className: "fa fa-arrows", "aria-hidden": "true" }, void 0, false, {
                fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
                lineNumber: 141,
                columnNumber: 33
              }, this)
            },
            void 0,
            false,
            {
              fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
              lineNumber: 134,
              columnNumber: 29
            },
            this
          ),
          /* @__PURE__ */ jsxDEV(
            "button",
            {
              type: "button",
              className: "mootimeter_page_select",
              "aria-label": `${page.number} ${page.title}`,
              "aria-current": page.id === selected ? "page" : void 0,
              onClick: () => select(page.id),
              onPointerDown: (e) => startPress(e, page),
              onPointerUp: cancelPress,
              onPointerLeave: cancelPress,
              onPointerCancel: cancelPress,
              onContextMenu: (e) => onToggleVisibility && e.preventDefault(),
              children: [
                /* @__PURE__ */ jsxDEV("span", { className: "mootimeter_pages_count", children: page.number }, void 0, false, {
                  fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
                  lineNumber: 156,
                  columnNumber: 29
                }, this),
                /* @__PURE__ */ jsxDEV("img", { src: page.iconUrl, width: "35px", className: "m-1", alt: "" }, void 0, false, {
                  fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
                  lineNumber: 157,
                  columnNumber: 29
                }, this)
              ]
            },
            void 0,
            true,
            {
              fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
              lineNumber: 144,
              columnNumber: 25
            },
            this
          )
        ]
      },
      page.id,
      true,
      {
        fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
        lineNumber: 110,
        columnNumber: 21
      },
      this
    )) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
      lineNumber: 108,
      columnNumber: 13
    }, this),
    isEditing && /* @__PURE__ */ jsxDEV("ul", { id: "mootimeter-addpage-button", children: /* @__PURE__ */ jsxDEV("li", { className: "d-flex mootimeter_addpage", title: s.addpage, children: /* @__PURE__ */ jsxDEV("button", { type: "button", className: "mootimeter_page_select", "aria-label": s.addpage, onClick: onAddPage, children: /* @__PURE__ */ jsxDEV("i", { className: "fa fa-plus p-1 mx-1 my-auto", "aria-hidden": "true" }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
      lineNumber: 166,
      columnNumber: 29
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
      lineNumber: 165,
      columnNumber: 25
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
      lineNumber: 164,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
      lineNumber: 163,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/pagelist.tsx",
    lineNumber: 107,
    columnNumber: 9
  }, this);
}
__name(PageList, "PageList");
export {
  PageList as default
};
//# sourceMappingURL=pagelist.dev.js.map
