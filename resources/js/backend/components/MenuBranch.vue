<script setup>
defineProps({ node: { type: Object, required: true } });
</script>

<template>
    <li v-if="node.route_name === 'development'" class="spa-menu-section-divider" role="separator">
        <el-icon aria-hidden="true"><AppIcon icon="code-line" /></el-icon>
    </li>
    <el-sub-menu v-if="node.children?.length" :index="'group-' + node.id" :title="node.name">
        <template #title>
            <el-icon><AppIcon :icon="node.icon" /></el-icon>
            <span class="spa-menu-label" :title="node.name">{{ node.name }}</span>
        </template>
        <el-menu-item v-if="node.path" :index="node.path" :title="node.name">
            <el-icon><AppIcon :icon="node.icon" /></el-icon>
            <template #title><span class="spa-menu-label" :title="node.name">{{ node.name }}</span></template>
        </el-menu-item>
        <MenuBranch v-for="child in node.children" :key="child.id" :node="child" />
    </el-sub-menu>
    <el-menu-item v-else-if="node.path" :index="node.path" :title="node.name">
        <el-icon><AppIcon :icon="node.icon" /></el-icon>
        <template #title><span class="spa-menu-label" :title="node.name">{{ node.name }}</span></template>
    </el-menu-item>
</template>
