import{AnonymousModeSetting as i,CheckboxSetting as d,Loading as m,NumberSetting as u}from"@moodle/lms/mod_mootimeter/lib/components";import{useStrings as l,useWebservice as p}from"@moodle/lms/mod_mootimeter/lib/hooks";import{Fragment as w,jsx as o,jsxs as b}from"react/jsx-runtime";function c({page:e,settingsVersion:s,refresh:t}){let r=l("mootimetertool_wordcloud",["answers_max_number","allowduplicateanswers"]),n=l("core",["settings"]),a=p("mootimetertool_wordcloud_get_page_data",{pageid:e.id},[e.id,s]);return a?b(w,{children:[o("p",{className:"text-bold",children:n.settings}),o(u,{pageid:e.id,name:"maxinputsperuser",value:a.maxinputsperuser,label:r.answers_max_number,onStored:t}),o(d,{pageid:e.id,name:"allowduplicateanswers",value:a.allowduplicateanswers,label:r.allowduplicateanswers,onStored:t}),o(i,{page:e,onStored:t})]}):o(m,{})}export{c as default};
/**
 * Settings of the wordcloud tool.
 *
 * @module     mootimetertool_wordcloud/settings
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=settings.js.map
