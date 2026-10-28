<script setup>
import { computed, ref, watch } from 'vue';
import { UploadFilled } from '@element-plus/icons-vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { fileApi } from '@/api/file/file';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const props = defineProps({ options: { type: Object, required: true } });
const emit = defineEmits(['saved']);
const visible = ref(false);
const busy = ref(false);
const fileList = ref([]);
const form = ref({ disk: 'public', type: 'general', visibility: 'public' });
const providers = computed(() => Object.entries(props.options.providers || {}));
const visibilities = computed(() => props.options.providers?.[form.value.disk]?.visibilities || ['public']);
const acceptedExtensions = computed(() => (props.options.allowed_extensions || []).map(extension => `.${extension}`).join(','));
watch(() => form.value.disk, () => {
    if (!visibilities.value.includes(form.value.visibility)) form.value.visibility = visibilities.value[0];
});

function open() {
    const preferred = props.options.providers?.[props.options.default_disk]?.enabled
        ? props.options.default_disk
        : providers.value.find(([, provider]) => provider.enabled)?.[0] || 'public';
    form.value = { disk: preferred, type: props.options.types?.[0]?.value || 'general', visibility: 'public' };
    fileList.value = [];
    visible.value = true;
}
function change(file, files) { fileList.value = files; }
function remove(file, files) { fileList.value = files; }
function providerLabel(provider) {
    if (provider.provider === 'local_private') return `${t('file.item.provider_local')} (${t('file.item.private')})`;
    return t(`file.item.provider_${provider.provider}`);
}
function providerState(provider) {
    if (provider.enabled) return '';
    return ` (${t(provider.configured ? 'common.inactive' : 'file.item.not_configured')})`;
}
function exceed() { fail(new Error(t('file.item.file_limit'))); }
async function submit() {
    if (!fileList.value.length) { fail(new Error(t('file.item.select_files'))); return; }
    const data = new FormData();
    fileList.value.forEach(file => data.append('files[]', file.raw));
    data.append('disk', form.value.disk);
    data.append('type', form.value.type);
    data.append('visibility', form.value.visibility);
    busy.value = true;
    try {
        await fileApi.upload({ data });
        notify(t('file.item.uploaded'));
        visible.value = false;
        emit('saved');
    } catch (error) { fail(error); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('file.item.upload_files')" width="70vw" class="file-upload-dialog" :busy="busy">
        <el-form label-position="right" label-width="128px" class="horizontal-editor-form">
            <div class="field-grid">
                <el-form-item :label="t('file.item.storage_provider')" required>
                    <el-select v-model="form.disk">
                        <el-option v-for="([disk, provider]) in providers" :key="disk" :value="disk" :disabled="!provider.enabled" :label="`${providerLabel(provider)}${providerState(provider)}`" />
                    </el-select>
                </el-form-item>
                <el-form-item :label="t('file.item.visibility')" required>
                    <el-select v-model="form.visibility">
                        <el-option v-for="visibility in visibilities" :key="visibility" :value="visibility" :label="t(`file.item.${visibility}`)" />
                    </el-select>
                </el-form-item>
            </div>
            <el-form-item :label="t('file.item.type')" required>
                <el-select v-model="form.type"><el-option v-for="item in options.types" :key="item.value" :value="item.value" :label="item.label" /></el-select>
            </el-form-item>
            <el-form-item :label="t('file.item.files')" required>
                <el-upload drag multiple :auto-upload="false" :file-list="fileList" :accept="acceptedExtensions" :limit="options.max_files || 16" :on-change="change" :on-remove="remove" :on-exceed="exceed">
                    <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
                    <div class="el-upload__text">{{ t('file.item.drop_or_select') }}</div>
                    <template #tip><div class="el-upload__tip">{{ t('file.item.upload_tip').replace('{size}', Math.round((options.max_size_kb || 32768) / 1024)).replace('{count}', options.max_files || 16) }}</div></template>
                </el-upload>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button :disabled="busy" @click="visible=false">{{ t('common.cancel') }}</el-button>
            <el-button type="primary" :loading="busy" @click="submit">{{ t('file.item.start_upload') }}</el-button>
        </template>
    </BaseDialog>
</template>
