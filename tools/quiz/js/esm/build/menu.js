import{patch as c}from"@moodle/lms/mod_mootimeter/lib/api";import{IconButton as m}from"@moodle/lms/mod_mootimeter/lib/components";import{useResource as a,useStrings as l}from"@moodle/lms/mod_mootimeter/lib/hooks";import{displayError as s}from"@moodle/lms/mod_mootimeter/lib/moodle";import{componentOf as r}from"./types";import{jsx as u}from"react/jsx-runtime";/**
 * Content menu button of the quiz to reveal the correct answers.
 *
 * @module     mootimetertool_quiz/menu
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function p({page:t,settingsVersion:n,refresh:i}){const e=l("mootimetertool_quiz",["tooltip_content_menu_answercorrection","tooltip_content_menu_answercorrection_disabled"]),o=a(r(t),`/pages/${t.id}`,[n]);return o?u(m,{icon:o.correctionShown?"fa-check-square-o":"fa-square-o",title:o.correctionShown?e.tooltip_content_menu_answercorrection_disabled:e.tooltip_content_menu_answercorrection,active:o.correctionShown,onClick:()=>{c(r(t),`/pages/${t.id}`,{correctionShown:!o.correctionShown}).then(i).catch(s)}}):null}export{p as default};
