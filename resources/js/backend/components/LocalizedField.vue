<script setup>
import { defineAsyncComponent, watch } from 'vue';
import { useTranslationTabs } from '@/composables/useTranslationTabs';
const RichTextEditor = defineAsyncComponent(() => import('@/components/RichTextEditor.vue'));
const props = defineProps({ modelValue: Object, errors: Object, field: String, label: String, type: { type: String, default: 'input' }, maxlength: { type: Number, default: 128 }, required: Boolean, requiredLocales: Array, locales: Array, placeholder: String, preferredLocale: String });
const emit = defineEmits(['update:modelValue']);
const { active, locales } = useTranslationTabs(() => props.locales, () => props.preferredLocale);
watch(() => props.errors, value => { const bad = locales.value.find(([locale]) => value?.[`${props.field}.${locale}`]); if (bad) active.value = bad[0]; });
function change(locale, value) { emit('update:modelValue', { ...props.modelValue, [locale]: value }); }
</script>
<template>
    <el-form-item :label="label" :required="required" :error="errors?.[`${field}.${active}`]?.[0] || errors?.[field]?.[0]" class="translation-field">
        <el-tabs v-model="active" type="card">
            <el-tab-pane v-for="[locale, localeLabel] in locales" :key="locale" :name="locale">
                <template #label><span>{{ localeLabel }}</span><span v-if="requiredLocales?.includes(locale) || (required && !requiredLocales && locale === 'en')" class="translation-required">*</span></template>
                <RichTextEditor v-if="type==='rich' && active === locale" :model-value="modelValue?.[locale]||''" :placeholder="placeholder||localeLabel" @update:model-value="value=>change(locale,value)" />
                <el-input v-else-if="type!=='rich'" :type="type==='textarea'?'textarea':'text'" :rows="type==='textarea'?3:undefined" :model-value="modelValue?.[locale]||''" :maxlength="maxlength" show-word-limit :placeholder="placeholder||localeLabel" @update:model-value="value=>change(locale,value)" />
            </el-tab-pane>
        </el-tabs>
    </el-form-item>
</template>
