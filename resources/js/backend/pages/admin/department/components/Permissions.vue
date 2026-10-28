<script setup>
import BaseAlert from '@/components/ui/BaseAlert.vue';
import { t } from '@/locales';
import PermissionTable from '@/components/PermissionTable.vue';
defineProps({ form: Object, errors: Object, permissionItems: Array });
</script>

<template>
<BaseAlert v-if="errors.menu_ids" :title="errors.menu_ids[0]" type="error" :closable="false"/><PermissionTable :items="permissionItems" v-model="form.menu_ids"/>
</template>
