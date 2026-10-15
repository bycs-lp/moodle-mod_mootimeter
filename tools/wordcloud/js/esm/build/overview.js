import{useState as d}from"react";import{Loading as _,OverviewTable as v,TransparentButton as b}from"@moodle/lms/mod_mootimeter/lib/components";import{useStrings as w,useWebservice as f}from"@moodle/lms/mod_mootimeter/lib/hooks";import{callWs as p,displayException as g}from"@moodle/lms/mod_mootimeter/lib/moodle";import{jsx as o,jsxs as c}from"react/jsx-runtime";function y({pageid:n,row:i,onChanged:r}){let e=w("core",["edit","save","cancel"]),[s,t]=d(!1),[l,m]=d(i.text);return s?c("form",{onSubmit:async a=>{a.preventDefault();try{await p("mootimetertool_wordcloud_update_answer",{pageid:n,answerid:i.answerid,text:l}),t(!1),r()}catch(u){g(u)}},className:"d-flex",children:[o("input",{type:"text",className:"form-control form-control-sm me-1","aria-label":e.edit,value:l,autoFocus:!0,onChange:a=>m(a.target.value)}),o("button",{type:"submit",className:"btn btn-primary btn-sm me-1",children:e.save}),o("button",{type:"button",className:"btn btn-secondary btn-sm",onClick:()=>t(!1),children:e.cancel})]}):c("span",{children:[i.text,o(b,{icon:"fa-pencil",title:e.edit,className:"text-body",onClick:()=>{m(i.text),t(!0)}})]})}function x({page:n,answersVersion:i,refresh:r}){let e=w("mod_mootimeter",["delete_single_answer_dialog_title","delete_single_answer_dialog_question"]),s=f("mootimetertool_wordcloud_get_overview",{pageid:n.id},[n.id,i]);return s?o(v,{rows:s.rows,deleteTitle:e.delete_single_answer_dialog_title,deleteQuestion:e.delete_single_answer_dialog_question,onDelete:t=>p("mod_mootimeter_delete_answers",{pageid:n.id,scope:"single",answerid:t.answerid}).then(r),renderAnswer:t=>o(y,{pageid:n.id,row:t,onChanged:r})}):o(_,{})}export{x as default};
/**
 * Answer overview of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/overview
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=overview.js.map
