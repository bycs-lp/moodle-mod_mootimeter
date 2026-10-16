var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * Wordcloud of the counted answers, laid out with d3-cloud.
 *
 * @module     mootimetertool_wordcloud/results
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { getString } from "@moodle/lms/core/stringUtils";
import { Loading } from "@moodle/lms/mod_mootimeter/lib/components";
import { useResource, useStrings } from "@moodle/lms/mod_mootimeter/lib/hooks";
import { notifyFilterContentUpdated } from "@moodle/lms/mod_mootimeter/lib/moodle";
import { layoutCloud } from "./cloudlayout";
import { COMPONENT } from "./types";
const MIN_FONT_SIZE = 16;
const RATIO = 4;
const FILL_TARGET = 0.65;
const FREQUENCY_EXPONENT = 0.8;
const CHAR_WIDTH = 0.6;
const FIT_CHAR_WIDTH = 0.65;
const DENSITY_DIVISOR = 3;
const MAX_SHRINK_ATTEMPTS = 3;
const SHRINK_STEP = 0.85;
const FONT_FAMILY = "Lexend, sans-serif";
const COLORS = ["#c0580a", "#1a5fa8", "#b33000", "#2e7d32", "#6b3fa0", "#c2185b"];
const layoutWords = /* @__PURE__ */ __name((answers, width, height) => {
  const sorted = [...answers].sort((a, b) => b.count - a.count);
  const maxcount = sorted[0].count;
  const mincount = sorted[sorted.length - 1].count;
  const spread = maxcount - mincount;
  const densitycap = Math.min(width, height) / DENSITY_DIVISOR;
  const words = sorted.map(({ text, count }) => {
    const frequency = spread > 0 ? (count - mincount) / spread : 0;
    return {
      text,
      count,
      multiplier: 1 + (RATIO - 1) * Math.pow(frequency, FREQUENCY_EXPONENT),
      rotate: Math.random() > 0.8 ? -90 : 0
    };
  });
  const space = words.reduce((sum, word) => sum + word.text.length * CHAR_WIDTH * word.multiplier * word.multiplier, 0);
  const basesize = Math.max(MIN_FONT_SIZE, Math.sqrt(FILL_TARGET * width * height / space));
  let running = null;
  const state = { stopped: false };
  const promise = (async () => {
    let placed = [];
    for (let attempt = 0, shrink = 1; attempt <= MAX_SHRINK_ATTEMPTS && !state.stopped; attempt++, shrink *= SHRINK_STEP) {
      const cloudwords = words.map(({ text, count, multiplier, rotate }) => {
        const lengthcap = (rotate === 0 ? width : height) / (text.length * FIT_CHAR_WIDTH);
        return { text, count, rotate, size: Math.min(basesize * multiplier, densitycap, lengthcap) * shrink };
      });
      const layout = layoutCloud(cloudwords, { width, height, font: FONT_FAMILY, padding: 4 });
      running = layout;
      placed = await layout.promise;
      if (placed.length === words.length) {
        break;
      }
    }
    return placed;
  })();
  return {
    promise,
    stop: /* @__PURE__ */ __name(() => {
      state.stopped = true;
      running?.stop();
    }, "stop")
  };
}, "layoutWords");
function ResultsView({ page, settingsVersion, answersVersion }) {
  const s = useStrings("mootimetertool_wordcloud", ["no_answer_due_to_showteacherpermission", "no_answer"]);
  const results = useResource(COMPONENT, `/pages/${page.id}/results`, [settingsVersion, answersVersion]);
  const container = useRef(null);
  const [layout, setLayout] = useState(null);
  const [arialabel, setArialabel] = useState("");
  const words = results?.available ? results.words : null;
  const wordskey = JSON.stringify(words);
  useEffect(() => {
    if (!words || !words.length || !container.current) {
      setLayout(null);
      return void 0;
    }
    const width = container.current.clientWidth;
    const height = container.current.clientHeight;
    let active = true;
    let running = null;
    const draw = /* @__PURE__ */ __name(async () => {
      await document.fonts?.ready;
      if (!active) {
        return;
      }
      const current = layoutWords(words, width, height);
      running = current;
      const placed = await current.promise;
      if (active) {
        setLayout({ width, height, words: placed });
        setArialabel(await getString("wordcloud_aria_label", "mootimetertool_wordcloud", words.length));
      }
    }, "draw");
    draw();
    return () => {
      active = false;
      running?.stop();
    };
  }, [wordskey]);
  useEffect(() => {
    notifyFilterContentUpdated(container.current);
  }, [layout]);
  if (!results) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
      lineNumber: 159,
      columnNumber: 16
    }, this);
  }
  if (!results.available) {
    return /* @__PURE__ */ jsxDEV("div", { className: "mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl mootimeter-vertical-align", children: s.no_answer_due_to_showteacherpermission }, void 0, false, {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
      lineNumber: 164,
      columnNumber: 13
    }, this);
  }
  const listid = `mtmt_wordlist_${page.id}`;
  return /* @__PURE__ */ jsxDEV(
    "div",
    {
      className: "mootimeterfullwidth mootimeter-colcontent-preview mootimeter-width-xl",
      ref: container,
      style: { display: "block" },
      children: [
        !results.words.length && /* @__PURE__ */ jsxDEV("p", { children: s.no_answer }, void 0, false, {
          fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
          lineNumber: 174,
          columnNumber: 39
        }, this),
        layout && /* @__PURE__ */ jsxDEV(Fragment, { children: [
          /* @__PURE__ */ jsxDEV(
            "svg",
            {
              width: "100%",
              viewBox: `${-layout.width / 2} ${-layout.height / 2} ${layout.width} ${layout.height}`,
              role: "img",
              "aria-label": arialabel,
              "aria-describedby": listid,
              children: /* @__PURE__ */ jsxDEV("g", { children: layout.words.map((word, index) => /* @__PURE__ */ jsxDEV(
                "text",
                {
                  transform: `translate(${word.x}, ${word.y})rotate(${word.rotate})`,
                  fontFamily: FONT_FAMILY,
                  fontSize: `${word.size}px`,
                  textAnchor: "middle",
                  fill: COLORS[index % COLORS.length],
                  children: [
                    /* @__PURE__ */ jsxDEV("title", { children: `${word.text}: ${word.count}` }, void 0, false, {
                      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
                      lineNumber: 194,
                      columnNumber: 37
                    }, this),
                    word.text
                  ]
                },
                word.text,
                true,
                {
                  fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
                  lineNumber: 186,
                  columnNumber: 33
                },
                this
              )) }, void 0, false, {
                fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
                lineNumber: 184,
                columnNumber: 25
              }, this)
            },
            void 0,
            false,
            {
              fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
              lineNumber: 177,
              columnNumber: 21
            },
            this
          ),
          /* @__PURE__ */ jsxDEV("ul", { id: listid, className: "sr-only", children: layout.words.map((word) => /* @__PURE__ */ jsxDEV("li", { children: `${word.text}: ${word.count}` }, word.text, false, {
            fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
            lineNumber: 201,
            columnNumber: 53
          }, this)) }, void 0, false, {
            fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
            lineNumber: 200,
            columnNumber: 21
          }, this)
        ] }, void 0, true, {
          fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
          lineNumber: 176,
          columnNumber: 17
        }, this)
      ]
    },
    void 0,
    true,
    {
      fileName: "public/mod/mootimeter/tools/wordcloud/js/esm/src/results.tsx",
      lineNumber: 172,
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
