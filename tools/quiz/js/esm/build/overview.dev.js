var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Answer overview of the quiz and poll tool.
 *
 * @module     mootimetertool_quiz/overview
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import React, { useState } from "react";
import { put, remove } from "@moodle/lms/mod_mootimeter/lib/api";
import { Choice, FormattedHtml, Loading, OverviewTable, TransparentButton } from "@moodle/lms/mod_mootimeter/lib/components";
import { useCollection, useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { displayError } from "@moodle/lms/mod_mootimeter/lib/moodle";
import { componentOf } from "./types";
function AnswerEditor({ page, row, data, onChanged }) {
  const s = useStrings("core", ["edit", "save", "cancel"]);
  const [editing, setEditing] = useState(false);
  const [selected, setSelected] = useState(row.optionIds);
  const single = data.maxAnswers === 1;
  if (!editing) {
    const texts = data.options.filter((option) => row.optionIds.includes(option.id));
    return /* @__PURE__ */ jsxDEV("span", { children: [
      texts.map((option, index) => /* @__PURE__ */ jsxDEV(React.Fragment, { children: [
        index > 0 && " | ",
        /* @__PURE__ */ jsxDEV(FormattedHtml, { html: option.textHtml }, void 0, false, {
          fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
          lineNumber: 59,
          columnNumber: 25
        }, this)
      ] }, option.id, true, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
        lineNumber: 57,
        columnNumber: 21
      }, this)),
      /* @__PURE__ */ jsxDEV(
        TransparentButton,
        {
          icon: "fa-pencil",
          title: s.edit,
          className: "text-body",
          onClick: () => {
            setSelected(row.optionIds);
            setEditing(true);
          }
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
          lineNumber: 62,
          columnNumber: 17
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
      lineNumber: 55,
      columnNumber: 13
    }, this);
  }
  const save = /* @__PURE__ */ __name(async () => {
    try {
      await put(componentOf(page), `/pages/${page.id}/responses/${row.id}`, { optionIds: selected });
      setEditing(false);
      onChanged();
    } catch (error) {
      displayError(error);
    }
  }, "save");
  return /* @__PURE__ */ jsxDEV("div", { children: [
    data.options.map((option) => /* @__PURE__ */ jsxDEV(
      Choice,
      {
        type: single ? "radio" : "checkbox",
        name: `mtmt_quiz_overview_${row.id}`,
        checked: selected.includes(option.id),
        label: /* @__PURE__ */ jsxDEV(FormattedHtml, { html: option.textHtml }, void 0, false, {
          fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
          lineNumber: 93,
          columnNumber: 28
        }, this),
        onChange: (checked) => setSelected((old) => {
          if (single) {
            return checked ? [option.id] : [];
          }
          return checked ? [...old, option.id] : old.filter((id) => id !== option.id);
        })
      },
      option.id,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
        lineNumber: 88,
        columnNumber: 17
      },
      this
    )),
    /* @__PURE__ */ jsxDEV("div", { className: "d-flex gap-1 mt-2", children: [
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary btn-sm", onClick: save, children: s.save }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
        lineNumber: 103,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-secondary btn-sm", onClick: () => setEditing(false), children: s.cancel }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
        lineNumber: 104,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
      lineNumber: 102,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
    lineNumber: 86,
    columnNumber: 9
  }, this);
}
__name(AnswerEditor, "AnswerEditor");
function OverviewView({ page, settingsVersion, answersVersion, refresh }) {
  const s = useStrings("mod_mootimeter", ["delete_answers_of_user_dialog_title", "delete_answers_of_user_dialog_question"]);
  const component = componentOf(page);
  const data = useResource(component, `/pages/${page.id}`, [settingsVersion]);
  const responses = useCollection(
    component,
    `/pages/${page.id}/responses?sort=id`,
    [settingsVersion, answersVersion]
  );
  if (!data || !responses) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
      lineNumber: 127,
      columnNumber: 16
    }, this);
  }
  return /* @__PURE__ */ jsxDEV(
    OverviewTable,
    {
      rows: responses.items,
      offset: (responses.page - 1) * responses.pageSize,
      totalPages: responses.totalPages,
      currentPage: responses.page,
      onPageChange: responses.setPage,
      deleteTitle: s.delete_answers_of_user_dialog_title,
      deleteQuestion: s.delete_answers_of_user_dialog_question,
      onDelete: (row) => remove(component, `/pages/${page.id}/responses/${row.id}`).then(refresh),
      renderAnswer: (row) => /* @__PURE__ */ jsxDEV(AnswerEditor, { page, row, data, onChanged: refresh }, row.id, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
        lineNumber: 140,
        columnNumber: 36
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/overview.tsx",
      lineNumber: 131,
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
