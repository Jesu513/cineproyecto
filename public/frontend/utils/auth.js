// ================================
// frontend/utils/auth.js
// Manejo de autenticación en frontend
// ================================

import { api } from './api.js';

const AUTH_TOKEN_KEY = 'auth_token';
const AUTH_USER_KEY = 'auth_user';

export const auth = {
  saveSession(token, user) {
    localStorage.setItem(AUTH_TOKEN_KEY, token);
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user));
  },

  getToken() {
    return localStorage.getItem(AUTH_TOKEN_KEY);
  },

  getUser() {
    const raw = localStorage.getItem(AUTH_USER_KEY);
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  },

  isLoggedIn() {
    return !!this.getToken();
  },

  hasRole(role) {
    const user = this.getUser();
    if (!user) return false;

    if (Array.isArray(role)) {
      return role.includes(user.role);
    }

    return user.role === role;
  },

  async refreshUser() {
    if (!this.getToken()) return null;
    try {
      const me = await api.me();
      localStorage.setItem(AUTH_USER_KEY, JSON.stringify(me));
      return me;
    } catch (e) {
      this.logout();
      return null;
    }
  },

  logout() {
    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(AUTH_USER_KEY);
    // Opcional: también avisar al backend
    try {
      api.logout();
    } catch (e) {}
  },

  requireAuth(redirectTo = '/SisCine/public/frontend/pages/login.html') {
    if (!this.isLoggedIn()) {
      window.location.href = redirectTo;
    }
  },

  requireRole(roles, redirectTo = '/SisCine/public/frontend/pages/index.html') {
    if (!this.isLoggedIn()) {
      window.location.href = '/SisCine/public/frontend/pages/login.html';
      return;
    }
    if (!this.hasRole(roles)) {
      window.location.href = redirectTo;
    }
  }
};

window.auth = {
    getUser: auth.getUser.bind(auth),
    logout: auth.logout.bind(auth),
    isLoggedIn: auth.isLoggedIn.bind(auth)
};

