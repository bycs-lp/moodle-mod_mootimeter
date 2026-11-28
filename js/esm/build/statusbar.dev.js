var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Status bar above the page content: countdown and live participation.
 *
 * @module     mod_mootimeter/statusbar
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCountdown, useStrings } from "./lib/hooks";
const ANNOUNCEMENTS = [60, 30, 10, 0];
const fill = /* @__PURE__ */ __name((text, values) => {
  if (typeof values !== "object") {
    return text.replace("{$a}", String(values));
  }
  return Object.entries(values).reduce((result, [name, value]) => result.replace(`{$a->${name}}`, String(value)), text);
}, "fill");
const formatDuration = /* @__PURE__ */ __name((seconds) => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor(seconds % 3600 / 60);
  const rest = String(seconds % 60).padStart(2, "0");
  return hours ? `${hours}:${String(minutes).padStart(2, "0")}:${rest}` : `${minutes}:${rest}`;
}, "formatDuration");
function Countdown({ closesAt, serverOffset, onExpired }) {
  const s = useStrings("mod_mootimeter", ["countdown", "countdown_ended", "countdown_remaining"]);
  const remaining = useCountdown(closesAt, serverOffset, onExpired);
  if (remaining === null) {
    return null;
  }
  let announcement = "";
  if (ANNOUNCEMENTS.includes(remaining)) {
    announcement = remaining === 0 ? s.countdown_ended : fill(s.countdown_remaining, formatDuration(remaining));
  }
  return /* @__PURE__ */ jsxDEV("div", { className: `mootimeter-countdown${remaining <= 10 ? " mootimeter-countdown-ending" : ""}`, children: [
    /* @__PURE__ */ jsxDEV("i", { className: "icon fa fa-clock-o fa-light", "aria-hidden": "true" }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 85,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: s.countdown }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 86,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { "aria-hidden": "true", children: formatDuration(remaining) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 87,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", "aria-live": "polite", children: announcement }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 88,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
    lineNumber: 84,
    columnNumber: 9
  }, this);
}
__name(Countdown, "Countdown");
function ParticipationCounter({ participation, groups, allowAllGroups, onGroup }) {
  const s = useStrings("mod_mootimeter", [
    "participation",
    "participation_answered",
    "participation_enrolled",
    "participation_enrolled_group"
  ]);
  const c = useStrings("core", ["allparticipants", "group"]);
  const { answered, active, enrolled, groupId } = participation;
  const percent = active ? Math.min(100, Math.round(answered / active * 100)) : 0;
  const options = [...allowAllGroups ? [{ id: 0, name: c.allparticipants }] : [], ...groups];
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-participation", role: "status", "aria-label": s.participation, children: [
    options.length > 1 && /* @__PURE__ */ jsxDEV(
      "select",
      {
        className: "form-select form-select-sm w-auto",
        "aria-label": c.group,
        value: groupId,
        onChange: (e) => onGroup(Number(e.target.value)),
        children: options.map((option) => /* @__PURE__ */ jsxDEV("option", { value: option.id, children: option.name }, option.id, false, {
          fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
          lineNumber: 124,
          columnNumber: 46
        }, this))
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
        lineNumber: 118,
        columnNumber: 17
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("span", { children: fill(s.participation_answered, { answered, active }) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 127,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("span", { className: "mootimeter-participation-enrolled", children: [
      "\xB7 ",
      fill(groupId ? s.participation_enrolled_group : s.participation_enrolled, enrolled)
    ] }, void 0, true, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 128,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "progress mootimeter-participation-progress", "aria-hidden": "true", children: /* @__PURE__ */ jsxDEV("div", { className: "progress-bar", style: { width: `${percent}%` } }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 132,
      columnNumber: 17
    }, this) }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 131,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
    lineNumber: 116,
    columnNumber: 9
  }, this);
}
__name(ParticipationCounter, "ParticipationCounter");
const SEPARATEGROUPS = 1;
function StatusBar(props) {
  const { page, isModerator, serverOffset, participation, groupMode, groups, allowAllGroups, onGroup, onExpired } = props;
  const s = useStrings("mod_mootimeter", ["participation_nogroup"]);
  const withoutgroup = !isModerator && page.participationVisible && groupMode === SEPARATEGROUPS && !allowAllGroups && groups.length === 0;
  const showcounter = Boolean(participation?.available);
  if (!page.closesAt && !showcounter && !withoutgroup) {
    return null;
  }
  return /* @__PURE__ */ jsxDEV("div", { className: "mootimeter-statusbar", children: [
    /* @__PURE__ */ jsxDEV(Countdown, { closesAt: page.closesAt, serverOffset, onExpired }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 171,
      columnNumber: 13
    }, this),
    showcounter && participation && /* @__PURE__ */ jsxDEV(
      ParticipationCounter,
      {
        participation,
        groups,
        allowAllGroups,
        onGroup
      },
      void 0,
      false,
      {
        fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
        lineNumber: 173,
        columnNumber: 17
      },
      this
    ),
    withoutgroup && /* @__PURE__ */ jsxDEV("span", { className: "mootimeter-participation", children: s.participation_nogroup }, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
      lineNumber: 180,
      columnNumber: 30
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mootimeter/js/esm/src/statusbar.tsx",
    lineNumber: 170,
    columnNumber: 9
  }, this);
}
__name(StatusBar, "StatusBar");
export {
  StatusBar as default,
  formatDuration
};
//# sourceMappingURL=statusbar.dev.js.map
