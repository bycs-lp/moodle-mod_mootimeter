var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Question view of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/content
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useState } from "react";
import { ApiError, post, remove as removeResource } from "@moodle/lms/mod_mootimeter/lib/api";
import { Loading, Notification, QuestionHeader } from "@moodle/lms/mod_mootimeter/lib/components";
import { useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { confirmDelete, displayError } from "@moodle/lms/mod_mootimeter/lib/moodle";
import { COMPONENT } from "./types";
function Content({ page, settingsVersion, answersVersion, refresh }) {
  const s = useStrings("mootimetertool_wordcloud", ["submit", "type_answer"]);
  const m = useStrings("mod_mootimeter", ["delete_single_answer_dialog_title", "delete_single_answer_dialog_question"]);
  const c = useStrings("core", ["delete"]);
  const data = useResource(COMPONENT, `/pages/${page.id}`, [settingsVersion, answersVersion]);
  const [answer, setAnswer] = useState("");
  const [problem, setProblem] = useState("");
  if (!data) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
      lineNumber: 48,
      columnNumber: 16
    }, this);
  }
  const submit = /* @__PURE__ */ __name(async (e) => {
    e.preventDefault();
    try {
      await post(COMPONENT, `/pages/${page.id}/answers`, { text: answer });
      setAnswer("");
      refresh();
    } catch (error) {
      if (error instanceof ApiError && [409, 422].includes(error.status)) {
        setProblem(error.message);
      } else {
        displayError(error);
      }
    }
  }, "submit");
  const remove = /* @__PURE__ */ __name(async (answerid) => {
    if (await confirmDelete(m.delete_single_answer_dialog_title, m.delete_single_answer_dialog_question)) {
      removeResource(COMPONENT, `/pages/${page.id}/answers/${answerid}`).then(refresh).catch(displayError);
    }
  }, "remove");
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-colcontent-preview mootimeter-width-s", children: [
    /* @__PURE__ */ jsxDEV(QuestionHeader, { page, children: problem && /* @__PURE__ */ jsxDEV(Notification, { type: "warning", icon: "fa-exclamation", text: problem }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
      lineNumber: 75,
      columnNumber: 29
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
      lineNumber: 74,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "filter_mathjaxloader_equation", id: "mtmt_wordcloud_pills", children: data.ownAnswers.map((item) => /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-pill mootimeter-pill-inline", children: /* @__PURE__ */ jsxDEV("span", { children: [
      item.text,
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "btn btn-link p-0 ms-1",
          title: c.delete,
          "aria-label": `${c.delete}: ${item.text}`,
          onClick: () => remove(item.id),
          children: /* @__PURE__ */ jsxDEV("i", { className: "fa fa-times fa-light", "aria-hidden": "true" }, void 0, false, {
            fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
            lineNumber: 90,
            columnNumber: 33
          }, this)
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
          lineNumber: 83,
          columnNumber: 29
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
      lineNumber: 81,
      columnNumber: 25
    }, this) }, item.id, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
      lineNumber: 80,
      columnNumber: 21
    }, this)) }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
      lineNumber: 78,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("form", { onSubmit: submit, className: "d-flex flex-column gap-3", children: [
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          type: "text",
          className: "mootimeter-input mootimeterfullwidth mtmt-wc-answerinput",
          "aria-label": s.type_answer,
          placeholder: s.type_answer,
          value: answer,
          autoFocus: true,
          onChange: (e) => {
            setAnswer(e.target.value);
            setProblem("");
          }
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
          lineNumber: 98,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV("button", { type: "submit", className: "mootimeter-btn mootimeter-btn-full", children: s.submit }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
        lineNumber: 110,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
      lineNumber: 97,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/content.tsx",
    lineNumber: 73,
    columnNumber: 9
  }, this);
}
__name(Content, "Content");
export {
  Content as default
};
//# sourceMappingURL=content.dev.js.map
