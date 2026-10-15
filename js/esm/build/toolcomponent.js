import{useEffect as i,useState as u}from"react";import{Loading as f}from"./lib/components";import{displayException as s}from"./lib/moodle";import{jsx as e}from"react/jsx-runtime";function a({specifier:o,...p}){let[t,r]=u(null);if(i(()=>{if(!o)return;let n=!0;return import(o).then(m=>n&&r({specifier:o,Component:m.default})).catch(s),()=>{n=!1}},[o]),!o)return null;if(!t||t.specifier!==o)return e(f,{});let{Component:l}=t;return e(l,{...p})}export{a as default};
/**
 * Loads and renders a React component of a mootimetertool subplugin.
 *
 * @module     mod_mootimeter/toolcomponent
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=toolcomponent.js.map
