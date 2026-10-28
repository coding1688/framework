<script setup>
import { computed, ref } from 'vue';
import { Plus } from '@element-plus/icons-vue';
import { carouselApi } from '@/api/content/carousel';
import { useEditorDialog } from '@/composables/useEditorDialog';
import EditorDialog from '@/components/EditorDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import FormStatusField from '@/components/FormStatusField.vue';
import { can } from '@/utils/permission';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const { visible, busy, errors, editing, form, reset, submit } = useEditorDialog(carouselApi, emit);
const options = ref({ allowed_extensions: [], max_size_kb: 0 });
const uploadBusy = ref(false);
const title = computed(() => t(editing.value ? 'common.edit' : 'common.create'));

async function open(row = null) {
    if (can('carousels', 'options') && can('carousels', 'upload')) {
        options.value = (await carouselApi.options()).data;
    }
    reset('edit', row, {
        title_i18n: row?.title_i18n || { en: '' },
        image_id: row?.image_id || null,
        image_url: row?.image_url || '',
        link_url: row?.link_url || '',
        sort: row?.sort ?? 0,
        active: row?.active ?? true,
    });
    visible.value = true;
}
async function uploadImage(file) {
    if (!file.raw || uploadBusy.value) return;
    const data = new FormData();
    data.append('image', file.raw);
    uploadBusy.value = true;
    try {
        const result = await carouselApi.upload({ data });
        form.image_id = result.data.id;
        form.image_url = result.data.url;
        notify(t('content.carousel.uploaded'));
    } catch (error) { fail(error); }
    finally { uploadBusy.value = false; }
}
function save() {
    const data = {
        title_i18n: form.title_i18n,
        image_id: form.image_id,
        link_url: form.link_url || null,
        sort: form.sort,
        active: form.active,
    };
    return submit(editing.value ? 'update' : 'create', data);
}
defineExpose({ open });
</script>

<template>
    <EditorDialog v-model="visible" :title="title" width="60%" :busy="busy || uploadBusy" @save="save">
        <el-form label-position="right" label-width="144px" class="horizontal-editor-form" :disabled="busy || uploadBusy" @submit.prevent="save">
            <LocalizedField v-model="form.title_i18n" field="title_i18n" :label="t('content.carousel.title')" :errors="errors" :maxlength="256" required />
            <el-form-item :label="t('content.carousel.image')" :error="errors.image_id?.[0] || errors.image?.[0]" required>
                <div class="carousel-image-field">
                    <el-image v-if="form.image_url" class="carousel-image-preview" :src="form.image_url" fit="cover" :preview-src-list="[form.image_url]" preview-teleported />
                    <div v-else class="carousel-image-placeholder"><AppIcon icon="image-line" size="30" /></div>
                    <div class="carousel-image-actions">
                        <el-upload v-if="can('carousels','upload')" accept="image/*" :auto-upload="false" :show-file-list="false" :disabled="uploadBusy" :on-change="uploadImage">
                            <el-button type="primary" plain :icon="Plus" :loading="uploadBusy">{{ t(form.image_url ? 'content.carousel.replace_image' : 'content.carousel.upload_image') }}</el-button>
                        </el-upload>
                        <p class="muted">{{ t('content.carousel.upload_hint') }}</p>
                    </div>
                </div>
            </el-form-item>
            <el-form-item :label="t('content.carousel.link_url')" :error="errors.link_url?.[0]">
                <el-input v-model="form.link_url" maxlength="512" :placeholder="t('content.carousel.link_placeholder')" />
            </el-form-item>
            <div class="field-grid">
                <el-form-item :label="t('common.sort')" :error="errors.sort?.[0]" required>
                    <el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" controls-position="right" />
                </el-form-item>
                <FormStatusField v-model="form.active" required :error="errors.active?.[0]" />
            </div>
            <button type="submit" class="sr-only" tabindex="-1">{{ t('common.save') }}</button>
        </el-form>
    </EditorDialog>
</template>

<style scoped>
.carousel-image-field {
    display: flex;
    align-items: center;
    gap: 16px;
    width: 100%;
}
.carousel-image-preview,
.carousel-image-placeholder {
    flex: 0 0 auto;
    width: 192px;
    height: 108px;
    border: 1px solid var(--el-border-color);
    border-radius: var(--theme-radius);
    background: var(--app-soft);
}
.carousel-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--el-text-color-placeholder);
}
.carousel-image-actions {
    min-width: 0;
}
.carousel-image-actions p {
    margin: 8px 0 0;
    line-height: 1.6;
}
@media (max-width: 600px) {
    .carousel-image-field {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
