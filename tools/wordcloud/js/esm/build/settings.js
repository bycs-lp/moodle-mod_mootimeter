import{patch as d}from"@moodle/lms/mod_mootimeter/lib/api";import{AnonymousModeSetting as p,CheckboxSetting as u,Loading as w,NumberSetting as g}from"@moodle/lms/mod_mootimeter/lib/components";import{useResource as S,useStrings as l}from"@moodle/lms/mod_mootimeter/lib/hooks";import{COMPONENT as m}from"./types";import{Fragment as f,jsx as e,jsxs as _}from"react/jsx-runtime";/**
 * Settings of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/settings
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function b({page:t,settingsVersion:i,refresh:s}){const n=l("mootimetertool_wordcloud",["answers_max_number","allowduplicateanswers"]),c=l("core",["settings"]),r=S(m,`/pages/${t.id}`,[i]);if(!r)return e(w,{});const a=o=>d(m,`/pages/${t.id}`,o).then(s);return _(f,{children:[e("p",{className:"text-bold",children:c.settings}),e(g,{value:r.maxAnswers,label:n.answers_max_number,onSave:o=>a({maxAnswers:o})}),e(u,{value:r.allowDuplicateAnswers,label:n.allowduplicateanswers,onSave:o=>a({allowDuplicateAnswers:o})}),e(p,{page:t,onStored:s})]})}export{b as default};
