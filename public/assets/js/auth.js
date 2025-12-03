// =======================================
// assets/js/auth.js
// Manejo de Login y Registro en frontend
// =======================================

import { api } from "../../frontend/utils/api.js";
import { auth } from "../../frontend/utils/auth.js";
import { showToast } from "../../frontend/utils/helpers.js";

document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");

    if (loginForm) initLogin(loginForm);
    if (registerForm) initRegister(registerForm);
});

/**
 * Inicializar formulario de login
 */
function initLogin(form) {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const email = form.email.value.trim();
        const password = form.password.value.trim();

        if (!email || !password) {
            return showToast("Completa todos los campos", "error");
        }

        try {
            const response = await api.login(email, password);

            auth.saveSession(response.token, response.user);

            showToast("Bienvenido nuevamente 🎬", "success");

            // Redirigir al home
            window.location.href = "/SisCine/public/frontend/pages/index.html";

        } catch (error) {
            showToast(error.message, "error");
        }
    });
}

/**
 * Inicializar formulario de registro
 */
function initRegister(form) {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const payload = {
            name: form.name.value.trim(),
            email: form.email.value.trim(),
            password: form.password.value.trim(),
            password_confirmation: form.password_confirmation.value.trim()
        };

        // Validación básica
        if (!payload.name || !payload.email || !payload.password || !payload.password_confirmation) {
            return showToast("Completa todos los campos", "error");
        }

        if (payload.password !== payload.password_confirmation) {
            return showToast("Las contraseñas no coinciden", "error");
        }

        try {
            const response = await api.register(payload);

            // Guardar sesión automáticamente
            auth.saveSession(response.token, response.user);

            showToast("Registro exitoso 🎉 Bienvenido a SisCine", "success");

            window.location.href = "/SisCine/public/frontend/pages/index.html";

        } catch (error) {
            showToast(error.message, "error");
        }
    });
}
