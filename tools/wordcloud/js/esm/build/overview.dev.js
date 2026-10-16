var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Answer overview of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/overview
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useState } from "react";
import { patch, remove } from "@moodle/lms/mod_mootimeter/lib/api";
import { Loading, OverviewTable, TransparentButton } from "@moodle/lms/mod_mootimeter/lib/components";
import { useCollection, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { displayError } from "@moodle/lms/mod_mootimeter/lib/moodle";
import { COMPONENT } from "./types";
function AnswerEditor({ pageid, row, onChanged }) {
  const s = useStrings("core", ["edit", "save", "cancel"]);
  const [editing, setEditing] = useState(false);
  const [text, setText] = useState(row.text);
  if (!editing) {
    return /* @__PURE__ */ jsxDEV("span", { children: [
      row.text,
      /* @__PURE__ */ jsxDEV(
        TransparentButton,
        {
          icon: "fa-pencil",
          title: s.edit,
          className: "text-body",
          onClick: () => {
            setText(row.text);
            setEditing(true);
          }
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
          lineNumber: 54,
          columnNumber: 17
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
      lineNumber: 52,
      columnNumber: 13
    }, this);
  }
  const save = /* @__PURE__ */ __name(async (e) => {
    e.preventDefault();
    try {
      await patch(COMPONENT, `/pages/${pageid}/answers/${row.id}`, { text });
      setEditing(false);
      onChanged();
    } catch (error) {
      displayError(error);
    }
  }, "save");
  return /* @__PURE__ */ jsxDEV("form", { onSubmit: save, className: "d-flex", children: [
    /* @__PURE__ */ jsxDEV(
      "input",
      {
        type: "text",
        className: "form-control form-control-sm me-1",
        "aria-label": s.edit,
        value: text,
        autoFocus: true,
        onChange: (e) => setText(e.target.value)
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
        lineNumber: 80,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("button", { type: "submit", className: "btn btn-primary btn-sm me-1", children: s.save }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
      lineNumber: 82,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-secondary btn-sm", onClick: () => setEditing(false), children: s.cancel }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
      lineNumber: 83,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
    lineNumber: 79,
    columnNumber: 9
  }, this);
}
__name(AnswerEditor, "AnswerEditor");
function OverviewView({ page, answersVersion, refresh }) {
  const s = useStrings("mod_mootimeter", ["delete_single_answer_dialog_title", "delete_single_answer_dialog_question"]);
  const answers = useCollection(COMPONENT, `/pages/${page.id}/answers?sort=id`, [answersVersion]);
  if (!answers) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
      lineNumber: 99,
      columnNumber: 16
    }, this);
  }
  return /* @__PURE__ */ jsxDEV(
    OverviewTable,
    {
      rows: answers.items,
      offset: (answers.page - 1) * answers.pageSize,
      totalPages: answers.totalPages,
      currentPage: answers.page,
      onPageChange: answers.setPage,
      deleteTitle: s.delete_single_answer_dialog_title,
      deleteQuestion: s.delete_single_answer_dialog_question,
      onDelete: (row) => remove(COMPONENT, `/pages/${page.id}/answers/${row.id}`).then(refresh),
      renderAnswer: (row) => /* @__PURE__ */ jsxDEV(AnswerEditor, { pageid: page.id, row, onChanged: refresh }, row.id, false, {
        fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
        lineNumber: 112,
        columnNumber: 36
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/overview.tsx",
      lineNumber: 103,
      columnNumber: 9
    },
    this
  );
}
__name(OverviewView, "OverviewView");
export {
  OverviewView as default
};
//# sourceMappingURL=overview.dev.js.map
