import{IconButton as i}from"@moodle/lms/mod_mootimeter/lib/components";import{useStrings as c,useWebservice as a}from"@moodle/lms/mod_mootimeter/lib/hooks";import{displayException as s,storeSetting as m}from"@moodle/lms/mod_mootimeter/lib/moodle";import{jsx as _}from"react/jsx-runtime";function l({page:t,settingsVersion:r,refresh:n}){let e=c("mootimetertool_quiz",["tooltip_content_menu_answercorrection","tooltip_content_menu_answercorrection_disabled"]),o=a("mootimetertool_quiz_get_page_data",{pageid:t.id},[t.id,r]);return o?_(i,{icon:o.answercorrection?"fa-check-square-o":"fa-square-o",title:o.answercorrection?e.tooltip_content_menu_answercorrection_disabled:e.tooltip_content_menu_answercorrection,active:o.answercorrection,onClick:()=>{m(t.id,"showanswercorrection",!o.answercorrection).then(n).catch(s)}}):null}export{l as default};
/**
 * Content menu button of the quiz to reveal the correct answers.
 *
 * @module     mootimetertool_quiz/menu
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=menu.js.map
