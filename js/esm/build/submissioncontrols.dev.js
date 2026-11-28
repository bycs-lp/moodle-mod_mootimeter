var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Moderator controls of the submission: open, close and countdown.
 *
 * @module     mod_mootimeter/submissioncontrols
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { patch } from "./lib/api";
import { IconButton } from "./lib/components";
import { useStrings } from "./lib/hooks";
import { displayError } from "./lib/moodle";
import { formatDuration } from "./statusbar";
const PRESETS = [30, 60, 120, 300];
const EXTENSIONS = [30, 60];
function SubmissionControls({ page, refresh }) {
  const s = useStrings("mod_mootimeter", [
    "submission_open",
    "submission_close",
    "countdown",
    "countdown_cancel",
    "countdown_custom",
    "countdown_extend",
    "countdown_start",
    "countdown_start_default"
  ]);
  const [open, setOpen] = useState(false);
  const defaultseconds = page.countdownDefaultSeconds;
  const [custom, setCustom] = useState(defaultseconds ? String(defaultseconds) : "");
  useEffect(() => setCustom(defaultseconds ? String(defaultseconds) : ""), [defaultseconds]);
  const menu = useRef(null);
  const running = page.closesAt !== null;
  useEffect(() => {
    if (!open) {
      return void 0;
    }
    menu.current?.querySelector("button")?.focus();
    const close = /* @__PURE__ */ __name((e) => e.key === "Escape" && setOpen(false), "close");
    document.addEventListener("keydown", close);
    return () => document.removeEventListener("keydown", close);
  }, [open]);
  const save = /* @__PURE__ */ __name((body) => {
    setOpen(false);
    patch("mod_mootimeter", `/pages/${page.id}`, body).then(refresh).catch(displayError);
  }, "save");
  const startCustom = /* @__PURE__ */ __name((e) => {
    e.preventDefault();
    const seconds = Number(custom);
    if (seconds > 0) {
      save({ countdownSeconds: seconds });
    }
  }, "startCustom");
  return /* @__PURE__ */ jsxDEV(Fragment, { children: [
    /* @__PURE__ */ jsxDEV(
      IconButton,
      {
        icon: page.submissionOpen ? "fa-unlock" : "fa-lock",
        title: page.submissionOpen ? s.submission_close : s.submission_open,
        active: !page.submissionOpen,
        onClick: () => save({ submissionOpen: !page.submissionOpen })
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
        lineNumber: 87,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-countdown-control", children: [
      /* @__PURE__ */ jsxDEV(IconButton, { icon: "fa-clock-o", title: s.countdown, active: running, onClick: () => setOpen(!open) }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
        lineNumber: 94,
        columnNumber: 17
      }, this),
      open && /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-countdown-menu", ref: menu, role: "group", "aria-label": s.countdown, children: running ? /* @__PURE__ */ jsxDEV(Fragment, { children: [
        EXTENSIONS.map((seconds) => /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-secondary",
            onClick: () => save({ countdownExtendSeconds: seconds }),
            children: s.countdown_extend.replace("{$a}", formatDuration(seconds))
          },
          seconds,
          false,
          {
            fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
            lineNumber: 100,
            columnNumber: 37
          },
          this
        )),
        /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-outline-danger",
            onClick: () => save({ countdownSeconds: 0 }),
            children: s.countdown_cancel
          },
          void 0,
          false,
          {
            fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
            lineNumber: 105,
            columnNumber: 33
          },
          this
        )
      ] }, void 0, true, {
        fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
        lineNumber: 98,
        columnNumber: 29
      }, this) : /* @__PURE__ */ jsxDEV(Fragment, { children: [
        defaultseconds > 0 && /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-primary",
            onClick: () => save({ countdownSeconds: defaultseconds }),
            children: s.countdown_start_default.replace("{$a}", formatDuration(defaultseconds))
          },
          void 0,
          false,
          {
            fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
            lineNumber: 113,
            columnNumber: 37
          },
          this
        ),
        PRESETS.filter((seconds) => seconds !== defaultseconds).map((seconds) => /* @__PURE__ */ jsxDEV(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-secondary",
            onClick: () => save({ countdownSeconds: seconds }),
            children: formatDuration(seconds)
          },
          seconds,
          false,
          {
            fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
            lineNumber: 119,
            columnNumber: 37
          },
          this
        )),
        /* @__PURE__ */ jsxDEV("form", { className: "d-flex gap-1", onSubmit: startCustom, children: [
          /* @__PURE__ */ jsxDEV(
            "input",
            {
              type: "number",
              min: "5",
              className: "form-control form-control-sm",
              "aria-label": s.countdown_custom,
              placeholder: s.countdown_custom,
              value: custom,
              onChange: (e) => setCustom(e.target.value)
            },
            void 0,
            false,
            {
              fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
              lineNumber: 125,
              columnNumber: 37
            },
            this
          ),
          /* @__PURE__ */ jsxDEV("button", { type: "submit", className: "btn btn-sm btn-primary", children: s.countdown_start }, void 0, false, {
            fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
            lineNumber: 128,
            columnNumber: 37
          }, this)
        ] }, void 0, true, {
          fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
          lineNumber: 124,
          columnNumber: 33
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
        lineNumber: 111,
        columnNumber: 29
      }, this) }, void 0, false, {
        fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
        lineNumber: 96,
        columnNumber: 21
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
      lineNumber: 93,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/submissioncontrols.tsx",
    lineNumber: 86,
    columnNumber: 9
  }, this);
}
__name(SubmissionControls, "SubmissionControls");
export {
  SubmissionControls as default
};
//# sourceMappingURL=submissioncontrols.dev.js.map
