import { config } from '@/config';
import { state } from '@/store/modules/user';
export const can = (section, action) => state.permissions.includes('*') || state.permissions.includes(config.ui.permission_prefixes[section] + '.' + action);
