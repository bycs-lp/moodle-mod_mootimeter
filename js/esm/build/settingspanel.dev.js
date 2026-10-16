var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Settings column of a page, shown to moderators in editing mode.
 *
 * @module     mod_mootimeter/settingspanel
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { patch, remove } from "./lib/api";
import { Divider, TextSetting, TransparentButton } from "./lib/components";
import { useStrings } from "./lib/hooks";
import { confirmDelete, displayError } from "./lib/moodle";
import ToolComponent from "./toolcomponent";
function SettingsPanel(props) {
  const { page, refresh, onDeleted } = props;
  const s = useStrings("mod_mootimeter", [
    "tooltip_enable_page",
    "tooltip_disable_page",
    "delete_page",
    "enter_question",
    "question",
    "settings"
  ]);
  const c = useStrings("core", ["areyousure"]);
  const save = /* @__PURE__ */ __name((body) => patch("mod_mootimeter", `/pages/${page.id}`, body).then(refresh), "save");
  const toggleVisibility = /* @__PURE__ */ __name(() => {
    save({ visible: !page.visible }).catch(displayError);
  }, "toggleVisibility");
  const deletePage = /* @__PURE__ */ __name(async () => {
    if (await confirmDelete(s.delete_page, c.areyousure)) {
      remove("mod_mootimeter", `/pages/${page.id}`).then(onDeleted).catch(displayError);
    }
  }, "deletePage");
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimetersettings mootimeterfullwidth", children: [
    /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-header-wrapper", children: [
      /* @__PURE__ */ jsxDEV("h4", { children: page.toolName }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
        lineNumber: 70,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "d-flex", children: [
        /* @__PURE__ */ jsxDEV(
          TransparentButton,
          {
            icon: page.visible ? "fa-eye" : "fa-eye-slash",
            title: page.visible ? s.tooltip_enable_page : s.tooltip_disable_page,
            onClick: toggleVisibility
          },
          void 0,
          false,
          {
            fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
            lineNumber: 72,
            columnNumber: 21
          },
          this
        ),
        /* @__PURE__ */ jsxDEV(TransparentButton, { icon: "fa-trash", title: s.delete_page, onClick: deletePage }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
          lineNumber: 77,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
        lineNumber: 71,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
      lineNumber: 69,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(
      TextSetting,
      {
        value: page.question,
        label: s.question,
        placeholder: s.enter_question,
        onSave: (question) => save({ question })
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
        lineNumber: 81,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(Divider, {}, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
      lineNumber: 88,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(ToolComponent, { specifier: page.components.settings, ...props }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
      lineNumber: 90,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/settingspanel.tsx",
    lineNumber: 68,
    columnNumber: 9
  }, this);
}
__name(SettingsPanel, "SettingsPanel");
export {
  SettingsPanel as default
};
//# sourceMappingURL=settingspanel.dev.js.map
