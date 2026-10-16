var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Moodle user interface services that are not available as ESM modules yet: dialogues, toasts, filters and the edit mode.
 *
 * @module     mod_mootimeter/lib/moodle
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { requireAsync } from "@moodle/lms/core/amd";
import { getString } from "@moodle/lms/core/stringUtils";
import { ApiError } from "./api";
async function formatUserDates(timestamps) {
  if (!timestamps.length) {
    return [];
  }
  const [UserDate, format] = await Promise.all([
    requireAsync("core/user_date"),
    getString("strftimedatetimeshortaccurate", "core_langconfig")
  ]);
  return UserDate.get(timestamps.map((timestamp) => ({ timestamp, format })));
}
__name(formatUserDates, "formatUserDates");
async function displayError(error) {
  if (error instanceof ApiError) {
    await addToast(error.message, "danger");
    return;
  }
  const Notification = await requireAsync("core/notification");
  Notification.exception(error);
}
__name(displayError, "displayError");
async function confirmDelete(title, question) {
  const Notification = await requireAsync("core/notification");
  try {
    await Notification.deleteCancelPromise(title, question, await getString("delete", "core"));
    return true;
  } catch {
    return false;
  }
}
__name(confirmDelete, "confirmDelete");
async function addToast(message, type = "success") {
  const Toast = await requireAsync("core/toast");
  await Toast.add(message, { type });
}
__name(addToast, "addToast");
async function enableEditMode() {
  const editswitch = document.querySelector('input[name="setmode"][data-context]');
  if (!editswitch || editswitch.checked) {
    return;
  }
  const Ajax = await requireAsync("core/ajax");
  await Ajax.call([{
    methodname: "core_change_editmode",
    args: { context: Number(editswitch.dataset.context), setmode: true }
  }])[0];
  editswitch.checked = true;
  editswitch.setAttribute("aria-checked", "true");
}
__name(enableEditMode, "enableEditMode");
async function notifyFilterContentUpdated(node) {
  if (node) {
    const FilterEvents = await requireAsync("core_filters/events");
    FilterEvents.notifyFilterContentUpdated([node]);
  }
}
__name(notifyFilterContentUpdated, "notifyFilterContentUpdated");
export {
  addToast,
  confirmDelete,
  displayError,
  enableEditMode,
  formatUserDates,
  notifyFilterContentUpdated
};
//# sourceMappingURL=moodle.dev.js.map
