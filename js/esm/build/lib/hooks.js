import{useEffect as u,useState as g}from"react";import{getStrings as m}from"@moodle/lms/core/stringUtils";import{ApiError as f,get as d}from"./api";import{displayError as p}from"./moodle";/**
 * React hooks shared between mod_mootimeter and the mootimetertool subplugins.
 *
 * @module     mod_mootimeter/lib/hooks
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function b(r,n){const[o,i]=g({}),s=n.join(",");return u(()=>{let e=!0;return m(n.map(t=>({key:t,component:r}))).then(t=>e&&i(Object.fromEntries(n.map((a,c)=>[a,t[c]])))).catch(p),()=>{e=!1}},[r,s]),new Proxy(o,{get:(e,t)=>e[t]??""})}function y(r,n,o){const[i,s]=g(null);return u(()=>{let e=!0;return d(r,n).then(t=>e&&s(t)).catch(t=>{e&&!(t instanceof f&&t.code==="pagelocked")&&p(t)}),()=>{e=!1}},[r,n,...o]),i}function C(r,n,o){const[i,s]=g(1),e=n.includes("?")?"&":"?",t=y(r,`${n}${e}page=${i}`,o);if(!t)return null;const{pageSize:a,totalItems:c}=t.pagination,l=Math.max(1,Math.ceil(c/a));return i>l&&s(l),{items:t.items,page:i,pageSize:a,totalPages:l,setPage:s}}export{C as useCollection,y as useResource,b as useStrings};
