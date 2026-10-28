import SecurityPasswordField from './SecurityPasswordField.vue';
import SecurityPasswordDialog from './SecurityPasswordDialog.vue';
import { securityPasswordOptionsKey } from './context';

export { SecurityPasswordField, SecurityPasswordDialog };
export { createSecurityPasswordPrompt, createSecurityPasswordState } from './prompt';
export { securityPasswordRequirement } from './requirement';

export default {
    install(app, options = {}) {
        app.provide(securityPasswordOptionsKey, options);
        app.component('SecurityPasswordField', SecurityPasswordField);
        app.component('SecurityPasswordDialog', SecurityPasswordDialog);
    },
};
