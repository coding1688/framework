import { request } from '@/utils/request';

export const memberWalletApi = {
    list: options => request('member.wallet.list', options),
    history: options => request('member.wallet.history', options),
    options: options => request('member.wallet.options', options),
    credit: options => request('member.wallet.credit', options),
    debit: options => request('member.wallet.debit', options),
};
