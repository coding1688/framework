<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import LocalizedField from '@/components/LocalizedField.vue';
import { vipLevelApi } from '@/api/member/vipLevel';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const visible = ref(false), busy = ref(false), errors = ref({});
const form = reactive({ id: null, level: 1, name_i18n: {}, description_i18n: {}, benefits_i18n: {}, duration_days: null, active: true, sort: 0 });
function open(row = null) {
    Object.assign(form, { id: null, level: 1, name_i18n: {}, description_i18n: {}, benefits_i18n: {}, duration_days: null, active: true, sort: 0 }, row || {});
    errors.value = {};
    visible.value = true;
}
async function submit() {
    if (busy.value) return;
    busy.value = true; errors.value = {};
    const data = { level: form.level, name_i18n: form.name_i18n, description_i18n: form.description_i18n, benefits_i18n: form.benefits_i18n, duration_days: form.duration_days, active: form.active, sort: form.sort };
    try {
        await (form.id ? vipLevelApi.update({ id: form.id, data }) : vipLevelApi.create({ data }));
        notify(t('common.saved')); visible.value = false; emit('saved');
    } catch (error) { errors.value = error.fields || {}; fail(error); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('member.vipLevel.editor')" width="76vw" :close-on-click-modal="false">
        <el-form label-position="top" @submit.prevent="submit">
            <el-row :gutter="20"><el-col :xs="24" :md="8"><el-form-item :label="t('member.vipLevel.level')" required :error="errors.level?.[0]"><el-input-number v-model="form.level" :min="1" :max="65535" /></el-form-item></el-col><el-col :xs="24" :md="8"><el-form-item :label="t('member.vipLevel.duration')" :error="errors.duration_days?.[0]"><el-input-number v-model="form.duration_days" :min="1" :max="3650" :placeholder="t('member.vipLevel.permanent')" /><small>{{ t('member.vipLevel.duration_hint') }}</small></el-form-item></el-col><el-col :xs="24" :md="8"><el-form-item :label="t('common.sort')" :error="errors.sort?.[0]"><el-input-number v-model="form.sort" :min="0" :max="99999" /></el-form-item></el-col></el-row>
            <LocalizedField v-model="form.name_i18n" field="name_i18n" :label="t('common.name')" :errors="errors" :maxlength="128" required />
            <LocalizedField v-model="form.description_i18n" field="description_i18n" :label="t('common.description')" type="textarea" :errors="errors" :maxlength="512" />
            <LocalizedField v-model="form.benefits_i18n" field="benefits_i18n" :label="t('member.vipLevel.benefits')" type="textarea" :errors="errors" :maxlength="2048" />
            <el-form-item :label="t('common.status')" :error="errors.active?.[0]"><el-switch v-model="form.active" :active-text="t('common.enable')" :inactive-text="t('common.disable')" /></el-form-item>
        </el-form>
        <template #footer><el-button @click="visible = false">{{ t('common.cancel') }}</el-button><el-button type="primary" :loading="busy" @click="submit">{{ t('common.save') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>small { display: block; margin-top: 6px; color: var(--el-text-color-secondary); }</style>
