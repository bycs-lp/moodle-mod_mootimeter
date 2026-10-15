import{Divider as _,TextSetting as f,TransparentButton as l}from"./lib/components";import{useStrings as n}from"./lib/hooks";import{callWs as r,confirmDelete as u,displayException as p}from"./lib/moodle";import v from"./toolcomponent";import{jsx as i,jsxs as o}from"react/jsx-runtime";function b(s){let{page:e,refresh:a,onDeleted:m}=s,t=n("mod_mootimeter",["tooltip_enable_page","tooltip_disable_page","delete_page","enter_question","question","settings"]),d=n("core",["areyousure"]),c=()=>{r("mod_mootimeter_set_page_visibility",{pageid:e.id,visible:!e.visible}).then(a).catch(p)},g=async()=>{await u(t.delete_page,d.areyousure)&&r("mod_mootimeter_delete_page",{pageid:e.id}).then(m).catch(p)};return o("div",{className:"mootimetersettings mootimeterfullwidth",children:[o("div",{className:"mootimeter-header-wrapper",children:[i("h4",{children:e.toolname}),o("div",{className:"d-flex",children:[i(l,{icon:e.visible?"fa-eye":"fa-eye-slash",title:e.visible?t.tooltip_enable_page:t.tooltip_disable_page,onClick:c}),i(l,{icon:"fa-trash",title:t.delete_page,onClick:g})]})]}),i(f,{pageid:e.id,name:"question",value:e.question,label:t.question,placeholder:t.enter_question,onStored:a}),i(_,{}),i(v,{specifier:e.components.settings,...s})]})}export{b as default};
/**
 * Settings column of a page, shown to moderators in editing mode.
 *
 * @module     mod_mootimeter/settingspanel
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=settingspanel.js.map
