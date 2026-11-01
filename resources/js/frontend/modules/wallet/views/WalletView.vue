<script setup>
import { onMounted, ref } from 'vue';
import { walletApi } from '../api';
import { formatDate } from '@/shared/utils/date';
import AppIcon from '@/shared/components/AppIcon.vue';
import FeedbackMessage from '@/shared/components/FeedbackMessage.vue';
import LoadingState from '@/shared/components/LoadingState.vue';

const wallets = ref([]), transactions = ref([]), loading = ref(false), error = ref('');

onMounted(async () => {
    loading.value = true;
    try {
        const [walletResult, historyResult] = await Promise.all([walletApi.list(), walletApi.history()]);
        wallets.value = walletResult.data || [];
        transactions.value = historyResult.data || [];
    } catch (exception) { error.value = exception.message; }
    finally { loading.value = false; }
});
</script>

<template>
    <div class="wallet-view">
        <FeedbackMessage :message="error" />
        <LoadingState v-if="loading" label="正在加载钱包信息…" />
        <template v-else>
            <section v-if="wallets.length" class="wallet-grid"><article v-for="wallet in wallets" :key="wallet.id" class="wallet-card"><div class="wallet-card-top"><span class="wallet-currency">{{ wallet.currency }}</span><span class="wallet-card-icon"><AppIcon name="wallet" :size="19" /></span></div><span class="wallet-balance-label">可用余额</span><strong>{{ wallet.balance }}</strong><small>最近账变 {{ formatDate(wallet.updated_at) }}</small></article></section>
            <section v-else class="content-card empty-state"><span class="empty-state-icon"><AppIcon name="wallet" :size="28" /></span><h3>暂无钱包</h3><p>钱包创建后，余额和账变信息会在这里展示。</p></section>
            <section class="content-card wallet-history"><header class="card-heading card-heading--row"><div><h3>最近账变</h3><p>展示最近 20 条账户资金变动。</p></div><span class="record-count">{{ transactions.length }} 条记录</span></header><div v-if="transactions.length" class="wallet-table-wrap"><table class="wallet-table"><thead><tr><th>收支</th><th>业务类型</th><th>金额</th><th>账后余额</th><th>参考号</th><th>时间</th></tr></thead><tbody><tr v-for="transaction in transactions" :key="transaction.id"><td><span class="transaction-direction" :class="transaction.direction">{{ transaction.direction === 'credit' ? '入账' : '出账' }}</span></td><td>{{ transaction.type }}</td><td class="transaction-amount" :class="transaction.direction">{{ transaction.direction === 'credit' ? '+' : '-' }}{{ transaction.amount }} <small>{{ transaction.currency }}</small></td><td>{{ transaction.balance_after }}</td><td class="transaction-reference">{{ transaction.reference }}</td><td>{{ formatDate(transaction.created_at) }}</td></tr></tbody></table></div><div v-else class="table-empty">暂无账变记录</div></section>
        </template>
    </div>
</template>
