<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppIcon from '@/shared/components/AppIcon.vue';
import LoadingState from '@/shared/components/LoadingState.vue';
import FeedbackMessage from '@/shared/components/FeedbackMessage.vue';
import { documentApi } from '../api';

const props = defineProps({ type: { type: String, required: true } });
const route = useRoute();
const navigation = ref([]), current = ref(null), navigationLoaded = ref(false);
const loading = ref(false), error = ref(''), query = ref(''), sidebarOpen = ref(false);
let requestId = 0;

const pageTitle = computed(() => props.type === 'development' ? '开发文档' : '使用文档');
const articleRoute = computed(() => props.type === 'development' ? 'docs.development.article' : 'docs.usage.article');
function hasDocuments(category) {
    return category.documents?.length || category.children?.some(hasDocuments);
}
function flattenCategories(categories, depth = 0) {
    return categories.flatMap(category => [
        { ...category, depth },
        ...flattenCategories(category.children || [], depth + 1),
    ]);
}
const categories = computed(() => flattenCategories(navigation.value.filter(category => category.type === props.type).filter(hasDocuments)).filter(category => category.depth > 0 || category.documents.length));
const documents = computed(() => categories.value.flatMap(category => (category.documents || []).map(document => ({ ...document, categoryName: category.name }))));
const searchResults = computed(() => {
    const keyword = query.value.trim().toLowerCase();
    if (!keyword) return [];
    return documents.value.filter(document => [document.title, document.summary, document.slug, document.categoryName].some(value => value?.toLowerCase().includes(keyword))).slice(0, 8);
});
const currentIndex = computed(() => documents.value.findIndex(document => document.type === current.value?.type && document.slug === current.value?.slug));
const previous = computed(() => currentIndex.value > 0 ? documents.value[currentIndex.value - 1] : null);
const next = computed(() => currentIndex.value >= 0 ? documents.value[currentIndex.value + 1] || null : null);
const rendered = computed(() => {
    if (!current.value?.content) return { html: '', headings: [] };
    const page = new DOMParser().parseFromString(current.value.content, 'text/html');
    const headings = [...page.querySelectorAll('h2, h3')].map((heading, index) => {
        const id = `section-${index + 1}`;
        heading.id = id;
        return { id, text: heading.textContent, level: Number(heading.tagName.slice(1)) };
    });
    return { html: page.body.innerHTML, headings };
});

async function loadNavigation() {
    loading.value = true;
    error.value = '';
    try {
        navigation.value = (await documentApi.navigation()).data || [];
        navigationLoaded.value = true;
        await loadRouteDocument();
    } catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
}
async function loadRouteDocument() {
    const target = route.params.slug
        ? { type: props.type, slug: route.params.slug }
        : documents.value[0];
    if (!target) { current.value = null; return; }
    const id = ++requestId;
    loading.value = true;
    error.value = '';
    try {
        const result = await documentApi.show(target.type, target.slug);
        if (id !== requestId) return;
        current.value = result.data;
        document.title = `${current.value.title} · ${pageTitle.value} · Framework`;
    } catch (exception) {
        if (id === requestId) { current.value = null; error.value = exception.message; }
    } finally { if (id === requestId) loading.value = false; }
}
function closeSidebar() { sidebarOpen.value = false; }
watch(() => route.params.slug, () => {
    closeSidebar();
    if (navigationLoaded.value) loadRouteDocument();
});
onMounted(loadNavigation);
</script>

<template>
    <section class="docs-page">
        <button class="docs-mobile-toggle" type="button" aria-label="打开文档导航" @click="sidebarOpen = true"><AppIcon name="menu" :size="19" />{{ pageTitle }}导航</button>
        <button v-if="sidebarOpen" class="docs-sidebar-overlay" type="button" aria-label="关闭文档导航" @click="closeSidebar" />
        <aside class="docs-sidebar" :class="{ 'is-open': sidebarOpen }">
            <div class="docs-sidebar-mobile"><strong>{{ pageTitle }}</strong><button type="button" aria-label="关闭文档导航" @click="closeSidebar"><AppIcon name="close" :size="20" /></button></div>
            <div class="docs-search">
                <AppIcon name="search" :size="17" />
                <input v-model="query" type="search" :placeholder="`搜索${pageTitle}`" :aria-label="`搜索${pageTitle}`" @keydown.escape="query = ''">
                <div v-if="query" class="docs-search-results">
                    <RouterLink v-for="result in searchResults" :key="result.slug" :to="{ name: articleRoute, params: { slug: result.slug } }" @click="query = ''"><strong>{{ result.title }}</strong><small>{{ result.categoryName }}</small></RouterLink>
                    <span v-if="!searchResults.length">没有匹配的文档</span>
                </div>
            </div>
            <nav class="docs-navigation" :aria-label="`${pageTitle}目录`">
                <div class="docs-nav-section"><span class="docs-nav-title">{{ pageTitle }}</span><div v-for="category in categories" :key="category.id" class="docs-nav-group"><span class="docs-nav-category" :class="`depth-${Math.min(category.depth, 3)}`">{{ category.name }}</span><RouterLink v-for="document in category.documents" :key="document.id" :to="{ name: articleRoute, params: { slug: document.slug } }" class="docs-nav-link" :class="[`depth-${Math.min(category.depth, 3)}`, { 'is-active': current?.id === document.id }]">{{ document.title }}</RouterLink></div></div>
            </nav>
        </aside>

        <main class="docs-article-wrap">
            <LoadingState v-if="loading" label="正在加载文档…" />
            <div v-else-if="error" class="docs-error"><FeedbackMessage :message="error" /><button class="button button--secondary" type="button" @click="loadNavigation">重新加载</button></div>
            <article v-else-if="current" class="docs-article">
                <div class="docs-breadcrumb"><span>{{ pageTitle }}</span><span>/</span><span>{{ current.category?.name }}</span></div>
                <h1>{{ current.title }}</h1>
                <p v-if="current.summary" class="docs-lead">{{ current.summary }}</p>
                <div class="docs-content" v-html="rendered.html" />
                <nav class="docs-pager" aria-label="相邻文档">
                    <RouterLink v-if="previous" :to="{ name: articleRoute, params: { slug: previous.slug } }" class="docs-pager-prev"><span>上一篇</span><strong>{{ previous.title }}</strong></RouterLink><span v-else />
                    <RouterLink v-if="next" :to="{ name: articleRoute, params: { slug: next.slug } }" class="docs-pager-next"><span>下一篇</span><strong>{{ next.title }} <AppIcon name="arrow" :size="15" /></strong></RouterLink>
                </nav>
            </article>
            <div v-else class="docs-empty"><h2>暂无{{ pageTitle }}</h2><p>已发布的文档会显示在这里。</p></div>
        </main>

        <aside v-if="rendered.headings.length && current" class="docs-toc"><span>本页内容</span><nav><a v-for="heading in rendered.headings" :key="heading.id" :href="`#${heading.id}`" :class="{ 'is-sub': heading.level === 3 }">{{ heading.text }}</a></nav></aside>
    </section>
</template>
