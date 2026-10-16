var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Tool selection for a new page.
 *
 * @module     mod_mootimeter/addpage
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useStrings } from "./lib/hooks";
function AddPage({ tools, onAdd }) {
  const s = useStrings("mod_mootimeter", ["select_page_type_heading", "select_page_type_paragraph"]);
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-add-tool-container", children: [
    /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-add-tool-header", children: [
      /* @__PURE__ */ jsxDEV("h3", { children: s.select_page_type_heading }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
        lineNumber: 46,
        columnNumber: 17
      }, this),
      s.select_page_type_paragraph && /* @__PURE__ */ jsxDEV("p", { children: s.select_page_type_paragraph }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
        lineNumber: 47,
        columnNumber: 50
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
      lineNumber: 45,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-tool-select-wrapper", children: tools.map((tool) => /* @__PURE__ */ jsxDEV(
      "button",
      {
        type: "button",
        className: "mootimeter-radio-card mtmt-tool-selector-list",
        "data-name": tool.name,
        onClick: () => onAdd(tool.name),
        children: [
          /* @__PURE__ */ jsxDEV("img", { src: tool.iconUrl, width: "40px", className: "m-1", alt: "" }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
            lineNumber: 58,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV("span", { className: "card-details", children: [
            /* @__PURE__ */ jsxDEV("span", { className: "card-type", children: tool.displayName }, void 0, false, {
              fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
              lineNumber: 60,
              columnNumber: 29
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "card-desc", children: tool.description }, void 0, false, {
              fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
              lineNumber: 61,
              columnNumber: 29
            }, this)
          ] }, void 0, true, {
            fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
            lineNumber: 59,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV("span", { className: "active-border" }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
            lineNumber: 63,
            columnNumber: 25
          }, this)
        ]
      },
      tool.name,
      true,
      {
        fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
        lineNumber: 51,
        columnNumber: 21
      },
      this
    )) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
      lineNumber: 49,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/addpage.tsx",
    lineNumber: 44,
    columnNumber: 9
  }, this);
}
__name(AddPage, "AddPage");
export {
  AddPage as default
};
//# sourceMappingURL=addpage.dev.js.map
