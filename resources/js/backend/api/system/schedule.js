import { request } from '@/utils/request';

export const scheduleApi = {
    list: options => request('system.schedule.list', options),
    options: options => request('system.schedule.options', options),
    create: options => request('system.schedule.create', options),
    update: options => request('system.schedule.update', options),
    status: options => request('system.schedule.status', options),
    delete: options => request('system.schedule.delete', options),
    runs: options => request('system.schedule.runs', options),
    runDetail: options => request('system.schedule.runDetail', options),
    run: options => request('system.schedule.run', options),
    settings: options => request('system.schedule.settings', options),
    updateSettings: options => request('system.schedule.updateSettings', options),
};
