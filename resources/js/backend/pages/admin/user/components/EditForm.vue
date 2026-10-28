<script setup>
import { t } from '@/locales';

defineProps({ form: Object, errors: Object, editing: Object, options: Object });
</script>

<template>
<el-form-item :label="t('form.email')" required :error="errors.email?.[0]"><el-input v-model="form.email" type="email" maxlength="256" required/></el-form-item><el-form-item :label="t('form.nickname')" required :error="errors.name?.[0]"><el-input v-model="form.name" maxlength="128" required/></el-form-item><template v-if="!editing"><el-form-item :label="t('form.password')" required :error="errors.password?.[0]"><el-input v-model="form.password" type="password" show-password minlength="12" maxlength="128" autocomplete="new-password" required/><p class="password-help">{{ t('form.password_hint') }}</p></el-form-item><el-form-item :label="t('admin.department.department')" required :error="errors.department_id?.[0]"><el-select v-model="form.department_id" filterable><el-option v-for="item in options.departments||[]" :key="item.id" :label="item.name" :value="item.id"/></el-select></el-form-item></template>
</template>
