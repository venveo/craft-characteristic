import{d as m,r as I,c as l,a as o,w as $,v as b,F as p,b as k,e as V,o as r,t as C,f as A,L,g as N,h as v,i as f,j as w,k as g,s as F,l as y,m as S,n as D}from"./vendor.b1a61d0c.js";var B=(e,t)=>{const s=e.__vccOpts||e;for(const[i,d]of t)s[i]=d;return s};const x=m({components:{},emits:["addLinkBlock"],props:{characteristics:{type:Array,required:!0}},setup(e,{emit:t}){const s=e.characteristics;return{selectedCharacteristicIndex:I(0),characteristics:s,handleAddBlock:function(u){let a={id:null,characteristicId:u.id,values:[]};t("addLinkBlock",a)}}}}),M={class:"buttons"},j={class:"select"},U=["value"];function q(e,t,s,i,d,u){return r(),l("div",M,[o("div",j,[$(o("select",{"onUpdate:modelValue":t[0]||(t[0]=a=>e.selectedCharacteristicIndex=a),class:"!rounded-r-none"},[(r(!0),l(p,null,k(e.characteristics,(a,c)=>(r(),l("option",{value:c,key:a.id},C(a.title),9,U))),128))],512),[[b,e.selectedCharacteristicIndex,void 0,{number:!0}]])]),o("button",{onClick:t[1]||(t[1]=V(a=>e.handleAddBlock(e.characteristics[e.selectedCharacteristicIndex]),["prevent"])),class:"btn add icon rounded-l-none"},"Add")])}var O=B(x,[["render",q]]);const _=A("main",{state:()=>({characteristics:[],blocks:[],fieldName:""}),getters:{unusedCharacteristics:e=>{const t=e.blocks.map(s=>s.characteristicId);return e.characteristics.filter(s=>!t.includes(s.id))},getCharacteristicById:e=>t=>e.characteristics.find(s=>s.id===t)},actions:{findCharacteristicIndexById(e){return this.characteristics.findIndex(t=>t.id===e)},findBlockIndexById(e){return this.blocks.findIndex(t=>t.id===e)},addBlock(e){e.id===null&&(e.id="new"+e.characteristicId),this.blocks.push(e)},deleteBlockById(e){const t=this.findBlockIndexById(e);t!==-1&&this.blocks.splice(t,1)},updateBlock(e,t){if(!e||!t)return;const s=this.findBlockIndexById(e);s!==-1&&(this.blocks[s]=t)},updateBlockValue(e,t){const s=this.findBlockIndexById(e),i=Object.assign({},this.blocks[s],{values:t});this.updateBlock(e,i)}}});const P=m({components:{Multiselect:L},emits:["deleteLinkBlock","selectValue"],props:{block:{type:Object,required:!0}},setup(e,{emit:t}){const s=_(),{block:i}=e;N(i,a=>{console.log("Updated",a)});const d=v(()=>characteristic.value.values),u=a=>{t("selectValue",a)};return{block:i,characteristic:v(()=>s.getCharacteristicById(i.characteristicId)),handleChange:u,values:d}}}),T={class:"matrixblock py-4"},E={class:"flex flex-nowrap"},G={class:"fields flex-grow space-y-2",ref:"addValueTrigger"},R=["href"],z={class:"input-wrapper flex"},H={class:"multiselect-wrapper"};function J(e,t,s,i,d,u){const a=f("Multiselect");return r(),l("div",T,[o("div",E,[o("div",G,[o("a",{class:"input ltr characteristic__title",href:e.characteristic.cpEditUrl,target:"_blank"},[o("strong",null,C(e.characteristic.title),1)],8,R),o("div",z,[o("div",H,[w(a,{modelValue:e.block.values,onChange:e.handleChange,options:e.characteristic.values,createTag:!!e.characteristic.allowCustomOptions,mode:"tags",max:parseInt(e.characteristic.maxValues),trackBy:"id",label:"value",valueProp:"id",searchable:!0,object:!1},null,8,["modelValue","onChange","options","createTag","max"])])])],512),o("div",null,[e.characteristic.required?g("",!0):(r(),l("a",{key:0,onClick:t[0]||(t[0]=c=>e.$emit("deleteLinkBlock")),class:"error icon delete","data-icon":"remove",role:"button",title:"Delete"}))])])])}var K=B(P,[["render",J]]);const Q=m({name:"App",components:{CharacteristicLinkBlock:K,CharacteristicControls:O},setup(){const e=_(),{fieldName:t,blocks:s,characteristics:i,unusedCharacteristics:d}=F(e);return{getValuesFieldName:c=>t.value+"["+c+"][values][]",getCharacteristicFieldName:c=>t.value+"["+c+"][characteristic]",blocks:s,characteristics:i,unusedCharacteristics:v(()=>d),handleAddBlock:c=>e.addBlock(c),handleDeleteBlock:c=>e.deleteBlockById(c),handleUpdateValue:(c,n)=>e.updateBlockValue(c,n)}},props:{}}),W=["name","value"],X=["name","value"];function Y(e,t,s,i,d,u){const a=f("characteristic-link-block"),c=f("characteristic-controls");return r(),l("div",null,[(r(!0),l(p,null,k(e.blocks,n=>(r(),l("div",{key:n.id},[o("input",{type:"hidden",name:e.getCharacteristicFieldName(n.id),value:n.characteristicId},null,8,W),(r(!0),l(p,null,k(n.values,h=>(r(),l("input",{type:"hidden",name:e.getValuesFieldName(n.id),value:h},null,8,X))),256))]))),128)),(r(!0),l(p,null,k(e.blocks,n=>(r(),y(a,{block:n,key:n.id,onDeleteLinkBlock:h=>e.handleDeleteBlock(n.id),onSelectValue:h=>e.handleUpdateValue(n.id,h)},null,8,["block","onDeleteLinkBlock","onSelectValue"]))),128)),e.unusedCharacteristics.value.length?(r(),y(c,{key:0,characteristics:e.unusedCharacteristics,onAddLinkBlock:e.handleAddBlock},null,8,["characteristics","onAddLinkBlock"])):g("",!0)])}var Z=B(Q,[["render",Y]]);Craft.CharacteristicsField=Garnish.Base.extend({init:function(e){this.setSettings(e,Craft.CharacteristicsField.defaults);const t=this.settings,s=S(Z);s.use(D());const i=_();i.$state={characteristics:t.characteristics,blocks:t.blocks,fieldName:t.name},s.mount(this.settings.mountPoint),document.querySelector(this.settings.defaultsContainer).remove()}},{defaults:{mountPoint:null,defaultsContainer:null,name:null,blocks:[]}});
//# sourceMappingURL=app.883a0156.js.map
