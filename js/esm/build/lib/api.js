import g from"@moodle/lms/core/config";import l from"@moodle/lms/core/pending";/**
 * Client of the REST API of mod_mootimeter and the mootimetertool subplugins.
 *
 * core/fetch rejects failed requests with the status text only, this client exposes the RFC 9457 problem details.
 *
 * @module     mod_mootimeter/lib/api
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */class T extends Error{status;code;details;constructor(t,e,s){super(String(e?.detail??e?.message??s)),this.name="ApiError",this.status=t,this.code=String(e?.code??e?.errorcode??""),this.details=Array.isArray(e?.details)?e.details:[]}}async function o(n,t,e,s){const a=new l(`${t}:${n} ${e}`);try{const i={Accept:"application/json, application/problem+json"};s!==void 0&&(i["Content-Type"]="application/json"),n!=="GET"&&(i["X-Moodle-Sesskey"]=g.sesskey);const r=await fetch(`${g.apibase}/rest/v2/${t}${e}`,{method:n,headers:i,credentials:"same-origin",body:s===void 0?void 0:JSON.stringify(s)});if(r.status===204)return;const c=await r.json().catch(()=>null);if(!r.ok)throw new T(r.status,c,r.statusText);return c}finally{a.resolve()}}const d=(n,t)=>o("GET",n,t);async function f(n,t){const e=t.includes("?")?"&":"?",s=[];for(let a=1;;a++){const i=await d(n,`${t}${e}page=${a}&pagesize=100`);if(s.push(...i.items),!i.items.length||s.length>=i.pagination.totalItems)return s}}const m=(n,t,e={})=>o("POST",n,t,e),P=(n,t,e)=>o("PUT",n,t,e),y=(n,t,e)=>o("PATCH",n,t,e),E=(n,t)=>o("DELETE",n,t);export{T as ApiError,d as get,f as getAll,y as patch,m as post,P as put,E as remove,o as request};
