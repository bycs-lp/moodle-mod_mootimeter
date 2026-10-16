var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Results chart of the quiz and poll tool, drawn with the Chart.js of Moodle core.
 *
 * @module     mootimetertool_quiz/results
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef } from "react";
import { requireAsync } from "@moodle/lms/core/amd";
import { Loading } from "@moodle/lms/mod_mootimeter/lib/components";
import { useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { componentOf } from "./types";
const Chart = await requireAsync("core/chartjs");
Chart.defaults.font.size = 25;
const getChartConfig = /* @__PURE__ */ __name((results, label) => {
  const config = buildChartConfig(results, label);
  config.options.maintainAspectRatio = false;
  return config;
}, "getChartConfig");
const buildChartConfig = /* @__PURE__ */ __name((results, label) => {
  const integerticks = { ticks: { stepSize: 1 } };
  const dataset = { label, data: results.values, backgroundColor: results.colors };
  switch (results.visualization) {
    case "pie":
      return {
        type: "pie",
        data: { labels: results.labels, datasets: [dataset] },
        options: { responsive: true, plugins: { legend: { position: "right" } } }
      };
    case "line":
      return {
        type: "line",
        data: {
          labels: results.labels,
          datasets: [{ ...dataset, pointStyle: "circle", pointRadius: 10, pointHoverRadius: 15 }]
        },
        options: { indexAxis: "x", scales: { x: { min: 0, ...integerticks } } }
      };
    case "bar":
      return {
        type: "bar",
        data: { labels: results.labels, datasets: [{ ...dataset, borderRadius: 20 }] },
        options: { indexAxis: "y", scales: { x: integerticks } }
      };
    default:
      return {
        type: "bar",
        data: { labels: results.labels, datasets: [{ ...dataset, borderRadius: 20 }] },
        options: { indexAxis: "x", scales: { x: integerticks, y: integerticks } }
      };
  }
}, "buildChartConfig");
function ResultsView({ page, settingsVersion, answersVersion }) {
  const s = useStrings("mootimetertool_quiz", ["no_answer_due_to_showteacherpermission", "show_results"]);
  const response = useResource(componentOf(page), `/pages/${page.id}/results`, [settingsVersion, answersVersion]);
  const canvas = useRef(null);
  const chart = useRef(null);
  const label = new DOMParser().parseFromString(page.questionHtml, "text/html").body.textContent ?? "";
  const results = response?.available ? {
    visualization: response.visualization,
    labels: response.options.map((option) => option.label),
    values: response.options.map((option) => option.count),
    colors: response.colors
  } : null;
  const resultskey = JSON.stringify(results);
  useEffect(() => {
    if (!results || !canvas.current) {
      return;
    }
    const config = getChartConfig(results, label);
    const current = chart.current;
    if (current && current.canvas === canvas.current && current.config.type === config.type) {
      current.data = config.data;
      current.options = config.options;
      current.update("none");
      return;
    }
    current?.destroy();
    chart.current = new Chart(canvas.current, config);
  }, [resultskey, label]);
  useEffect(() => () => chart.current?.destroy(), []);
  if (!response) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/results.tsx",
      lineNumber: 137,
      columnNumber: 16
    }, this);
  }
  if (!results) {
    return /* @__PURE__ */ jsxDEV("div", { className: "mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl mootimeter-vertical-align", children: s.no_answer_due_to_showteacherpermission }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/results.tsx",
      lineNumber: 142,
      columnNumber: 13
    }, this);
  }
  return /* @__PURE__ */ jsxDEV(
    "div",
    {
      className: "mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl mootimeter-vertical-align",
      id: "mtmt_quiz_results_wrapper",
      children: /* @__PURE__ */ jsxDEV("div", { className: "position-relative w-100 h-100", children: /* @__PURE__ */ jsxDEV("canvas", { ref: canvas, role: "img", "aria-label": `${s.show_results}: ${label}`, children: /* @__PURE__ */ jsxDEV("ul", { children: results.labels.map((text, index) => /* @__PURE__ */ jsxDEV("li", { children: [
        text,
        ": ",
        results.values[index]
      ] }, index, true, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/results.tsx",
        lineNumber: 154,
        columnNumber: 62
      }, this)) }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/results.tsx",
        lineNumber: 153,
        columnNumber: 21
      }, this) }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/results.tsx",
        lineNumber: 152,
        columnNumber: 17
      }, this) }, void 0, false, {
        fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/results.tsx",
        lineNumber: 151,
        columnNumber: 13
      }, this)
    },
    void 0,
    false,
    {
      fileName: "public/mod/mootimeter/tools/quiz/js/esm/src/results.tsx",
      lineNumber: 149,
      columnNumber: 9
    },
    this
  );
}
__name(ResultsView, "ResultsView");
export {
  ResultsView as default
};
//# sourceMappingURL=results.dev.js.map
