<script setup>
import { computed } from 'vue';
import { documentCategoryApi } from '@/api/content/documentCategory';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import FormStatusField from '@/components/FormStatusField.vue';
import { buildCategoryTree, withoutCategoryBranch } from '../../categoryTree';
import { t } from '@/locales';

const props = defineProps({ categories: { type: Array, default: () => [] } });
const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, form, reset, submit } = useEditorDialog(documentCategoryApi, emit);
const title = computed(() => t(editing.value ? 'common.edit' : 'common.create'));
const parentOptions = computed(() => buildCategoryTree(withoutCategoryBranch(props.categories, editing.value?.id), form.type));

function open(row = null, defaults = {}) {
    reset('edit', row, {
        type: row?.type || defaults.type || 'development',
        parent_id: row?.parent_id || defaults.parent_id || null,
        slug: row?.slug || '',
        name_i18n: row?.name_i18n || { en: '' },
        description_i18n: row?.description_i18n || { en: '' },
        sort: row?.sort ?? 0,
        active: row?.active ?? true,
    });
    visible.value = true;
}
function changeType() { form.parent_id = null; }
function save() {
    return submit(editing.value ? 'update' : 'create', {
        type: form.type,
        parent_id: form.parent_id || null,
        slug: form.slug,
        name_i18n: form.name_i18n,
        description_i18n: form.description_i18n,
        sort: form.sort,
        active: form.active,
    });
}
defineExpose({ open });
</script>

<template>
    <EditorDialog v-model="visible" :title="title" width="70vw" :busy="busy" @save="save">
        <el-form label-position="top" :disabled="busy" @submit.prevent="save">
            <div class="field-grid">
                <el-form-item :label="t('documentation.category.type')" :error="errors.type?.[0]" required>
                    <el-select v-model="form.type" @change="changeType"><el-option :label="t('documentation.category.development')" value="development" /><el-option :label="t('documentation.category.usage')" value="usage" /></el-select>
                </el-form-item>
                <el-form-item :label="t('documentation.category.parent')" :error="errors.parent_id?.[0]">
                    <el-tree-select v-model="form.parent_id" :data="parentOptions" node-key="value" check-strictly clearable filterable :render-after-expand="false" />
                </el-form-item>
            </div>
            <el-form-item :label="t('documentation.category.slug')" :error="errors.slug?.[0]" required>
                <el-input v-model="form.slug" maxlength="128" placeholder="getting-started" />
            </el-form-item>
            <LocalizedField v-model="form.name_i18n" field="name_i18n" :label="t('documentation.category.name')" :errors="errors" :maxlength="256" required />
            <LocalizedField v-model="form.description_i18n" field="description_i18n" :label="t('documentation.category.description')" :errors="errors" :maxlength="2048" type="textarea" />
            <el-form-item :label="t('common.sort')" :error="errors.sort?.[0]" required><el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" controls-position="right" /></el-form-item>
            <FormStatusField v-model="form.active" required :error="errors.active?.[0]" />
            <button type="submit" class="sr-only" tabindex="-1">{{ t('common.save') }}</button>
        </el-form>
    </EditorDialog>
</template>
