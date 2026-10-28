<script setup>
import BaseAlert from '@/components/ui/BaseAlert.vue';
import { t } from '@/locales';
import TranslationInput from '@/components/TranslationInput.vue';
import FormStatusField from '@/components/FormStatusField.vue';
defineProps({ form: Object, errors: Object, editing: Object, parentChoices: Array });
</script>

<template>
<TranslationInput v-model="form.name_i18n" :errors="errors"/>
<div class="field-grid"><el-form-item :label="t('common.parent')" required :error="errors.parent_id?.[0]"><el-select v-model="form.parent_id"   filterable><el-option v-for="item in parentChoices" :key="item.id" :label="item.label" :value="item.id"/></el-select></el-form-item><el-form-item :label="t('common.sort')" required :error="errors.sort?.[0]"><el-input-number v-model="form.sort" :min="0" :max="99999" :precision="0" controls-position="right"/></el-form-item></div>
<template v-if="!editing"><el-checkbox v-model="form.create_leader" class="leader-toggle">{{ t('admin.department.new_leader') }}</el-checkbox><fieldset v-if="form.create_leader" class="leader-block"><legend>{{ t('admin.department.new_leader') }}</legend><div class="field-grid"><el-form-item :label="t('form.email')" required :error="errors['leader.email']?.[0]"><el-input v-model="form.leader.email" type="email" maxlength="256" required/></el-form-item><el-form-item :label="t('form.nickname')" required :error="errors['leader.name']?.[0]"><el-input v-model="form.leader.name" maxlength="128" required/></el-form-item></div><el-form-item :label="t('form.password')" required :error="errors['leader.password']?.[0]"><el-input v-model="form.leader.password" type="password" show-password minlength="12" maxlength="128" autocomplete="new-password" required/><p class="password-help">{{ t('form.password_hint') }}</p></el-form-item></fieldset></template>
<BaseAlert v-if="errors.leader_ids" :title="errors.leader_ids[0]" type="error" :closable="false"/>
<FormStatusField v-model="form.active" required :error="errors.active?.[0]" />
</template>

<style scoped>
.leader-toggle {
    margin: 0 0 12px 144px;
}
.leader-block {
    width: calc(100% - 144px);
    margin: 0 0 18px 144px;
}
@media (max-width: 900px) {
    .leader-toggle {
        margin-left: 0;
    }
    .leader-block {
        width: 100%;
        margin-left: 0;
    }
}
</style>
