var c=(t=>typeof require<"u"?require:typeof Proxy<"u"?new Proxy(t,{get:(e,r)=>(typeof require<"u"?require:e)[r]}):t)(function(t){if(typeof require<"u")return require.apply(this,arguments);throw Error('Dynamic require of "'+t+'" is not supported')});function o(t){return new Promise((e,r)=>{c([t],e,r)})}var[d,g,l,f,p,w]=await Promise.all([o("core/ajax"),o("core/str"),o("core/notification"),o("core/toast"),o("core/config"),o("core_filters/events")]),y=p.wwwroot;function P(t,e){return Promise.resolve(d.call([{methodname:t,args:e}])[0])}var a=new Map;async function T(t,e){let r=e.filter(n=>!a.has(`${t}/${n}`));if(r.length){let n=Promise.resolve(g.get_strings(r.map(i=>({key:i,component:t}))));r.forEach((i,u)=>{a.set(`${t}/${i}`,n.then(m=>m[u]))})}let s=await Promise.all(e.map(n=>a.get(`${t}/${n}`)));return Object.fromEntries(e.map((n,i)=>[n,s[i]]))}function v(t,e,r){return Promise.resolve(g.get_string(t,e,r))}function b(t){l.exception(t)}async function h(t,e){try{return await l.deleteCancelPromise(t,e,v("delete","core")),!0}catch{return!1}}function _(t,e="success"){f.add(t,{type:e})}function $(t){t&&w.notifyFilterContentUpdated([t])}async function C(t,e,r){let s=String(r);return typeof r=="boolean"&&(s=r?"1":"0"),(await P("mod_mootimeter_store_setting",{pageid:t,name:e,value:s})).value}export{_ as addToast,P as callWs,h as confirmDelete,b as displayException,v as getString,T as loadStrings,$ as notifyFilterContentUpdated,o as requireAmd,C as storeSetting,y as wwwroot};
/**
 * Bridge from the ESM/React world to the Moodle AMD APIs.
 *
 * @module     mod_mootimeter/lib/moodle
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//# sourceMappingURL=moodle.js.map
