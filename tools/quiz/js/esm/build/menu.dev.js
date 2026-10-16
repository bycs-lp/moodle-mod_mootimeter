var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Content menu button of the quiz to reveal the correct answers.
 *
 * @module     mootimetertool_quiz/menu
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { patch } from "@moodle/lms/mod_mootimeter/lib/api";
import { IconButton } from "@moodle/lms/mod_mootimeter/lib/components";
import { useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { displayError } from "@moodle/lms/mod_mootimeter/lib/moodle";
import { componentOf } from "./types";
function Menu({ page, settingsVersion, refresh }) {
  const s = useStrings("mootimetertool_quiz", [
    "tooltip_content_menu_answercorrection",
    "tooltip_content_menu_answercorrection_disabled"
  ]);
  const data = useResource(componentOf(page), `/pages/${page.id}`, [settingsVersion]);
  if (!data) {
    return null;
  }
  return /* @__PURE__ */ jsxDEV(
    IconButton,
    {
      icon: data.correctionShown ? "fa-check-square-o" : "fa-square-o",
      title: data.correctionShown ? s.tooltip_content_menu_answercorrection_disabled : s.tooltip_content_menu_answercorrection,
      active: data.correctionShown,
      onClick: () => {
        patch(componentOf(page), `/pages/${page.id}`, { correctionShown: !data.correctionShown }).then(refresh).catch(displayError);
      }
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/menu.tsx",
      lineNumber: 51,
      columnNumber: 9
    },
    this
  );
}
__name(Menu, "Menu");
export {
  Menu as default
};
//# sourceMappingURL=menu.dev.js.map
