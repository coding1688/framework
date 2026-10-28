<script setup>
import { t } from '@/locales';
defineProps({active:Boolean});
</script>
<template><el-tag :type="active?'success':'info'" effect="light" size="small">{{ t(active?'active':'inactive') }}</el-tag></template>
