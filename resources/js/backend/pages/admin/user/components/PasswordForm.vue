<script setup>
import { t } from '@/locales';

defineProps({ form: Object, errors: Object, editing: Object });
</script>

<template>
<el-form-item :label="t('form.new_password')" required :error="errors.password?.[0]"><el-input v-model="form.password" type="password" show-password minlength="12" maxlength="128" autocomplete="new-password" required/><p class="password-help">{{ t('form.password_hint') }}</p></el-form-item><el-form-item :label="t('form.password_confirmation')" required :error="errors.password_confirmation?.[0]"><el-input v-model="form.password_confirmation" type="password" show-password maxlength="128" autocomplete="new-password" required/></el-form-item>
</template>
