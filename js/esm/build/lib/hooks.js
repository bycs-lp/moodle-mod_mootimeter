import{useEffect as o,useState as l}from"react";import{callWs as a,displayException as u,loadStrings as g}from"./moodle";function y(n,r){let[s,i]=l({}),c=r.join(",");return o(()=>{let t=!0;return g(n,r).then(e=>t&&i(e)).catch(u),()=>{t=!1}},[n,c]),new Proxy(s,{get:(t,e)=>t[e]??""})}function h(n,r,s){let[i,c]=l(null);return o(()=>{let t=!0;return a(n,r).then(e=>t&&c(e)).catch(u),()=>{t=!1}},s),i}export{y as useStrings,h as useWebservice};
/**
 * React hooks shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * @module     mod_mootimeter/lib/hooks
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=hooks.js.map
