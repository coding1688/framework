import { request } from '@/utils/request';

export const departmentApi = {
    list: options => request('admin.department.list', options),
    options: options => request('admin.department.options', options),
    create: options => request('admin.department.create', options),
    update: options => request('admin.department.update', options),
    status: options => request('admin.department.status', options),
    delete: options => request('admin.department.delete', options),
    permissions: options => request('admin.department.permissions', options),
    employees: options => request('admin.department.employees', options),
    leaders: options => request('admin.department.leaders', options),
};
