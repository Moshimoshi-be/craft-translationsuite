import{b as e,d as t,q as s}from"./vendor.2bd584ab.js";(async()=>e(t({delimiters:["${","}"],components:{draggable:s},data:()=>({enabled:!0,dragging:!1,list:[],selection:[{name:"Message",type:"message"},{name:"Category",type:"category"}]}),computed:{selectionList(){return JSON.stringify(this.selection)}},methods:{log(e){console.log(e)}},mounted(){const e=window.Translationsuite.getActiveLanguages();for(let t of e)this.list.push({name:t.toUpperCase(),type:"language"})}})).mount("#importContainer"))().then((e=>{}));
//# sourceMappingURL=import.38e27db7.js.map
