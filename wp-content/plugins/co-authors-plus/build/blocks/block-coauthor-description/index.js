!function(){var t={184:function(t,e){var n;!function(){"use strict";var r={}.hasOwnProperty;function o(){for(var t=[],e=0;e<arguments.length;e++){var n=arguments[e];if(n){var i=typeof n;if("string"===i||"number"===i)t.push(n);else if(Array.isArray(n)){if(n.length){var a=o.apply(null,n);a&&t.push(a)}}else if("object"===i){if(n.toString!==Object.prototype.toString&&!n.toString.toString().includes("[native code]")){t.push(n.toString());continue}for(var l in n)r.call(n,l)&&n[l]&&t.push(l)}}}return t.join(" ")}t.exports?(o.default=o,t.exports=o):void 0===(n=function(){return o}.apply(e,[]))||(t.exports=n)}()}},e={};function n(r){var o=e[r];if(void 0!==o)return o.exports;var i=e[r]={exports:{}};return t[r](i,i.exports,n),i.exports}n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,{a:e}),e},n.d=function(t,e){for(var r in e)n.o(e,r)&&!n.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:e[r]})},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},function(){"use strict";var t=window.wp.blocks,e=window.wp.element,r=window.wp.primitives,o=(0,e.createElement)(r.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,e.createElement)(r.Path,{d:"M6.08 10.103h2.914L9.657 12h1.417L8.23 4H6.846L4 12h1.417l.663-1.897Zm1.463-4.137.994 2.857h-2l1.006-2.857ZM11 16H4v-1.5h7V16Zm1 0h8v-1.5h-8V16Zm-4 4H4v-1.5h4V20Zm7-1.5V20H9v-1.5h6Z"}));function i(){return i=Object.assign?Object.assign.bind():function(t){for(var e=1;e<arguments.length;e++){var n=arguments[e];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(t[r]=n[r])}return t},i.apply(this,arguments)}var a=window.wp.blockEditor,l=(window.wp.i18n,window.wp.data),u=n(184),s=n.n(u),c=JSON.parse('{"u2":"co-authors-plus/description"}');(0,t.registerBlockType)(c.u2,{edit:function(t){let{context:n,attributes:r,setAttributes:o}=t;const{textAlign:u}=r,c=(0,l.useSelect)((t=>t("co-authors-plus/blocks").getAuthorPlaceholder()),[]),p=n["co-authors-plus/author"]||c,{description:f}=p;return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(a.BlockControls,null,(0,e.createElement)(a.AlignmentControl,{value:u,onChange:t=>{o({textAlign:t})}})),(0,e.createElement)("div",i({},(0,a.useBlockProps)({className:s()({[`has-text-align-${u}`]:u,"is-layout-flow":!0})}),{dangerouslySetInnerHTML:{__html:f.rendered}})))},icon:o})}()}();