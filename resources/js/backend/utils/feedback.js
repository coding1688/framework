import { ElMessage } from 'element-plus';
import { t } from '@/locales';
export const notify = message => ElMessage({message,type:'success',offset:16});
export const fail = error => { if (error?.name !== 'AbortError' && !error?.redirected) { ElMessage({message:error?.message || t('common.error'),type:'error',offset:16}); } };
