var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Loads and renders a React component of a mootimetertool subplugin.
 *
 * @module     mod_mootimeter/toolcomponent
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import { Loading } from "./lib/components";
import { displayError } from "./lib/moodle";
function ToolComponent({ specifier, ...props }) {
  const [component, setComponent] = useState(null);
  useEffect(() => {
    if (!specifier) {
      return void 0;
    }
    let active = true;
    import(specifier).then((module) => active && setComponent({ specifier, Component: module.default })).catch(displayError);
    return () => {
      active = false;
    };
  }, [specifier]);
  if (!specifier) {
    return null;
  }
  if (!component || component.specifier !== specifier) {
    return /* @__PURE__ */ jsxDEV(Loading, {}, void 0, false, {
      fileName: "public/mod/mootimeter/js/esm/src/toolcomponent.tsx",
      lineNumber: 60,
      columnNumber: 16
    }, this);
  }
  const { Component } = component;
  return /* @__PURE__ */ jsxDEV(Component, { ...props }, void 0, false, {
    fileName: "public/mod/mootimeter/js/esm/src/toolcomponent.tsx",
    lineNumber: 63,
    columnNumber: 12
  }, this);
}
__name(ToolComponent, "ToolComponent");
export {
  ToolComponent as default
};
//# sourceMappingURL=toolcomponent.dev.js.map
