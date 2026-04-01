/**
 * @param {{ role?: string, permissions?: string[] }|null|undefined} user
 * @param {string} permission
 */
export function canAccess(user, permission) {
    if (!user) {
        return false;
    }
    const perms = user.permissions;
    if (!Array.isArray(perms)) {
        return user.role === 'admin';
    }
    if (perms.includes('*')) {
        return true;
    }
    return perms.includes(permission);
}
