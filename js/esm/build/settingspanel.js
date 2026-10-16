import{patch as u,remove as v}from"./lib/api";import{Divider as _,TextSetting as h,TransparentButton as r}from"./lib/components";import{useStrings as l}from"./lib/hooks";import{confirmDelete as b,displayError as m}from"./lib/moodle";import y from"./toolcomponent";import{jsx as o,jsxs as s}from"react/jsx-runtime";/**
 * Settings column of a page, shown to moderators in editing mode.
 *
 * @module     mod_mootimeter/settingspanel
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function P(n){const{page:e,refresh:p,onDeleted:c}=n,t=l("mod_mootimeter",["tooltip_enable_page","tooltip_disable_page","delete_page","enter_question","question","settings"]),d=l("core",["areyousure"]),a=i=>u("mod_mootimeter",`/pages/${e.id}`,i).then(p),g=()=>{a({visible:!e.visible}).catch(m)},f=async()=>{await b(t.delete_page,d.areyousure)&&v("mod_mootimeter",`/pages/${e.id}`).then(c).catch(m)};return s("div",{className:"mootimetersettings mootimeterfullwidth",children:[s("div",{className:"mootimeter-header-wrapper",children:[o("h4",{children:e.toolName}),s("div",{className:"d-flex",children:[o(r,{icon:e.visible?"fa-eye":"fa-eye-slash",title:e.visible?t.tooltip_enable_page:t.tooltip_disable_page,onClick:g}),o(r,{icon:"fa-trash",title:t.delete_page,onClick:f})]})]}),o(h,{value:e.question,label:t.question,placeholder:t.enter_question,onSave:i=>a({question:i})}),o(_,{}),o(y,{specifier:e.components.settings,...n})]})}export{P as default};
