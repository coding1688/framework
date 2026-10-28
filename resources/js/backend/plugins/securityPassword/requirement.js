export function securityPasswordRequirement(routeName, routes = {}) {
    const requirement = routes?.[routeName]?.security_password;
    if (requirement?.required !== true) return null;
    return { required: true, mode: requirement.mode === 'dialog' ? 'dialog' : 'form' };
}
