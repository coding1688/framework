<script setup>
import { t } from '@/locales';
import TranslationInput from '@/components/TranslationInput.vue';
import FormStatusField from '@/components/FormStatusField.vue';
import { computed, ref } from 'vue';
import { icons } from '@/icons';
const iconQuery = ref('');
const iconChoices = computed(() => icons.filter(name => name.toLowerCase().includes(iconQuery.value.toLowerCase())).slice(0, 60));
const pageComponents = Object.keys(import.meta.glob('../../**/List.vue')).map(path => 'admin/' + path.replace('../../', '').replace('.vue', ''));
defineProps({ form: Object, errors: Object, system: Boolean, parentChoices: Array });
</script>

<template>
<TranslationInput v-model="form.name_i18n" :errors="errors"/>
<div class="field-grid" v-if="form.type!=='button'"><el-form-item :label="t('admin.menu.icon')" :error="errors.icon?.[0]"><el-select v-model="form.icon" filterable :filter-method="value => iconQuery = value" @visible-change="visible => { if (!visible) iconQuery = ''; }"><el-option v-for="name in [...new Set([form.icon, ...iconChoices].filter(Boolean))]" :key="name" :label="name" :value="name"><el-icon><AppIcon :icon="name" /></el-icon> {{ name }}</el-option></el-select></el-form-item><el-form-item v-if="form.type==='page'" :label="t('admin.menu.component')" required :error="errors.component?.[0]"><el-select v-model="form.component" :disabled="system" filterable><el-option v-for="name in pageComponents" :key="name" :label="name" :value="name" /></el-select></el-form-item></div><div class="field-grid"><el-form-item :label="t('admin.menu.type')" required :error="errors.type?.[0]"><el-select v-model="form.type" :disabled="system"><el-option v-for="kind in ['directory','page','button']" :key="kind" :label="t(kind)" :value="kind"/></el-select></el-form-item><el-form-item :label="t('admin.menu.permission')" required :error="errors.permission?.[0]"><el-input v-model="form.permission" :disabled="system" maxlength="128" required/></el-form-item></div><el-form-item v-if="form.type!=='directory'" :label="t('admin.menu.path')" :required="form.type==='page'" :error="errors.path?.[0]"><el-input v-model="form.path" :disabled="system" maxlength="256" :placeholder="t('admin.menu.path')"/></el-form-item>
<div class="field-grid"><el-form-item :label="t('common.parent')" :required="form.type==='button'" :error="errors.parent_id?.[0]"><el-select v-model="form.parent_id" :disabled="system&&form.type==='button'" :clearable="form.type!=='button'" filterable><el-option v-for="item in parentChoices" :key="item.id" :label="item.label" :value="item.id"/></el-select></el-form-item><el-form-item :label="t('common.sort')" required :error="errors.sort?.[0]"><el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" controls-position="right"/></el-form-item></div>
<FormStatusField v-model="form.active" required :error="errors.active?.[0]" />
</template>
