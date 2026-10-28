<script setup>
import { watch } from 'vue';
import { useTranslationTabs } from '@/composables/useTranslationTabs';
import { t } from '@/locales';
const props=defineProps({modelValue:Object,errors:Object});const emit=defineEmits(['update:modelValue']);
const { active, locales } = useTranslationTabs();
watch(()=>props.errors,value=>{const bad=locales.value.find(([locale])=>value?.['name_i18n.'+locale]);if(bad)active.value=bad[0];});
function change(locale,value){emit('update:modelValue',{...props.modelValue,[locale]:value});}
</script>
<template><el-form-item :label="t('common.name')" required :error="errors?.['name_i18n.'+active]?.[0] || errors?.name_i18n?.[0]" class="translation-field"><el-tabs v-model="active" type="card"><el-tab-pane v-for="[locale,label] in locales" :key="locale" :name="locale" ><template #label><span>{{ label }}</span><span v-if="locale==='en'" class="translation-required" aria-hidden="true">*</span></template><el-input :model-value="modelValue[locale]||''" @update:model-value="value=>change(locale,value)" :placeholder="label" :aria-label="t('common.name')+' · '+label" maxlength="128" /></el-tab-pane></el-tabs></el-form-item></template>
