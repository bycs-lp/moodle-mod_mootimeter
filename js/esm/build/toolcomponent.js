import{useEffect as u,useState as i}from"react";import{Loading as f}from"./lib/components";import{displayError as s}from"./lib/moodle";import{jsx as e}from"react/jsx-runtime";/**
 * Loads and renders a React component of a mootimetertool subplugin.
 *
 * @module     mod_mootimeter/toolcomponent
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function a({specifier:o,...r}){const[t,p]=i(null);if(u(()=>{if(!o)return;let n=!0;return import(o).then(m=>n&&p({specifier:o,Component:m.default})).catch(s),()=>{n=!1}},[o]),!o)return null;if(!t||t.specifier!==o)return e(f,{});const{Component:l}=t;return e(l,{...r})}export{a as default};
