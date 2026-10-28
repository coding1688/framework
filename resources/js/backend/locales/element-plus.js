import en from 'element-plus/es/locale/lang/en';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import zhTw from 'element-plus/es/locale/lang/zh-tw';
import th from 'element-plus/es/locale/lang/th';
import ja from 'element-plus/es/locale/lang/ja';
import ko from 'element-plus/es/locale/lang/ko';
const config = window.backendConfig;
export default {en,zh_CN:zhCn,zh_TW:zhTw,th,ja,ko}[config.locale] || en;
