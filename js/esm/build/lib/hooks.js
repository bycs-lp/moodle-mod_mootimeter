import{useEffect as o,useState as l}from"react";import{callWs as u,displayException as a,loadStrings as g}from"./moodle";function y(n,r){let[s,i]=l({}),c=r.join(",");return o(()=>{let e=!0;return g(n,r).then(t=>e&&i(t)).catch(a),()=>{e=!1}},[n,c]),new Proxy(s,{get:(e,t)=>e[t]??""})}function h(n,r,s){let[i,c]=l(null);return o(()=>{let e=!0;return u(n,r).then(t=>e&&c(t)).catch(t=>{t?.errorcode!=="pagelocked"&&a(t)}),()=>{e=!1}},s),i}export{y as useStrings,h as useWebservice};
/**
 * React hooks shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * @module     mod_mootimeter/lib/hooks
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=hooks.js.map
