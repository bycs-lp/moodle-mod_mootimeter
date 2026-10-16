var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * UI components shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * The markup reuses the CSS classes of styles.scss.
 *
 * @module     mod_mootimeter/lib/components
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import React, { useEffect, useRef, useState } from "react";
import { Pagination } from "@moodlehq/design-system";
import { patch } from "./api";
import { useStrings } from "./hooks";
import { confirmDelete, displayError, formatUserDates, notifyFilterContentUpdated } from "./moodle";
function IconButton({ icon, title, onClick, active = false, disabled = false }) {
  return /* @__PURE__ */ jsxDEV(
    "button",
    {
      type: "button",
      className: `mootimeter-btn-icon-only-rounded${active ? " active" : ""}`,
      title,
      "aria-label": title,
      "aria-pressed": active,
      disabled,
      onClick,
      children: /* @__PURE__ */ jsxDEV("i", { className: `icon fa ${icon} fa-light`, "aria-hidden": "true" }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 59,
        columnNumber: 13
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 50,
      columnNumber: 9
    },
    this
  );
}
__name(IconButton, "IconButton");
function TransparentButton({ icon, title, onClick, className = "" }) {
  return /* @__PURE__ */ jsxDEV(
    "button",
    {
      type: "button",
      className: `mootimeter-btn-icon-only-transparent ${className}`,
      title,
      "aria-label": title,
      onClick,
      children: /* @__PURE__ */ jsxDEV("i", { className: `icon fa ${icon} fa-light`, "aria-hidden": "true" }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 87,
        columnNumber: 13
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 80,
      columnNumber: 9
    },
    this
  );
}
__name(TransparentButton, "TransparentButton");
function Notification({ type, text, icon = "", iconclass = "" }) {
  return /* @__PURE__ */ jsxDEV("div", { className: `mootimeter-colcontent-preview-notification notification-${type}`, role: "status", children: [
    /* @__PURE__ */ jsxDEV("span", { className: iconclass, children: /* @__PURE__ */ jsxDEV("i", { className: `icon fa ${icon} fa-light`, "aria-hidden": "true" }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 108,
      columnNumber: 41
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 108,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("p", { children: text }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 109,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 107,
    columnNumber: 9
  }, this);
}
__name(Notification, "Notification");
function FormattedHtml({ html, tag = "span", className = "" }) {
  const ref = useRef(null);
  useEffect(() => {
    notifyFilterContentUpdated(ref.current);
  }, [html]);
  return React.createElement(tag, { ref, className, dangerouslySetInnerHTML: { __html: html } });
}
__name(FormattedHtml, "FormattedHtml");
function QuestionHeader({ page, children }) {
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-colcontent-preview-header filter_mathjaxloader_equation", children: [
    /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-pill", children: /* @__PURE__ */ jsxDEV("span", { children: page.toolName }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 148,
      columnNumber: 46
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 148,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(FormattedHtml, { tag: "h4", html: page.questionHtml }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 149,
      columnNumber: 13
    }, this),
    children
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 147,
    columnNumber: 9
  }, this);
}
__name(QuestionHeader, "QuestionHeader");
function Choice({ type, checked, label, onChange, name, disabled = false, wrapperclass = "" }) {
  const prefix = type === "checkbox" ? "mootimeter-checkbox" : "mootimeter-radio-btn";
  const iconprefix = type === "checkbox" ? "checkbox" : "radio-btn";
  return /* @__PURE__ */ jsxDEV("div", { className: `${prefix} ${wrapperclass}`, children: /* @__PURE__ */ jsxDEV("label", { children: [
    /* @__PURE__ */ jsxDEV(
      "input",
      {
        type,
        className: type === "checkbox" ? "mootimeter-checkbox-switch" : "mootimeter-radio-btn",
        name,
        checked,
        disabled,
        onChange: (e) => onChange(e.target.checked)
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 179,
        columnNumber: 17
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("span", { className: `${iconprefix}-icon-wrapper`, children: /* @__PURE__ */ jsxDEV("i", { className: `icon fa fa-check fa-light ${iconprefix}-icon`, "aria-hidden": "true" }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 188,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 187,
      columnNumber: 17
    }, this),
    /* @__PURE__ */ jsxDEV("span", { children: label }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 190,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 178,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 177,
    columnNumber: 9
  }, this);
}
__name(Choice, "Choice");
function TextSetting({ value, placeholder, label, onSave }) {
  const [current, setCurrent] = useState(value);
  const focused = useRef(false);
  useEffect(() => {
    if (!focused.current) {
      setCurrent(value);
    }
  }, [value]);
  const store = /* @__PURE__ */ __name(() => {
    focused.current = false;
    if (current === value) {
      return;
    }
    onSave(current).catch(displayError);
  }, "store");
  return /* @__PURE__ */ jsxDEV(
    "input",
    {
      type: "text",
      className: "mootimeter-input mootimeterfullwidth mootimeter_settings_selector",
      "aria-label": label,
      placeholder,
      value: current,
      onFocus: () => {
        focused.current = true;
      },
      onChange: (e) => setCurrent(e.target.value),
      onBlur: store,
      onKeyDown: (e) => e.key === "Enter" && e.target.blur()
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 231,
      columnNumber: 9
    },
    this
  );
}
__name(TextSetting, "TextSetting");
function NumberSetting({ value, label, min = 0, onSave }) {
  const [current, setCurrent] = useState(String(value));
  useEffect(() => setCurrent(String(value)), [value]);
  const store = /* @__PURE__ */ __name((newvalue) => {
    const cleaned = Math.max(min, Number.isFinite(newvalue) ? Math.trunc(newvalue) : min);
    setCurrent(String(cleaned));
    if (cleaned === value) {
      return;
    }
    onSave(cleaned).catch(displayError);
  }, "store");
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-number-input", children: /* @__PURE__ */ jsxDEV("label", { children: [
    /* @__PURE__ */ jsxDEV("span", { className: "mootimeter-number-input-wrapper", children: [
      /* @__PURE__ */ jsxDEV(
        "input",
        {
          type: "number",
          className: "mootimeter_settings_selector",
          min,
          value: current,
          onChange: (e) => setCurrent(e.target.value),
          onBlur: () => store(Number(current)),
          onKeyDown: (e) => e.key === "Enter" && e.target.blur()
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 277,
          columnNumber: 21
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "mootimeter-btn-icon-only-transparent mootimeter-number-input-btn-up",
          "aria-hidden": "true",
          tabIndex: -1,
          onMouseDown: (e) => e.preventDefault(),
          onClick: () => store(Number(current) + 1),
          children: /* @__PURE__ */ jsxDEV("i", { className: "icon fa fa-sort-up fa-light", "aria-hidden": "true" }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 294,
            columnNumber: 25
          }, this)
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 286,
          columnNumber: 21
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "mootimeter-btn-icon-only-transparent mootimeter-number-input-btn-down",
          "aria-hidden": "true",
          tabIndex: -1,
          onMouseDown: (e) => e.preventDefault(),
          onClick: () => store(Number(current) - 1),
          children: /* @__PURE__ */ jsxDEV("i", { className: "icon fa fa-sort-down fa-light", "aria-hidden": "true" }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 304,
            columnNumber: 25
          }, this)
        },
        void 0,
        false,
        {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 296,
          columnNumber: 21
        },
        this
      )
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 276,
      columnNumber: 17
    }, this),
    label
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 275,
    columnNumber: 13
  }, this) }, void 0, false, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 274,
    columnNumber: 9
  }, this);
}
__name(NumberSetting, "NumberSetting");
function CheckboxSetting({ value, label, disabled = false, onSave }) {
  const [current, setCurrent] = useState(value);
  useEffect(() => setCurrent(value), [value]);
  const change = /* @__PURE__ */ __name((checked) => {
    setCurrent(checked);
    onSave(checked).catch((error) => {
      setCurrent(value);
      displayError(error);
    });
  }, "change");
  return /* @__PURE__ */ jsxDEV(Choice, { type: "checkbox", checked: current, label, disabled, onChange: change }, void 0, false, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 338,
    columnNumber: 12
  }, this);
}
__name(CheckboxSetting, "CheckboxSetting");
function AnonymousModeSetting({ page, onStored }) {
  const s = useStrings("mod_mootimeter", ["anonymousmode", "anonymousmode_desc"]);
  return /* @__PURE__ */ jsxDEV(
    CheckboxSetting,
    {
      value: page.anonymousMode,
      label: `${s.anonymousmode} ${s.anonymousmode_desc}`,
      disabled: page.hasAnswers,
      onSave: (anonymousMode) => patch("mod_mootimeter", `/pages/${page.id}`, { anonymousMode }).then(() => onStored?.())
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 355,
      columnNumber: 9
    },
    this
  );
}
__name(AnonymousModeSetting, "AnonymousModeSetting");
function Divider() {
  return /* @__PURE__ */ jsxDEV("hr", { className: "divider" }, void 0, false, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 370,
    columnNumber: 12
  }, this);
}
__name(Divider, "Divider");
function Loading() {
  const s = useStrings("mod_mootimeter", ["loading"]);
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-render-loading", role: "status", children: [
    /* @__PURE__ */ jsxDEV("span", { className: "spinner-border spinner-border-sm", "aria-hidden": "true" }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 382,
      columnNumber: 13
    }, this),
    " ",
    s.loading
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 381,
    columnNumber: 9
  }, this);
}
__name(Loading, "Loading");
function useRowTimes(rows) {
  const [times, setTimes] = useState({});
  const key = rows.map((row) => `${row.id}:${row.createdAt}`).join(",");
  useEffect(() => {
    let active = true;
    formatUserDates(rows.map((row) => Math.floor(Date.parse(row.createdAt) / 1e3))).then((formatted) => active && setTimes(Object.fromEntries(rows.map((row, index) => [row.id, formatted[index]])))).catch(displayError);
    return () => {
      active = false;
    };
  }, [key]);
  return times;
}
__name(useRowTimes, "useRowTimes");
function OverviewTable(props) {
  const { rows, offset, totalPages, currentPage, onPageChange, renderAnswer, onDelete, deleteTitle, deleteQuestion } = props;
  const s = useStrings("core", ["name", "answer", "date", "time", "options", "delete", "nothingtodisplay", "previous", "next"]);
  const m = useStrings("mod_mootimeter", ["show_answer_overview"]);
  const times = useRowTimes(rows);
  const remove = /* @__PURE__ */ __name(async (row) => {
    if (await confirmDelete(deleteTitle, deleteQuestion)) {
      onDelete(row).catch(displayError);
    }
  }, "remove");
  return /* @__PURE__ */ jsxDEV("div", { className: `mootimeter-colcontent-preview mootimeter-width-xl mootimeter-height-auto ${props.className ?? ""}`, children: [
    /* @__PURE__ */ jsxDEV("table", { className: "table", children: [
      /* @__PURE__ */ jsxDEV("caption", { className: "sr-only", children: m.show_answer_overview }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 451,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("thead", { children: /* @__PURE__ */ jsxDEV("tr", { children: [
        /* @__PURE__ */ jsxDEV("th", { scope: "col", children: "#" }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 454,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("th", { scope: "col", children: s.name }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 455,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("th", { scope: "col", children: s.answer }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 456,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("th", { scope: "col", children: [
          s.date,
          " / ",
          s.time
        ] }, void 0, true, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 457,
          columnNumber: 25
        }, this),
        /* @__PURE__ */ jsxDEV("th", { scope: "col", children: s.options }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 458,
          columnNumber: 25
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 453,
        columnNumber: 21
      }, this) }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 452,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("tbody", { children: [
        !rows.length && /* @__PURE__ */ jsxDEV("tr", { children: /* @__PURE__ */ jsxDEV("td", { colSpan: 5, children: s.nothingtodisplay }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 464,
          columnNumber: 29
        }, this) }, void 0, false, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 463,
          columnNumber: 25
        }, this),
        rows.map((row, index) => /* @__PURE__ */ jsxDEV("tr", { children: [
          /* @__PURE__ */ jsxDEV("td", { children: offset + index + 1 }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 469,
            columnNumber: 29
          }, this),
          /* @__PURE__ */ jsxDEV("td", { children: row.fullName }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 470,
            columnNumber: 29
          }, this),
          /* @__PURE__ */ jsxDEV("td", { children: renderAnswer(row) }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 471,
            columnNumber: 29
          }, this),
          /* @__PURE__ */ jsxDEV("td", { children: times[row.id] ?? "" }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 472,
            columnNumber: 29
          }, this),
          /* @__PURE__ */ jsxDEV("td", { children: /* @__PURE__ */ jsxDEV(IconButton, { icon: "fa-trash", title: s.delete, onClick: () => remove(row) }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 474,
            columnNumber: 33
          }, this) }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
            lineNumber: 473,
            columnNumber: 29
          }, this)
        ] }, row.id, true, {
          fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
          lineNumber: 468,
          columnNumber: 25
        }, this))
      ] }, void 0, true, {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 461,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
      lineNumber: 450,
      columnNumber: 13
    }, this),
    totalPages > 1 && /* @__PURE__ */ jsxDEV(
      Pagination,
      {
        totalPages,
        currentPage,
        onPageChange,
        ariaLabel: m.show_answer_overview,
        previousPageLabel: s.previous,
        nextPageLabel: s.next
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
        lineNumber: 481,
        columnNumber: 17
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/lib/components.tsx",
    lineNumber: 449,
    columnNumber: 9
  }, this);
}
__name(OverviewTable, "OverviewTable");
export {
  AnonymousModeSetting,
  CheckboxSetting,
  Choice,
  Divider,
  FormattedHtml,
  IconButton,
  Loading,
  Notification,
  NumberSetting,
  OverviewTable,
  QuestionHeader,
  TextSetting,
  TransparentButton
};
//# sourceMappingURL=components.dev.js.map
