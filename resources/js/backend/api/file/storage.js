import { request } from '@/utils/request';

export const storageApi = Object.fromEntries(['list', 'settings', 'saveSettings', 'create', 'update', 'delete', 'tasks', 'sync', 'retry'].map(action => [action, options => request(`file.storage.${action}`, options)]));
