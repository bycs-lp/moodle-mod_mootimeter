var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Client of the REST API of mod_mootimeter and the mootimetertool subplugins.
 *
 * core/fetch rejects failed requests with the status text only, this client exposes the RFC 9457 problem details.
 *
 * @module     mod_mootimeter/lib/api
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import config from "@moodle/lms/core/config";
import Pending from "@moodle/lms/core/pending";
class ApiError extends Error {
  static {
    __name(this, "ApiError");
  }
  status;
  code;
  details;
  constructor(status, problem, fallback) {
    super(String(problem?.detail ?? problem?.message ?? fallback));
    this.name = "ApiError";
    this.status = status;
    this.code = String(problem?.code ?? problem?.errorcode ?? "");
    this.details = Array.isArray(problem?.details) ? problem.details : [];
  }
}
async function request(method, component, path, body) {
  const pending = new Pending(`${component}:${method} ${path}`);
  try {
    const headers = { "Accept": "application/json, application/problem+json" };
    if (body !== void 0) {
      headers["Content-Type"] = "application/json";
    }
    if (method !== "GET") {
      headers["X-Moodle-Sesskey"] = config.sesskey;
    }
    const response = await fetch(`${config.apibase}/rest/v2/${component}${path}`, {
      method,
      headers,
      credentials: "same-origin",
      body: body === void 0 ? void 0 : JSON.stringify(body)
    });
    if (response.status === 204) {
      return void 0;
    }
    const data = await response.json().catch(() => null);
    if (!response.ok) {
      throw new ApiError(response.status, data, response.statusText);
    }
    return data;
  } finally {
    pending.resolve();
  }
}
__name(request, "request");
const get = /* @__PURE__ */ __name((component, path) => request("GET", component, path), "get");
async function getAll(component, path) {
  const separator = path.includes("?") ? "&" : "?";
  const items = [];
  for (let page = 1; ; page++) {
    const collection = await get(component, `${path}${separator}page=${page}&pagesize=100`);
    items.push(...collection.items);
    if (!collection.items.length || items.length >= collection.pagination.totalItems) {
      return items;
    }
  }
}
__name(getAll, "getAll");
const post = /* @__PURE__ */ __name((component, path, body = {}) => request("POST", component, path, body), "post");
const put = /* @__PURE__ */ __name((component, path, body) => request("PUT", component, path, body), "put");
const patch = /* @__PURE__ */ __name((component, path, body) => request("PATCH", component, path, body), "patch");
const remove = /* @__PURE__ */ __name((component, path) => request("DELETE", component, path), "remove");
export {
  ApiError,
  get,
  getAll,
  patch,
  post,
  put,
  remove,
  request
};
//# sourceMappingURL=api.dev.js.map
