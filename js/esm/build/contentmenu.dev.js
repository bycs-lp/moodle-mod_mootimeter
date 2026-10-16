var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Content menu above the page content.
 *
 * @module     mod_mootimeter/contentmenu
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import config from "@moodle/lms/core/config";
import { patch, remove } from "./lib/api";
import { IconButton } from "./lib/components";
import { useStrings } from "./lib/hooks";
import { confirmDelete, displayError } from "./lib/moodle";
import ToolComponent from "./toolcomponent";
function ContentMenu(props) {
  const { page, isModerator, view, fullscreen, onView, onFullscreen, refresh } = props;
  const s = useStrings("mod_mootimeter", [
    "tooltip_content_menu_teacherpermission",
    "tooltip_content_menu_teacherpermission_disabled",
    "tooltip_delete_all_answers",
    "delete_all_answers_dialog_title",
    "delete_all_answers_dialog_question",
    "show_answer_overview",
    "tooltip_show_question_page",
    "tooltip_show_results_page",
    "tooltip_fullscreen_expand",
    "tooltip_fullscreen_compress",
    "download_results"
  ]);
  const toggleResultsVisibility = /* @__PURE__ */ __name(() => {
    patch("mod_mootimeter", `/pages/${page.id}`, { resultsPublished: !page.resultsPublished }).then(refresh).catch(displayError);
  }, "toggleResultsVisibility");
  const deleteAllAnswers = /* @__PURE__ */ __name(async () => {
    if (await confirmDelete(s.delete_all_answers_dialog_title, s.delete_all_answers_dialog_question)) {
      remove("mod_mootimeter", `/pages/${page.id}/answers`).then(refresh).catch(displayError);
    }
  }, "deleteAllAnswers");
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-content-menu", id: "mootimeter-pagecontentmenu", children: [
    isModerator && /* @__PURE__ */ jsxDEV(Fragment, { children: [
      /* @__PURE__ */ jsxDEV(
        IconButton,
        {
          icon: page.resultsPublished ? "fa-eye" : "fa-eye-slash",
          title: page.resultsPublished ? s.tooltip_content_menu_teacherpermission_disabled : s.tooltip_content_menu_teacherpermission,
          active: page.resultsPublished,
          onClick: toggleResultsVisibility
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
          lineNumber: 77,
          columnNumber: 21
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(ToolComponent, { specifier: page.components.menu, ...props }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
        lineNumber: 85,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(IconButton, { icon: "fa-trash", title: s.tooltip_delete_all_answers, onClick: deleteAllAnswers }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
        lineNumber: 86,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(
        IconButton,
        {
          icon: view === "overview" ? "fa-pencil-square-o" : "fa-table",
          title: view === "overview" ? s.tooltip_show_question_page : s.show_answer_overview,
          onClick: () => onView(view === "overview" ? "question" : "overview")
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
          lineNumber: 87,
          columnNumber: 21
        },
        this
      ),
      /* @__PURE__ */ jsxDEV("div", { className: "vertical-divider" }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
        lineNumber: 92,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
      lineNumber: 76,
      columnNumber: 17
    }, this),
    /* @__PURE__ */ jsxDEV(
      IconButton,
      {
        icon: view === "results" ? "fa-pencil-square-o" : "fa-bar-chart",
        title: view === "results" ? s.tooltip_show_question_page : s.tooltip_show_results_page,
        onClick: () => onView(view === "results" ? "question" : "results")
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
        lineNumber: 95,
        columnNumber: 13
      },
      this
    ),
    isModerator && (view === "results" || view === "overview") && /* @__PURE__ */ jsxDEV(
      IconButton,
      {
        icon: "fa-download",
        title: s.download_results,
        onClick: () => window.location.assign(`${config.wwwroot}/mod/mootimeter/download.php?pageid=${page.id}`)
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
        lineNumber: 101,
        columnNumber: 17
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(
      IconButton,
      {
        icon: fullscreen ? "fa-compress" : "fa-expand",
        title: fullscreen ? s.tooltip_fullscreen_compress : s.tooltip_fullscreen_expand,
        active: fullscreen,
        onClick: () => onFullscreen(!fullscreen)
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
        lineNumber: 107,
        columnNumber: 13
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/contentmenu.tsx",
    lineNumber: 74,
    columnNumber: 9
  }, this);
}
__name(ContentMenu, "ContentMenu");
export {
  ContentMenu as default
};
//# sourceMappingURL=contentmenu.dev.js.map
