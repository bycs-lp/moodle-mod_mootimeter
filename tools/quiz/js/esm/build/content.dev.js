var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Question view of the quiz and poll tool.
 *
 * @module     mootimetertool_quiz/content
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { ApiError, post } from "@moodle/lms/mod_mootimeter/lib/api";
import { Choice, FormattedHtml, Loading, Notification, QuestionHeader } from "@moodle/lms/mod_mootimeter/lib/components";
import { useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { displayError } from "@moodle/lms/mod_mootimeter/lib/moodle";
import { componentOf } from "./types";
function Content({ page, isModerator, settingsVersion, refresh }) {
  const s = useStrings("mootimetertool_quiz", [
    "submit_answer",
    "sendbutton_context_one_answers_possible",
    "sendbutton_context_more_answers_possible",
    "enter_answeroption"
  ]);
  const m = useStrings("mod_mootimeter", ["notification_success_store_answer"]);
  const data = useResource(componentOf(page), `/pages/${page.id}`, [settingsVersion]);
  const [selected, setSelected] = useState([]);
  const [result, setResult] = useState(null);
  const initialised = useRef(false);
  useEffect(() => {
    if (data && !initialised.current) {
      setSelected(data.selectedOptionIds);
      initialised.current = true;
    }
  }, [data]);
  if (!data) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
      lineNumber: 63,
      columnNumber: 16
    }, this);
  }
  const single = data.maxAnswers === 1;
  const optionids = data.options.map((option) => option.id);
  const toggle = /* @__PURE__ */ __name((optionid, checked) => {
    setResult(null);
    if (single) {
      setSelected(checked ? [optionid] : []);
    } else {
      setSelected((old) => checked ? [...old, optionid] : old.filter((id) => id !== optionid));
    }
  }, "toggle");
  const submit = /* @__PURE__ */ __name(async () => {
    try {
      await post(componentOf(page), `/pages/${page.id}/responses`, {
        optionIds: selected.filter((id) => optionids.includes(id))
      });
      setResult({ success: true, message: m.notification_success_store_answer });
      refresh();
    } catch (error) {
      if (error instanceof ApiError && [409, 422].includes(error.status)) {
        setResult({ success: false, message: error.message });
      } else {
        displayError(error);
      }
    }
  }, "submit");
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-colcontent-preview mootimeter-width-s", children: [
    /* @__PURE__ */ jsxDEV(QuestionHeader, { page, children: result && /* @__PURE__ */ jsxDEV(
      Notification,
      {
        type: result.success ? "success" : "warning",
        icon: result.success ? "fa-check" : "fa-exclamation",
        text: result.message
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
        lineNumber: 98,
        columnNumber: 21
      },
      this
    ) }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
      lineNumber: 96,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-colcontent-preview-options filter_mathjaxloader_equation", children: data.options.map((option) => /* @__PURE__ */ jsxDEV(
      Choice,
      {
        type: single ? "radio" : "checkbox",
        name: `mtmt_quiz_${page.id}`,
        checked: selected.includes(option.id),
        disabled: data.correctionShown,
        wrapperclass: (data.correctionShown ? "mootimeter-highlighter" : "") + (data.correctionShown && option.isCorrect ? " mootimeter-success" : ""),
        label: option.textHtml || !isModerator ? /* @__PURE__ */ jsxDEV(FormattedHtml, { html: option.textHtml }, void 0, false, {
          fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
          lineNumber: 119,
          columnNumber: 31
        }, this) : s.enter_answeroption,
        onChange: (checked) => toggle(option.id, checked)
      },
      option.id,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
        lineNumber: 108,
        columnNumber: 21
      },
      this
    )) }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
      lineNumber: 106,
      columnNumber: 13
    }, this),
    !data.correctionShown && /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-colcontent-preview-send", children: [
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "mootimeter-btn mootimeter-btn-full", onClick: submit, children: s.submit_answer }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
        lineNumber: 128,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV("small", { children: single ? s.sendbutton_context_one_answers_possible : s.sendbutton_context_more_answers_possible }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
        lineNumber: 131,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
      lineNumber: 127,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/content.tsx",
    lineNumber: 95,
    columnNumber: 9
  }, this);
}
__name(Content, "Content");
export {
  Content as default
};
//# sourceMappingURL=content.dev.js.map
