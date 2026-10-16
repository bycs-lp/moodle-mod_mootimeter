import{requireAsync as o}from"@moodle/lms/core/amd";import{getString as i}from"@moodle/lms/core/stringUtils";import{ApiError as a}from"./api";/**
 * Moodle user interface services that are not available as ESM modules yet: dialogues, toasts, filters and the edit mode.
 *
 * @module     mod_mootimeter/lib/moodle
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */async function l(e){if(!e.length)return[];const[t,n]=await Promise.all([o("core/user_date"),i("strftimedatetimeshortaccurate","core_langconfig")]);return t.get(e.map(r=>({timestamp:r,format:n})))}async function m(e){if(e instanceof a){await s(e.message,"danger");return}(await o("core/notification")).exception(e)}async function f(e,t){const n=await o("core/notification");try{return await n.deleteCancelPromise(e,t,await i("delete","core")),!0}catch{return!1}}async function s(e,t="success"){await(await o("core/toast")).add(e,{type:t})}async function g(){const e=document.querySelector('input[name="setmode"][data-context]');if(!e||e.checked)return;await(await o("core/ajax")).call([{methodname:"core_change_editmode",args:{context:Number(e.dataset.context),setmode:!0}}])[0],e.checked=!0,e.setAttribute("aria-checked","true")}async function p(e){e&&(await o("core_filters/events")).notifyFilterContentUpdated([e])}export{s as addToast,f as confirmDelete,m as displayError,g as enableEditMode,l as formatUserDates,p as notifyFilterContentUpdated};
