// ================================
// assets/js/app.js
// Inicialización global del frontend
// ================================

import { auth } from "../../frontend/utils/auth.js";
import { showToast } from "../../frontend/utils/helpers.js";

document.addEventListener("DOMContentLoaded", () => {
    initializeNavbar();
});

/**
 * Inserta el navbar dinámico dependiendo del estado de sesión.
 */
function initializeNavbar() {
    const navbarContainer = document.getElementById("navbar-root");
    if (!navbarContainer) return;

    const user = auth.getUser();
    const isLogged = auth.isLoggedIn();

    navbarContainer.innerHTML = `
        <nav class="bg-gray-900 text-white px-6 py-4 flex justify-between items-center">
            <a href="/SisCine/public/frontend/pages/index.html" class="text-xl font-bold">
                🎬 SisCine
            </a>

            <div class="flex items-center gap-6">
                <a href="/SisCine/public/frontend/pages/movies.html" class="hover:text-yellow-400">Películas</a>

                ${isLogged ? `
                    <a href="/SisCine/public/frontend/pages/my-bookings.html" class="hover:text-yellow-400">Mis reservas</a>
                    <a href="/SisCine/public/frontend/pages/profile.html" class="hover:text-yellow-400">${user.name}</a>
                    <button id="logoutBtn" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                        Cerrar sesión
                    </button>
                ` : `
                    <a href="/SisCine/public/frontend/pages/login.html" class="bg-blue-500 px-3 py-1 rounded hover:bg-blue-600">
                        Iniciar sesión
                    </a>
                `}
            </div>
        </nav>
    `;

    if (isLogged) {
        document.getElementById("logoutBtn")?.addEventListener("click", handleLogout);
    }
}

/**
 * Manejar cierre de sesión
 */
async function handleLogout() {
    try {
        await auth.logout();
        showToast("Sesión cerrada", "success");
        window.location.href = "/SisCine/public/frontend/pages/index.html";
    } catch (e) {
        showToast("No se pudo cerrar sesión", "error");
    }
}
