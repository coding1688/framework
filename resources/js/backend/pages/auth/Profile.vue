<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { authApi } from '@/api/auth';
import BaseCard from '@/components/ui/BaseCard.vue';
import { SecurityPasswordField } from '@/plugins/securityPassword';
import { state as identity, userinfo } from '@/store/modules/user';
import { applyPreferences } from '@/store/modules/settings';
import { config } from '@/config';
import { t } from '@/locales';
import { notify, fail } from '@/utils/feedback';

const defaultPreferences = config.ui.theme.defaults;
const form = reactive({ current_password: '', password: '', password_confirmation: '' });
const securityForm = reactive({ current_password: '', current_security_password: '', security_password: '', security_password_confirmation: '' });
const appearance = reactive({ ...defaultPreferences, ...identity.user?.preferences });
const busy = ref(false), securityBusy = ref(false), appearanceBusy = ref(false), errors = ref({}), securityErrors = ref({});
const securityConfigured = computed(() => Boolean(identity.user?.security_password_set));
const accountBusy = computed(() => busy.value || securityBusy.value);
const presets = Object.keys(config.ui.theme.presets);
const activeTab = ref('password');
let disposed = false;
let passwordRequest, securityRequest, appearanceRequest;
function clearForm(values) { Object.keys(values).forEach(key => { values[key] = ''; }); }
function presetColor(preset) { return config.ui.theme.presets[preset]?.color; }
watch(securityConfigured, () => { clearForm(securityForm); securityErrors.value = {}; });
watch(appearance, value => applyPreferences(value), { deep: true });
watch(() => identity.user?.preferences, value => { if (value) Object.assign(appearance, value); }, { deep: true });
function resetAppearance() {
    Object.assign(appearance, identity.user?.preferences || defaultPreferences);
    applyPreferences(appearance);
}
async function saveAppearance() {
    if (appearanceBusy.value) return;
    appearanceBusy.value = true;
    appearanceRequest = new AbortController();
    try {
        const result = await authApi.preferences({ data: { ...appearance }, signal: appearanceRequest.signal });
        if (identity.user) identity.user.preferences = result.data;
        applyPreferences(result.data);
        if (!disposed) notify(t('common.saved'));
    } catch (error) { if (!disposed) fail(error); }
    finally { appearanceRequest = undefined; appearanceBusy.value = false; }
}
async function save() {
    if (accountBusy.value) return;
    busy.value = true;
    errors.value = {};
    passwordRequest = new AbortController();
    const data = { ...form };
    try {
        await authApi.password({ data, signal: passwordRequest.signal });
        clearForm(form);
        if (!disposed) notify(t('common.saved'));
    } catch (error) {
        if (!disposed) { errors.value = error.fields || {}; fail(error); }
    } finally { clearForm(data); passwordRequest = undefined; busy.value = false; }
}
async function saveSecurityPassword() {
    if (accountBusy.value) return;
    securityBusy.value = true;
    securityErrors.value = {};
    securityRequest = new AbortController();
    const data = {
        security_password: securityForm.security_password,
        security_password_confirmation: securityForm.security_password_confirmation,
        ...(securityConfigured.value ? { current_security_password: securityForm.current_security_password } : { current_password: securityForm.current_password }),
    };
    try {
        const result = await authApi.securityPassword({ data, signal: securityRequest.signal });
        if (disposed) return;
        clearForm(securityForm);
        if (identity.user && result.data?.security_password_set === true) identity.user.security_password_set = true;
        await userinfo(true);
        if (!disposed) notify(t('security.security_password_saved'));
    } catch (error) {
        if (!disposed) { securityErrors.value = error.fields || {}; fail(error); }
    } finally {
        clearForm(data);
        securityRequest = undefined;
        securityBusy.value = false;
    }
}
onBeforeUnmount(() => { disposed = true; passwordRequest?.abort(); securityRequest?.abort(); appearanceRequest?.abort(); applyPreferences(identity.user?.preferences || {}); clearForm(form); clearForm(securityForm); });
</script>

<template>
    <div class="profile-page">
        <BaseCard shadow="never" class="profile-card">
            <el-tabs v-model="activeTab" class="profile-tabs">
                <el-tab-pane name="password">
                    <template #label><span class="profile-tab-label"><AppIcon icon="lock-password-line" size="17" /><span>{{ t('security.login_password_title') }}</span></span></template>
                    <div class="security-card-heading">
                        <span class="security-card-icon"><AppIcon icon="lock-password-line" size="22" /></span>
                        <div>
                            <h3>{{ t('security.login_password_title') }}</h3>
                            <p>{{ t('auth.security_hint') }}</p>
                        </div>
                    </div>
                    <el-form label-position="top" class="account-security-form" :disabled="accountBusy" @submit.prevent="save">
                        <el-form-item v-for="key in Object.keys(form)" :key="key" :label="t(key === 'password' ? 'form.new_password' : key === 'current_password' ? 'auth.current_password' : 'form.password_confirmation')" :error="errors[key]?.[0]" required>
                            <el-input v-model="form[key]" type="password" show-password maxlength="128" :autocomplete="key === 'current_password' ? 'current-password' : 'new-password'" required />
                        </el-form-item>
                        <div class="password-help"><AppIcon icon="information-line" size="16" /><span>{{ t('form.password_hint') }}</span></div>
                        <div class="security-form-actions">
                            <el-button type="primary" native-type="submit" :loading="busy" :disabled="accountBusy">{{ t('common.save') }}</el-button>
                        </div>
                    </el-form>
                </el-tab-pane>
                <el-tab-pane name="security">
                    <template #label><span class="profile-tab-label"><AppIcon icon="key-2-line" size="17" /><span>{{ t(securityConfigured ? 'security.change_title' : 'security.setup_title') }}</span></span></template>
                    <div class="security-card-heading">
                        <span class="security-card-icon"><AppIcon icon="key-2-line" size="22" /></span>
                        <div>
                            <h3>{{ t(securityConfigured ? 'security.change_title' : 'security.setup_title') }}</h3>
                            <p>{{ t('security.password_hint') }}</p>
                        </div>
                    </div>
                    <el-form label-position="top" class="account-security-form" :disabled="accountBusy" @submit.prevent="saveSecurityPassword">
                        <SecurityPasswordField v-if="securityConfigured" v-model="securityForm.current_security_password" configured field="current_security_password" :label="t('security.current_security_password_label')" :errors="securityErrors" :disabled="accountBusy" />
                        <el-form-item v-else :label="t('security.current_login_password_label')" :error="securityErrors.current_password?.[0]" required>
                            <el-input v-model="securityForm.current_password" type="password" show-password maxlength="128" autocomplete="current-password" required />
                        </el-form-item>
                        <el-form-item :label="t('security.new_password_label')" :error="securityErrors.security_password?.[0]" required>
                            <el-input v-model="securityForm.security_password" type="password" show-password minlength="8" maxlength="128" autocomplete="new-password" required />
                        </el-form-item>
                        <el-form-item :label="t('security.confirmation_label')" :error="securityErrors.security_password_confirmation?.[0]" required>
                            <el-input v-model="securityForm.security_password_confirmation" type="password" show-password minlength="8" maxlength="128" autocomplete="new-password" required />
                        </el-form-item>
                        <div class="password-help"><AppIcon icon="information-line" size="16" /><span>{{ t('security.password_rules') }}</span></div>
                        <div class="security-form-actions">
                            <el-button type="primary" native-type="submit" :loading="securityBusy" :disabled="accountBusy">{{ t('security.save_security_password') }}</el-button>
                        </div>
                    </el-form>
                </el-tab-pane>
                <el-tab-pane name="appearance">
                    <template #label><span class="profile-tab-label"><AppIcon icon="palette-line" size="17" /><span>{{ t('auth.appearance') }}</span></span></template>
                    <div class="appearance-heading">
                        <span class="security-card-icon"><AppIcon icon="palette-line" size="22" /></span>
                        <div>
                            <h3>{{ t('auth.appearance') }}</h3>
                            <p>{{ t('auth.appearance_hint') }}</p>
                        </div>
                    </div>
                    <div class="appearance-form">
                        <div class="appearance-row"><span class="appearance-label">{{ t('auth.theme_mode') }}</span><el-radio-group v-model="appearance.mode"><el-radio-button value="light">{{ t('auth.light') }}</el-radio-button><el-radio-button value="dark">{{ t('auth.dark') }}</el-radio-button><el-radio-button value="system">{{ t('auth.system') }}</el-radio-button></el-radio-group></div>
                        <div class="appearance-row"><span class="appearance-label">{{ t('auth.preset') }}</span><div class="appearance-presets" role="radiogroup" :aria-label="t('auth.preset')"><button v-for="preset in presets" :key="preset" type="button" class="appearance-preset" :class="{ 'is-active': appearance.preset === preset }" :aria-label="t(`auth.${preset}`)" :aria-pressed="appearance.preset === preset" @click="appearance.preset = preset"><span class="appearance-swatch" :style="{ backgroundColor: presetColor(preset) }" aria-hidden="true" /><span>{{ t(`auth.${preset}`) }}</span><AppIcon v-if="appearance.preset === preset" icon="check-line" /></button></div></div>
                        <div class="appearance-row"><span class="appearance-label">{{ t('auth.density') }}</span><el-radio-group v-model="appearance.density"><el-radio-button value="comfortable">{{ t('auth.comfortable') }}</el-radio-button><el-radio-button value="compact">{{ t('auth.compact') }}</el-radio-button></el-radio-group></div>
                        <div class="appearance-row"><span class="appearance-label">{{ t('auth.radius') }}</span><el-radio-group v-model="appearance.radius"><el-radio-button value="small">{{ t('auth.small') }}</el-radio-button><el-radio-button value="medium">{{ t('auth.medium') }}</el-radio-button><el-radio-button value="large">{{ t('auth.large') }}</el-radio-button></el-radio-group></div>
                        <div class="appearance-row"><span class="appearance-label">{{ t('auth.button_size') }}</span><el-radio-group v-model="appearance.button_size"><el-radio-button value="small">{{ t('auth.small') }}</el-radio-button><el-radio-button value="default">{{ t('auth.medium') }}</el-radio-button><el-radio-button value="large">{{ t('auth.large') }}</el-radio-button></el-radio-group></div>
                    </div>
                    <div class="appearance-actions"><el-button :disabled="appearanceBusy" @click="resetAppearance">{{ t('common.reset') }}</el-button><el-button type="primary" :loading="appearanceBusy" @click="saveAppearance">{{ t('common.save') }}</el-button></div>
                </el-tab-pane>
            </el-tabs>
        </BaseCard>
    </div>
</template>

<style scoped>
.profile-page {
    display: grid;
    gap: 18px;
    max-width: 1320px;
    margin: 0 auto;
}
.security-card-heading p,
.appearance-heading p {
    margin: 0;
    color: var(--app-muted);
    font-size: 12px;
    line-height: 1.7;
}
.profile-card {
    max-width: none;
    border-radius: var(--theme-radius);
}
.profile-card :deep(.el-card__body) {
    padding: 0 24px 24px;
}
.profile-tabs :deep(.el-tabs__header) {
    margin: 0 0 24px;
}
.profile-tabs :deep(.el-tabs__item) {
    height: 56px;
    padding: 0 24px;
    font-weight: 500;
}
.profile-tab-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    line-height: 20px;
}
.profile-tab-label .app-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.security-card-heading,
.appearance-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 22px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--el-border-color-lighter);
}
.security-card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    border-radius: 11px;
    background: var(--el-color-primary-light-9);
    color: var(--el-color-primary);
}
.security-card-heading h3,
.appearance-heading h3 {
    margin: 0 0 5px;
    color: var(--el-text-color-primary);
    font-size: 16px;
    line-height: 1.4;
}
.account-security-form {
    max-width: 640px;
}
.account-security-form :deep(.el-form-item) {
    margin-bottom: 18px;
}
.account-security-form :deep(.el-form-item__label) {
    height: auto;
    margin-bottom: 7px;
    padding: 0;
    color: var(--el-text-color-regular);
    font-weight: 500;
    line-height: 20px;
}
.account-security-form :deep(.el-input) {
    width: 100%;
}
.password-help {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    margin: 2px 0 20px;
    padding: 10px 12px;
    border: 1px solid var(--app-border);
    border-radius: var(--el-border-radius-base);
    background: var(--app-soft);
    color: var(--app-muted);
    font-size: 12px;
    line-height: 20px;
}
.password-help .app-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    height: 20px;
    color: var(--el-color-primary);
}
.security-form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 18px;
    border-top: 1px solid var(--el-border-color-lighter);
}
.security-form-actions .el-button {
    min-width: 112px;
}
.appearance-form {
    display: grid;
}
.appearance-row {
    display: grid;
    grid-template-columns: 144px minmax(0, 1fr);
    align-items: center;
    gap: 16px;
    padding: 15px 0;
    border-bottom: 1px solid var(--el-border-color-lighter);
}
.appearance-row:first-child {
    padding-top: 0;
}
.appearance-row:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}
.appearance-label {
    color: var(--el-text-color-regular);
    font-weight: 500;
}
.appearance-row :deep(.el-radio-group) {
    flex-wrap: wrap;
}
.appearance-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.appearance-preset {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: var(--button-component-size);
    min-height: 0;
    padding: 7px 12px;
    border: 1px solid var(--el-border-color);
    border-radius: var(--el-border-radius-base);
    background: var(--el-bg-color);
    color: var(--el-text-color-regular);
    font-size: var(--button-font-size);
    cursor: pointer;
}
.appearance-preset:hover,
.appearance-preset.is-active {
    border-color: var(--el-color-primary);
    color: var(--el-color-primary);
}
.appearance-preset.is-active {
    background: var(--el-color-primary-light-9);
    box-shadow: 0 0 0 1px var(--el-color-primary-light-7);
}
.appearance-preset:focus-visible {
    outline: 2px solid var(--el-color-primary-light-5);
    outline-offset: 2px;
}
.appearance-swatch {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgb(0 0 0 / 8%);
}
.appearance-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid var(--el-border-color-lighter);
}
@media (max-width: 720px) {
    .profile-card :deep(.el-card__body) {
        padding: 0 18px 18px;
    }
    .profile-tabs :deep(.el-tabs__header) {
        margin-bottom: 20px;
    }
    .profile-tabs :deep(.el-tabs__item) {
        padding: 0 16px;
    }
    .appearance-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}
@media (max-width: 480px) {
    .security-form-actions .el-button {
        width: 100%;
    }
}
</style>
