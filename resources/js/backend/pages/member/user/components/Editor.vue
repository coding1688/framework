<script setup>
import { reactive, ref } from 'vue';
import BaseDialog from '@/components/ui/BaseDialog.vue';
import { frontendUserApi } from '@/api/member/user';
import { date } from '@/utils/date';
import { t } from '@/locales';
import { deviceTypeLabel } from '../constants';

const visible = ref(false), loading = ref(false), error = ref('');
const form = reactive({ id: null, username: '', name: '', email: '', is_test: false, vip_level: 0, providers: [], active: true, email_verified: false, last_login_at: null, last_login_ip: null, last_login_device_type: null, created_at: null });

async function open(row) {
    error.value = '';
    visible.value = true;
    loading.value = true;
    try { Object.assign(form, (await frontendUserApi.detail({ id: row.id })).data); }
    catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
}
defineExpose({ open });
</script>

<template>
    <BaseDialog v-model="visible" :title="t('member.user.detail')" width="50%" :close-on-click-modal="false">
        <div v-loading="loading">
            <el-alert v-if="error" :title="error" type="error" :closable="false" />
            <el-descriptions :column="1" border>
                <el-descriptions-item :label="t('member.user.username')">{{ form.username }}</el-descriptions-item><el-descriptions-item :label="t('member.user.user_type')">{{ t(form.is_test ? 'member.user.test_user' : 'member.user.regular_user') }}</el-descriptions-item><el-descriptions-item :label="t('form.email')">{{ form.email }}</el-descriptions-item><el-descriptions-item :label="t('member.user.providers')">{{ form.providers.join(', ') || '—' }}</el-descriptions-item><el-descriptions-item :label="t('member.user.last_login')">{{ date(form.last_login_at) }}</el-descriptions-item><el-descriptions-item label="IP">{{ form.last_login_ip || '—' }}</el-descriptions-item><el-descriptions-item :label="t('member.user.device_type')">{{ deviceTypeLabel(form.last_login_device_type) }}</el-descriptions-item><template v-if="!form.active"><el-descriptions-item :label="t('member.user.frozen_at')">{{ date(form.frozen_at) }}</el-descriptions-item><el-descriptions-item :label="t('member.user.frozen_by')">{{ form.frozen_by || '—' }}</el-descriptions-item><el-descriptions-item :label="t('member.user.freeze_reason')">{{ form.frozen_reason || '—' }}</el-descriptions-item></template>
            </el-descriptions>
        </div>
        <template #footer><el-button @click="visible = false">{{ t('common.close') }}</el-button></template>
    </BaseDialog>
</template>
