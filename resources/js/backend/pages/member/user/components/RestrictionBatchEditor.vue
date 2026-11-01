<script setup>
import { onMounted, reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { frontendUserApi } from '@/api/member/user';
import { fail, notify } from '@/utils/feedback';
import { t } from '@/locales';

const emit = defineEmits(['saved']);
const visible = ref(false), busy = ref(false), loading = ref(false), error = ref(''), types = ref([]);
const form = reactive({ user_ids: [], type: 'login', restricted: true, reason: '' });

onMounted(async () => {
    loading.value = true;
    try { types.value = (await frontendUserApi.restrictionTypes()).data || []; }
    catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
});
function open(rows) {
    Object.assign(form, { user_ids: rows.map(row => row.id), type: 'login', restricted: true, reason: '' });
    error.value = '';
    visible.value = true;
}
async function submit() {
    if (busy.value) return;
    if (form.restricted && !form.reason.trim()) {
        error.value = t('member.user.restriction_reason_required');
        return;
    }
    busy.value = true;
    error.value = '';
    try {
        await frontendUserApi.restrictions({ data: { ...form } });
        notify(t('common.saved'));
        visible.value = false;
        emit('saved');
    } catch (exception) { error.value = exception.message; fail(exception); }
    finally { busy.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('member.user.restriction_batch')" width="50%" :close-on-click-modal="false">
        <div v-loading="loading">
            <el-alert :title="t('member.user.restriction_selected', { count: form.user_ids.length })" type="info" :closable="false" />
            <el-form label-position="top" @submit.prevent="submit">
                <el-form-item :label="t('member.user.restriction_action')" required><el-radio-group v-model="form.restricted"><el-radio-button :value="true">{{ t('member.user.restriction_apply') }}</el-radio-button><el-radio-button :value="false">{{ t('member.user.restriction_lift') }}</el-radio-button></el-radio-group></el-form-item>
                <el-form-item :label="t('member.user.restriction_type')" required><el-select v-model="form.type"><el-option v-for="option in types" :key="option.type" :label="t(`member.user.restriction_${option.type}`)" :value="option.type" /></el-select></el-form-item>
                <el-form-item :label="t(form.restricted ? 'member.user.restriction_reason' : 'member.user.restriction_lift_reason')" :required="form.restricted"><el-input v-model="form.reason" type="textarea" maxlength="512" show-word-limit /></el-form-item>
                <el-alert v-if="error" :title="error" type="error" :closable="false" />
            </el-form>
        </div>
        <template #footer><el-button @click="visible = false">{{ t('common.cancel') }}</el-button><el-button type="primary" :loading="busy" @click="submit">{{ t('common.confirm') }}</el-button></template>
    </BaseDialog>
</template>

<style scoped>
.el-alert { margin-bottom: 20px; }
.el-select { width: 100%; }
</style>
