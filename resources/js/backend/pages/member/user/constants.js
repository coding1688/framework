import { t } from '@/locales';

export const DEVICE_TYPES = Object.freeze({
    WEB: 'WEB',
    H5: 'H5',
    ANDROID: 'ANDROID',
    IOS: 'IOS',
    HARMONY: 'HARMONY',
});

const deviceTypeLabelKeys = {
    [DEVICE_TYPES.WEB]: 'member.user.device_web',
    [DEVICE_TYPES.H5]: 'member.user.device_h5',
    [DEVICE_TYPES.ANDROID]: 'member.user.device_android',
    [DEVICE_TYPES.IOS]: 'member.user.device_ios',
    [DEVICE_TYPES.HARMONY]: 'member.user.device_harmony',
};

export function deviceTypeLabel(type) {
    return deviceTypeLabelKeys[type] ? t(deviceTypeLabelKeys[type]) : (type || '—');
}
