<script setup>
import { computed } from 'vue';
import { announcementApi } from '@/api/content/announcement';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import FormStatusField from '@/components/FormStatusField.vue';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, form, reset, submit } = useEditorDialog(announcementApi, emit);
const title = computed(() => t(editing.value ? 'common.edit' : 'common.create'));

function open(row = null) {
    reset('edit', row, {
        title_i18n: row?.title_i18n || { en: '' },
        content_i18n: row?.content_i18n || { en: '' },
        published_at: row?.published_at || '',
        sort: row?.sort ?? 0,
        active: row?.active ?? true,
    });
    visible.value = true;
}
function save() {
    const data = {
        title_i18n: form.title_i18n,
        content_i18n: form.content_i18n,
        published_at: form.published_at || null,
        sort: form.sort,
        active: form.active,
    };
    return submit(editing.value ? 'update' : 'create', data);
}
defineExpose({ open });
</script>

<template>
    <EditorDialog v-model="visible" :title="title" width="76vw" :busy="busy" @save="save">
        <el-form label-position="right" label-width="144px" class="horizontal-editor-form" :disabled="busy" @submit.prevent="save">
            <LocalizedField v-model="form.title_i18n" field="title_i18n" :label="t('content.announcement.title')" :errors="errors" :maxlength="256" required />
            <LocalizedField v-model="form.content_i18n" field="content_i18n" :label="t('content.announcement.content')" :errors="errors" type="rich" required />
            <div class="field-grid">
                <el-form-item :label="t('content.announcement.published_at')" :error="errors.published_at?.[0]">
                    <el-date-picker v-model="form.published_at" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" clearable />
                </el-form-item>
                <el-form-item :label="t('common.sort')" :error="errors.sort?.[0]" required>
                    <el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" controls-position="right" />
                </el-form-item>
            </div>
            <FormStatusField v-model="form.active" required :error="errors.active?.[0]" />
            <button type="submit" class="sr-only" tabindex="-1">{{ t('common.save') }}</button>
        </el-form>
    </EditorDialog>
</template>

<style scoped>
.el-date-editor {
    width: 100%;
}
</style>
