<script setup>
import BaseAlert from '@/components/ui/BaseAlert.vue';
import { t } from '@/locales';
import StatusTag from '@/components/StatusTag.vue';
defineProps({ form: Object, errors: Object, employees: Array, canSetLeaders: Boolean, busy: Boolean });
</script>

<template>
<BaseAlert v-if="errors.leader_ids" :title="errors.leader_ids[0]" type="error" :closable="false"/><el-table :data="employees" border :empty-text="t('common.empty')"><el-table-column prop="name" :label="t('form.nickname')"/><el-table-column prop="email" :label="t('form.email')"/><el-table-column :label="t('common.status')"><template #default="{row}"><StatusTag :active="row.active"/></template></el-table-column><el-table-column :label="t('admin.department.leaders')" width="140"><template #default="{row}"><el-checkbox :model-value="form.leader_ids.includes(row.id)" :disabled="!canSetLeaders||!row.active||busy" :aria-label="t('admin.department.leaders')+' '+row.name" @change="checked=>form.leader_ids=checked?[...form.leader_ids,row.id]:form.leader_ids.filter(id=>id!==row.id)"/></template></el-table-column></el-table>
</template>
