import{useStrings as r}from"./lib/hooks";import{jsx as e,jsxs as t}from"react/jsx-runtime";function d({tools:p,onAdd:o}){let s=r("mod_mootimeter",["select_page_type_heading","select_page_type_paragraph"]);return t("div",{className:"mootimeter-add-tool-container",children:[t("div",{className:"mootimeter-add-tool-header",children:[e("h3",{children:s.select_page_type_heading}),s.select_page_type_paragraph&&e("p",{children:s.select_page_type_paragraph})]}),e("div",{className:"mootimeter-tool-select-wrapper",children:p.map(a=>t("button",{type:"button",className:"mootimeter-radio-card mtmt-tool-selector-list","data-name":a.name,onClick:()=>o(a.name),children:[e("img",{src:a.iconurl,width:"40px",className:"m-1",alt:""}),t("span",{className:"card-details",children:[e("span",{className:"card-type",children:a.displayname}),e("span",{className:"card-desc",children:a.description})]}),e("span",{className:"active-border"})]},a.name))})]})}export{d as default};
/**
 * Tool selection for a new page.
 *
 * @module     mod_mootimeter/addpage
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=addpage.js.map
