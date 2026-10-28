<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import { notificationTemplateApi } from '@/api/system/notificationTemplate';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const visible = ref(false), busy = ref(false), errors = ref({});
const form = reactive({ id: null, code: '', name_i18n: {}, subject_i18n: {}, content_i18n: {}, channels: ['database', 'mail'], active: true });
function open(row = null) {
    Object.assign(form, { id: null, code: '', name_i18n: {}, subject_i18n: {}, content_i18n: {}, channels: ['database', 'mail'], active: true }, row || {});
    errors.value = {};
    visible.value = true;
}
async function submit() {
    if (busy.value) return;
    busy.value = true; errors.value = {};
    try {
        const options = { data: { code: form.code, name_i18n: form.name_i18n, subject_i18n: form.subject_i18n, content_i18n: form.content_i18n, channels: form.channels, active: form.active } };
        await (form.id ? notificationTemplateApi.update({ id: form.id, ...options }) : notificationTemplateApi.create(options));
        notify(t('common.saved')); visible.value = false; emit('saved');
    } catch (error) { errors.value = error.fields || {}; fail(error); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template><BaseDialog v-model="visible" :title="t('system.notificationTemplate.editor')" width="76vw" :close-on-click-modal="false"><el-form label-position="top" @submit.prevent="submit"><el-form-item :label="t('system.notificationTemplate.code')" required :error="errors.code?.[0]"><el-input v-model="form.code" maxlength="128" :disabled="!!form.id" required /></el-form-item><LocalizedField v-model="form.name_i18n" field="name_i18n" :label="t('common.name')" :errors="errors" :maxlength="128" required /><LocalizedField v-model="form.subject_i18n" field="subject_i18n" :label="t('system.notificationTemplate.subject')" :errors="errors" :maxlength="256" required /><LocalizedField v-model="form.content_i18n" field="content_i18n" :label="t('system.notificationTemplate.content')" type="textarea" :errors="errors" :maxlength="4096" required /><el-form-item :label="t('system.notificationTemplate.channels')" required><el-checkbox-group v-model="form.channels"><el-checkbox value="database">{{ t('notification.database') }}</el-checkbox><el-checkbox value="mail">{{ t('notification.mail') }}</el-checkbox></el-checkbox-group></el-form-item><el-form-item :label="t('common.status')"><el-switch v-model="form.active" /></el-form-item></el-form><template #footer><el-button @click="visible = false">{{ t('common.cancel') }}</el-button><el-button type="primary" :loading="busy" @click="submit">{{ t('common.save') }}</el-button></template></BaseDialog></template>
