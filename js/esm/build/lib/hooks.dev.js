var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * React hooks shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * @module     mod_mootimeter/lib/hooks
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useState } from "react";
import { getStrings } from "@moodle/lms/core/stringUtils";
import { ApiError, get } from "./api";
import { displayError } from "./moodle";
function useStrings(component, keys) {
  const [strings, setStrings] = useState({});
  const keylist = keys.join(",");
  useEffect(() => {
    let active = true;
    getStrings(keys.map((key) => ({ key, component }))).then((values) => active && setStrings(Object.fromEntries(keys.map((key, index) => [key, values[index]])))).catch(displayError);
    return () => {
      active = false;
    };
  }, [component, keylist]);
  return new Proxy(strings, { get: /* @__PURE__ */ __name((target, key) => target[key] ?? "", "get") });
}
__name(useStrings, "useStrings");
function useResource(component, path, deps) {
  const [data, setData] = useState(null);
  useEffect(() => {
    let active = true;
    get(component, path).then((resource) => active && setData(resource)).catch((error) => {
      if (active && !(error instanceof ApiError && error.code === "pagelocked")) {
        displayError(error);
      }
    });
    return () => {
      active = false;
    };
  }, [component, path, ...deps]);
  return data;
}
__name(useResource, "useResource");
function useCollection(component, path, deps) {
  const [page, setPage] = useState(1);
  const separator = path.includes("?") ? "&" : "?";
  const collection = useResource(component, `${path}${separator}page=${page}`, deps);
  if (!collection) {
    return null;
  }
  const { pageSize, totalItems } = collection.pagination;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  if (page > totalPages) {
    setPage(totalPages);
  }
  return { items: collection.items, page, pageSize, totalPages, setPage };
}
__name(useCollection, "useCollection");
export {
  useCollection,
  useResource,
  useStrings
};
//# sourceMappingURL=hooks.dev.js.map
