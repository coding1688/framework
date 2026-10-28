import { request } from '@/utils/request';

export const queueApi = {
    overview: options => request('system.queue.overview', options),
    jobs: options => request('system.queue.jobs', options),
    failed: options => request('system.queue.failed', options),
    detail: options => request('system.queue.detail', options),
    retry: options => request('system.queue.retry', options),
    retryBatch: options => request('system.queue.retryBatch', options),
    delete: options => request('system.queue.delete', options),
};
