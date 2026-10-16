var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Settings of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/settings
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { patch } from "@moodle/lms/mod_mootimeter/lib/api";
import { AnonymousModeSetting, CheckboxSetting, Loading, NumberSetting } from "@moodle/lms/mod_mootimeter/lib/components";
import { useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { COMPONENT } from "./types";
function Settings({ page, settingsVersion, refresh }) {
  const s = useStrings("mootimetertool_wordcloud", ["answers_max_number", "allowduplicateanswers"]);
  const c = useStrings("core", ["settings"]);
  const data = useResource(COMPONENT, `/pages/${page.id}`, [settingsVersion]);
  if (!data) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/settings.tsx",
      lineNumber: 44,
      columnNumber: 16
    }, this);
  }
  const save = /* @__PURE__ */ __name((body) => patch(COMPONENT, `/pages/${page.id}`, body).then(refresh), "save");
  return /* @__PURE__ */ jsxDEV(Fragment, { children: [
    /* @__PURE__ */ jsxDEV("p", { className: "text-bold", children: c.settings }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/settings.tsx",
      lineNumber: 51,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(
      NumberSetting,
      {
        value: data.maxAnswers,
        label: s.answers_max_number,
        onSave: (maxAnswers) => save({ maxAnswers })
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/settings.tsx",
        lineNumber: 52,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(
      CheckboxSetting,
      {
        value: data.allowDuplicateAnswers,
        label: s.allowduplicateanswers,
        onSave: (allowDuplicateAnswers) => save({ allowDuplicateAnswers })
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/settings.tsx",
        lineNumber: 57,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(AnonymousModeSetting, { page, onStored: refresh }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/settings.tsx",
      lineNumber: 62,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/settings.tsx",
    lineNumber: 50,
    columnNumber: 9
  }, this);
}
__name(Settings, "Settings");
export {
  Settings as default
};
//# sourceMappingURL=settings.dev.js.map
