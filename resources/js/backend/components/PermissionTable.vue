<script setup>
import {computed} from 'vue';
import { state } from '@/store/modules/user';
import { flatten } from '@/utils/navigation';
import { t } from '@/locales';
const props=defineProps({items:Array,modelValue:Array});const emit=defineEmits(['update:modelValue']);
const selected=computed(()=>new Set(props.modelValue)),allowed=computed(()=>new Set(props.items.map(item=>item.id)));
const nodes=computed(()=>{const result=new Map(flatten(state.navigation).map(item=>[item.id,item]));props.items.forEach(item=>result.set(item.id,item));return result;});
const groups=computed(()=>{
  const tree=nodes.value;
  const pages=new Map();
  for(const item of props.items){if(item.type==='directory')continue;const page=item.type==='page'?item:tree.get(item.parent_id);const key=page?.id??item.parent_id??item.id;if(!pages.has(key))pages.set(key,{id:key,name:page?.name||item.permission.split('.').slice(0,-1).join('.'),parent_id:page?.parent_id,items:[]});pages.get(key).items.push(item);}
  const result=new Map();
  for(const page of pages.values()){let category=tree.get(page.parent_id);const seen=new Set();while(category?.parent_id&&!seen.has(category.id)){seen.add(category.id);const parent=tree.get(category.parent_id);if(!parent)break;category=parent;}const key=category?.id??0;if(!result.has(key))result.set(key,{id:key,name:category?.name||t('permission.permissions'),permission:category?.permission,pages:[]});result.get(key).pages.push(page);}
  return [...result.values()];
});
const ids=items=>items.map(item=>item.id).filter(id=>allowed.value.has(id));
function categoryIds(group){return [...(allowed.value.has(group.id)?[group.id]:[]),...group.pages.flatMap(page=>ids(page.items))];}
function categoryToggleIds(group){return group.permission==='service'?[group.id].filter(id=>allowed.value.has(id)):categoryIds(group);}
function categoryChecked(group){return group.permission==='service'?selected.value.has(group.id):all(categoryIds(group));}
function categoryIndeterminate(group){return group.permission==='service'?!selected.value.has(group.id)&&some(group.pages.flatMap(page=>ids(page.items))):some(categoryIds(group));}
function all(values){return values.length>0&&values.every(id=>selected.value.has(id));}
function some(values){return values.some(id=>selected.value.has(id))&&!all(values);}
function toggle(values,checked){const next=new Set(props.modelValue.filter(id=>allowed.value.has(id)));const targets=new Set(values);if(checked){for(const id of values){let node=nodes.value.get(id);const seen=new Set();while(node?.parent_id&&!seen.has(node.id)){seen.add(node.id);if(allowed.value.has(node.parent_id))targets.add(node.parent_id);node=nodes.value.get(node.parent_id);}}}targets.forEach(id=>checked?next.add(id):next.delete(id));emit('update:modelValue',[...next]);}
</script>
<template><div class="permission-scroll"><table class="permission-table"><colgroup><col style="width:16%"><col style="width:22%"><col></colgroup><tbody><template v-for="group in groups" :key="group.id"><tr v-for="(page,index) in group.pages" :key="page.id"><th v-if="index===0" :rowspan="group.pages.length"><el-checkbox :model-value="categoryChecked(group)" :indeterminate="categoryIndeterminate(group)" @change="value=>toggle(categoryToggleIds(group),value)">{{ group.name }}</el-checkbox></th><th><el-checkbox :model-value="all(ids(page.items))" :indeterminate="some(ids(page.items))" @change="value=>toggle(ids(page.items),value)">{{ page.name }}</el-checkbox></th><td><el-checkbox v-for="item in page.items" :key="item.id" :model-value="selected.has(item.id)" @change="value=>toggle([item.id],value)">{{ item.type==='page'?t('admin.menu.page'):item.name }}</el-checkbox></td></tr></template></tbody></table><el-empty v-if="!groups.length" :description="t('common.empty')" /></div></template>
