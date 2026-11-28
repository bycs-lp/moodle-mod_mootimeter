import{useEffect as g,useRef as p,useState as c}from"react";import{getStrings as d}from"@moodle/lms/core/stringUtils";import{ApiError as f,get as b}from"./api";import{displayError as m}from"./moodle";/**
 * React hooks shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * @module     mod_mootimeter/lib/hooks
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function P(i,t){const[l,n]=c({}),o=t.join(",");return g(()=>{let r=!0;return d(t.map(e=>({key:e,component:i}))).then(e=>r&&n(Object.fromEntries(t.map((s,a)=>[s,e[a]])))).catch(m),()=>{r=!1}},[i,o]),new Proxy(l,{get:(r,e)=>r[e]??""})}function w(i,t,l){const[n,o]=c(null);return g(()=>{let r=!0;return b(i,t).then(e=>r&&o(e)).catch(e=>{r&&!(e instanceof f&&e.code==="pagelocked")&&m(e)}),()=>{r=!1}},[i,t,...l]),n}function h(i,t,l){const[n,o]=c(1),r=t.includes("?")?"&":"?",e=w(i,`${t}${r}page=${n}`,l);if(!e)return null;const{pageSize:s,totalItems:a}=e.pagination,u=Math.max(1,Math.ceil(a/s));return n>u&&o(u),{items:e.items,page:n,pageSize:s,totalPages:u,setPage:o}}function C(i,t,l){const n=i===null?null:Date.parse(i),o=()=>n===null?null:Math.max(0,Math.ceil((n-Date.now()-t)/1e3)),[r,e]=c(o),s=p(l);return s.current=l,g(()=>{if(e(o()),n===null)return;const a=window.setInterval(()=>{const u=o();e(u),u===0&&(window.clearInterval(a),s.current())},250);return()=>window.clearInterval(a)},[n,t]),r}export{h as useCollection,C as useCountdown,w as useResource,P as useStrings};
