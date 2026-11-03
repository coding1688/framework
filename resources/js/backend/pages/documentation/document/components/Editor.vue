<script setup>
import { computed } from 'vue';
import { documentApi } from '@/api/content/document';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import FormStatusField from '@/components/FormStatusField.vue';
import { buildCategoryTree } from '../../categoryTree';
import { t } from '@/locales';

const props = defineProps({ categories: { type: Array, default: () => [] } });
const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, form, reset, submit } = useEditorDialog(documentApi, emit);
const title = computed(() => t(editing.value ? 'common.edit' : 'common.create'));
const categoryOptions = computed(() => buildCategoryTree(props.categories, form.type));

function open(row = null) {
    reset('edit', row, {
        type: row?.type || 'development',
        category_id: row?.category_id || null,
        slug: row?.slug || '',
        title_i18n: row?.title_i18n || { en: '' },
        summary_i18n: row?.summary_i18n || { en: '' },
        content_i18n: row?.content_i18n || { en: '' },
        published_at: row?.published_at || '',
        sort: row?.sort ?? 0,
        active: row?.active ?? true,
    });
    visible.value = true;
}
function changeType() { form.category_id = null; }
function save() {
    return submit(editing.value ? 'update' : 'create', {
        type: form.type,
        category_id: form.category_id,
        slug: form.slug,
        title_i18n: form.title_i18n,
        summary_i18n: form.summary_i18n,
        content_i18n: form.content_i18n,
        published_at: form.published_at || null,
        sort: form.sort,
        active: form.active,
    });
}
defineExpose({ open });
</script>

<template>
    <EditorDialog v-model="visible" :title="title" width="76vw" :busy="busy" @save="save">
        <el-form label-position="top" :disabled="busy" @submit.prevent="save">
            <div class="field-grid">
                <el-form-item :label="t('documentation.document.type')" :error="errors.type?.[0]" required>
                    <el-select v-model="form.type" @change="changeType"><el-option :label="t('documentation.document.development')" value="development" /><el-option :label="t('documentation.document.usage')" value="usage" /></el-select>
                </el-form-item>
                <el-form-item :label="t('documentation.document.category')" :error="errors.category_id?.[0]" required>
                    <el-tree-select v-model="form.category_id" :data="categoryOptions" node-key="value" check-strictly filterable :render-after-expand="false" :placeholder="t('documentation.document.select_category')" />
                </el-form-item>
            </div>
            <el-form-item :label="t('documentation.document.slug')" :error="errors.slug?.[0]" required><el-input v-model="form.slug" maxlength="128" placeholder="installation" /></el-form-item>
            <LocalizedField v-model="form.title_i18n" field="title_i18n" :label="t('documentation.document.title')" :errors="errors" :maxlength="256" required />
            <LocalizedField v-model="form.summary_i18n" field="summary_i18n" :label="t('documentation.document.summary')" :errors="errors" :maxlength="2048" type="textarea" />
            <LocalizedField v-model="form.content_i18n" field="content_i18n" :label="t('documentation.document.content')" :errors="errors" type="rich" required />
            <div class="field-grid">
                <el-form-item :label="t('documentation.document.published_at')" :error="errors.published_at?.[0]"><el-date-picker v-model="form.published_at" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" clearable /></el-form-item>
                <el-form-item :label="t('common.sort')" :error="errors.sort?.[0]" required><el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" controls-position="right" /></el-form-item>
            </div>
            <FormStatusField v-model="form.active" required :error="errors.active?.[0]" />
            <button type="submit" class="sr-only" tabindex="-1">{{ t('common.save') }}</button>
        </el-form>
    </EditorDialog>
</template>

<style scoped>
.el-date-editor { width: 100%; }
</style>
