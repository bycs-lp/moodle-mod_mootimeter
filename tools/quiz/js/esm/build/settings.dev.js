var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Settings of the quiz and poll tool.
 *
 * @module     mootimetertool_quiz/settings
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import { requireAsync } from "@moodle/lms/core/amd";
import config from "@moodle/lms/core/config";
import { getString } from "@moodle/lms/core/stringUtils";
import { patch, post, remove } from "@moodle/lms/mod_mootimeter/lib/api";
import {
  AnonymousModeSetting,
  Divider,
  Loading,
  Notification,
  NumberSetting,
  TransparentButton
} from "@moodle/lms/mod_mootimeter/lib/components";
import { useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { addToast, displayError } from "@moodle/lms/mod_mootimeter/lib/moodle";
import { componentOf } from "./types";
const VISUALIZATIONS = [
  { id: "pillar", icon: "chart-pillar", string: "visualization_pillar" },
  { id: "bar", icon: "chart-bar", string: "visualization_bar" },
  { id: "pie", icon: "chart-pie", string: "visualization_pie" }
];
function OptionEditor(props) {
  const { page, option, supportscorrect, placeholder, deletelabel, correctlabel, onChanged } = props;
  const [text, setText] = useState(option.text);
  const [iscorrect, setIscorrect] = useState(option.isCorrect);
  const path = `/pages/${page.id}/options/${option.id}`;
  useEffect(() => setText(option.text), [option.text]);
  useEffect(() => setIscorrect(option.isCorrect), [option.isCorrect]);
  const update = /* @__PURE__ */ __name((body) => {
    patch(componentOf(page), path, body).then(onChanged).catch(displayError);
  }, "update");
  return /* @__PURE__ */ jsxDEV("div", { className: "input-with-checkbox-icon", children: [
    supportscorrect && /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-checkbox", children: /* @__PURE__ */ jsxDEV("label", { children: [
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          type: "checkbox",
          className: "mootimeter-checkbox-switch",
          "aria-label": correctlabel,
          checked: iscorrect,
          onChange: (e) => {
            setIscorrect(e.target.checked);
            update({ isCorrect: e.target.checked });
          }
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
          lineNumber: 91,
          columnNumber: 25
        },
        this
      ),
      /* @__PURE__ */ jsxDEV("span", { className: "checkbox-icon-wrapper", children: /* @__PURE__ */ jsxDEV("i", { className: "icon fa fa-check fa-light checkbox-icon", "aria-hidden": "true" }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 102,
        columnNumber: 29
      }, this) }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 101,
        columnNumber: 25
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 90,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 89,
      columnNumber: 17
    }, this),
    /* @__PURE__ */ jsxDEV(
      "input",
      {
        type: "text",
        className: "mootimeter-input mootimeterfullwidth mootimeter-answer-options mootimeter_settings_selector",
        "aria-label": placeholder,
        placeholder,
        value: text,
        onChange: (e) => setText(e.target.value),
        onBlur: () => text !== option.text && update({ text }),
        onKeyDown: (e) => e.key === "Enter" && e.target.blur()
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 107,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(
      TransparentButton,
      {
        icon: "fa-close",
        title: deletelabel,
        className: "mtmt-remove-answeroption",
        onClick: () => {
          remove(componentOf(page), path).then(onChanged).catch(displayError);
        }
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 117,
        columnNumber: 13
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
    lineNumber: 87,
    columnNumber: 9
  }, this);
}
__name(OptionEditor, "OptionEditor");
function ImportOptions({ page, onImported }) {
  const s = useStrings("mootimetertool_quiz", ["import_options", "import_options_submit"]);
  const openForm = /* @__PURE__ */ __name(async () => {
    try {
      const ModalFormClass = await requireAsync("core_form/modalform");
      const form = new ModalFormClass({
        formClass: "mootimetertool_quiz\\form\\import_options",
        args: { pageid: page.id },
        modalConfig: { title: s.import_options },
        saveButtonText: s.import_options_submit
      });
      form.addEventListener(form.events.FORM_SUBMITTED, async (e) => {
        addToast(await getString("import_options_success", "mootimetertool_quiz", e.detail.count));
        onImported();
      });
      form.show();
    } catch (error) {
      displayError(error);
    }
  }, "openForm");
  return /* @__PURE__ */ jsxDEV("div", { className: "button-icon-with-text", onClick: openForm, children: [
    /* @__PURE__ */ jsxDEV(TransparentButton, { icon: "fa-file-csv", title: s.import_options, className: "mtmt-import-options" }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 164,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { children: s.import_options }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 165,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
    lineNumber: 163,
    columnNumber: 9
  }, this);
}
__name(ImportOptions, "ImportOptions");
function Settings({ page, settingsVersion, refresh }) {
  const s = useStrings("mootimetertool_quiz", [
    "answeroptions",
    "mark_correct_answer",
    "enter_answeroption",
    "add_question_option",
    "visualization",
    "visualization_pillar",
    "visualization_bar",
    "visualization_pie",
    "answers_max_number",
    "correct_answer"
  ]);
  const c = useStrings("core", ["settings", "delete"]);
  const data = useResource(componentOf(page), `/pages/${page.id}`, [settingsVersion]);
  if (!data) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 193,
      columnNumber: 16
    }, this);
  }
  const save = /* @__PURE__ */ __name((body) => patch(componentOf(page), `/pages/${page.id}`, body).then(refresh), "save");
  const addOption = /* @__PURE__ */ __name(() => {
    post(componentOf(page), `/pages/${page.id}/options`, {}).then(refresh).catch(displayError);
  }, "addOption");
  const setVisualization = /* @__PURE__ */ __name((visualization) => {
    save({ visualization }).catch(displayError);
  }, "setVisualization");
  return /* @__PURE__ */ jsxDEV(Fragment, { children: [
    /* @__PURE__ */ jsxDEV("p", { className: "text-bold", children: s.answeroptions }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 208,
      columnNumber: 13
    }, this),
    data.supportsCorrectOptions && /* @__PURE__ */ jsxDEV(
      Notification,
      {
        type: "info",
        icon: "fa-share fa-flip-vertical",
        iconclass: "mootimeter_rotate_110",
        text: s.mark_correct_answer
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 211,
        columnNumber: 17
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("div", { className: "input-with-checkbox-icon_wrapper", id: "mtmt-quiz-ao-wrapper", children: data.options.map((option) => /* @__PURE__ */ jsxDEV(
      OptionEditor,
      {
        page,
        option,
        supportscorrect: data.supportsCorrectOptions,
        placeholder: s.enter_answeroption,
        deletelabel: c.delete,
        correctlabel: s.correct_answer,
        onChanged: refresh
      },
      option.id,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 217,
        columnNumber: 21
      },
      this
    )) }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 215,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "button-icon-with-text", onClick: addOption, children: [
      /* @__PURE__ */ jsxDEV(TransparentButton, { icon: "fa-plus", title: s.add_question_option }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 231,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("p", { children: s.add_question_option }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 232,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 230,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(ImportOptions, { page, onImported: refresh }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 234,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(Divider, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 236,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-bold", children: s.visualization }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 238,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-visualization-options-wrapper", children: VISUALIZATIONS.map((visualization) => /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-settings-visualization-option", children: /* @__PURE__ */ jsxDEV(
      "button",
      {
        type: "button",
        className: `mootimeter-btn mootimeter-btn-icon mtmt_visualization_selector${data.visualization === visualization.id ? " active" : ""}`,
        title: s[visualization.string],
        "aria-label": s[visualization.string],
        "aria-pressed": data.visualization === visualization.id,
        onClick: () => setVisualization(visualization.id),
        children: /* @__PURE__ */ jsxDEV(
          "img",
          {
            src: `${config.wwwroot}/mod/mootimeter/tools/quiz/pix/${visualization.icon}.svg`,
            width: "24px",
            className: "m-1",
            alt: ""
          },
          void 0,
          false,
          {
            fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
            lineNumber: 251,
            columnNumber: 29
          },
          this
        )
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 242,
        columnNumber: 25
      },
      this
    ) }, visualization.id, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 241,
      columnNumber: 21
    }, this)) }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 239,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(Divider, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 262,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { className: "text-bold", children: c.settings }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 264,
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
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
        lineNumber: 265,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(AnonymousModeSetting, { page, onStored: refresh }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
      lineNumber: 270,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/settings.tsx",
    lineNumber: 207,
    columnNumber: 9
  }, this);
}
__name(Settings, "Settings");
export {
  Settings as default
};
//# sourceMappingURL=settings.dev.js.map
