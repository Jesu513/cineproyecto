// =============================================
// assets/js/admin.js
// Control principal del Panel de Administración
// =============================================

import { api } from "../../frontend/utils/api.js";
import { auth } from "../../frontend/utils/auth.js";
import { showToast, formatDate, formatCurrency } from "../../frontend/utils/helpers.js";

document.addEventListener("DOMContentLoaded", () => {
    // Solo un admin puede estar aquí
    auth.requireRole("admin", "/SisCine/public/frontend/pages/index.html");

    const page = document.body.dataset.page;

    switch (page) {
        case "admin-dashboard":
            loadDashboard();
            break;

        case "admin-movies":
            loadMovies();
            setupMovieEvents();
            break;

        case "admin-rooms":
            loadRooms();
            setupRoomEvents();
            break;

        case "admin-showtimes":
            loadShowtimes();
            setupShowtimeEvents();
            break;

        case "admin-bookings":
            loadAllBookings();
            break;

        case "admin-users":
            loadUsers();
            setupUserEvents();
            break;

        case "admin-promotions":
            loadPromotions();
            setupPromotionEvents();
            break;

        case "admin-reports":
            loadReports();
            break;

        default:
            console.warn("No se reconoce la página admin actual.");
    }
});


// ======================================================
// 1. DASHBOARD ADMIN
// ======================================================
async function loadDashboard() {
    try {
        const data = await api.adminDashboard();

        document.getElementById("totalMovies").innerText = data.total_movies;
        document.getElementById("totalUsers").innerText = data.total_users;
        document.getElementById("totalBookings").innerText = data.total_bookings;
        document.getElementById("totalRevenue").innerText = formatCurrency(data.total_revenue, "PEN");

    } catch (error) {
        showToast(error.message, "error");
    }
}


// ======================================================
// 2. GESTIÓN DE PELÍCULAS
// ======================================================
async function loadMovies() {
    const tbody = document.getElementById("moviesTableBody");
    if (!tbody) return;

    tbody.innerHTML = "<tr><td colspan='6' class='text-center'>Cargando...</td></tr>";

    try {
        const movies = await api.adminGetMovies();

        tbody.innerHTML = "";

        movies.forEach(movie => {
            tbody.innerHTML += `
                <tr class="border-b border-gray-700">
                    <td><img src="${movie.poster_url}" class="w-12 h-16 object-cover rounded"></td>
                    <td>${movie.title}</td>
                    <td>${movie.genres?.join(", ") || "-"}</td>
                    <td>${movie.is_active ? "🟢 Activa" : "🔴 Inactiva"}</td>
                    <td>
                        <button class="editMovie bg-blue-500 px-3 py-1 rounded" data-id="${movie.id}">Editar</button>
                        <button class="toggleMovie bg-yellow-500 px-3 py-1 rounded" data-id="${movie.id}">
                            ${movie.is_active ? "Desactivar" : "Activar"}
                        </button>
                    </td>
                </tr>
            `;
        });

    } catch (error) {
        tbody.innerHTML = "<tr><td colspan='6'>Error al cargar películas</td></tr>";
        showToast(error.message, "error");
    }
}

function setupMovieEvents() {
    document.addEventListener("click", async (e) => {
        if (e.target.classList.contains("toggleMovie")) {
            const id = e.target.dataset.id;
            try {
                await api.adminToggleMovieStatus(id);
                showToast("Estado actualizado", "success");
                loadMovies();
            } catch (error) {
                showToast(error.message, "error");
            }
        }

        if (e.target.classList.contains("editMovie")) {
            const id = e.target.dataset.id;
            window.location.href = `/SisCine/public/frontend/pages/admin/movie-edit.html?id=${id}`;
        }
    });
}


// ======================================================
// 3. GESTIÓN DE SALAS
// ======================================================
async function loadRooms() {
    const tbody = document.getElementById("roomsTableBody");
    if (!tbody) return;

    try {
        const rooms = await api.adminGetRooms();

        tbody.innerHTML = "";

        rooms.forEach(room => {
            tbody.innerHTML += `
                <tr class="border-b border-gray-700">
                    <td>${room.name}</td>
                    <td>${room.capacity}</td>
                    <td>${room.room_type}</td>
                    <td>${room.is_active ? "🟢" : "🔴"}</td>
                    <td>
                        <button data-id="${room.id}" class="editRoom bg-blue-500 px-3 py-1 rounded">Editar</button>
                    </td>
                </tr>
            `;
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}

function setupRoomEvents() {
    document.addEventListener("click", e => {
        if (e.target.classList.contains("editRoom")) {
            const id = e.target.dataset.id;
            window.location.href = `/SisCine/public/frontend/pages/admin/room-edit.html?id=${id}`;
        }
    });
}


// ======================================================
// 4. GESTIÓN DE SHOWTIMES
// ======================================================
async function loadShowtimes() {
    const tbody = document.getElementById("showtimesTableBody");
    if (!tbody) return;

    try {
        const showtimes = await api.adminGetShowtimes();

        tbody.innerHTML = "";

        showtimes.forEach(st => {
            tbody.innerHTML += `
                <tr class="border-b border-gray-700">
                    <td>${st.movie_title}</td>
                    <td>${st.room_name}</td>
                    <td>${formatDate(st.show_date)}</td>
                    <td>${st.show_time}</td>
                    <td>${formatCurrency(st.base_price, "PEN")}</td>
                    <td>
                        <button class="editShowtime bg-blue-500 px-3 py-1 rounded" data-id="${st.id}">Editar</button>
                    </td>
                </tr>
            `;
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}

function setupShowtimeEvents() {
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("editShowtime")) {
            const id = e.target.dataset.id;
            window.location.href = `/SisCine/public/frontend/pages/admin/showtime-edit.html?id=${id}`;
        }
    });
}


// ======================================================
// 5. GESTIÓN DE RESERVAS COMPLETAS
// ======================================================
async function loadAllBookings() {
    const tbody = document.getElementById("bookingsTableBody");
    if (!tbody) return;

    try {
        const bookings = await api.adminGetBookings();

        tbody.innerHTML = "";

        bookings.forEach(b => {
            tbody.innerHTML += `
                <tr class="border-b border-gray-700">
                    <td>${b.booking_code}</td>
                    <td>${b.customer_name}</td>
                    <td>${b.movie_title}</td>
                    <td>${formatDate(b.show_date)}</td>
                    <td>${b.show_time}</td>
                    <td>${formatCurrency(b.final_amount, "PEN")}</td>
                    <td>${b.status}</td>
                </tr>
            `;
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}


// ======================================================
// 6. GESTIÓN DE USUARIOS
// ======================================================
async function loadUsers() {
    const tbody = document.getElementById("usersTableBody");
    if (!tbody) return;

    try {
        const users = await api.adminGetUsers();

        tbody.innerHTML = "";

        users.forEach(u => {
            tbody.innerHTML += `
                <tr class="border-b border-gray-700">
                    <td>${u.name}</td>
                    <td>${u.email}</td>
                    <td>${u.role}</td>
                    <td>${u.is_active ? "🟢" : "🔴"}</td>
                    <td>
                        <button data-id="${u.id}" class="toggleUser bg-yellow-500 px-3 py-1 rounded">
                            ${u.is_active ? "Desactivar" : "Activar"}
                        </button>
                    </td>
                </tr>
            `;
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}

function setupUserEvents() {
    document.addEventListener("click", async (e) => {
        if (e.target.classList.contains("toggleUser")) {
            const id = e.target.dataset.id;
            try {
                await api.adminToggleUser(id);
                showToast("Usuario actualizado", "success");
                loadUsers();
            } catch (error) {
                showToast(error.message, "error");
            }
        }
    });
}


// ======================================================
// 7. GESTIÓN DE PROMOCIONES
// ======================================================
async function loadPromotions() {
    const tbody = document.getElementById("promotionsTableBody");
    if (!tbody) return;

    try {
        const promos = await api.getActivePromotions();

        tbody.innerHTML = "";

        promos.forEach(p => {
            tbody.innerHTML += `
                <tr class="border-b border-gray-700">
                    <td>${p.code}</td>
                    <td>${p.name}</td>
                    <td>${p.discount_type}</td>
                    <td>${p.discount_value}</td>
                    <td>${formatDate(p.valid_from)}</td>
                    <td>${formatDate(p.valid_until)}</td>
                </tr>
            `;
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}

function setupPromotionEvents() {
    // Aquí iría la lógica para crear promociones
}


// ======================================================
// 8. REPORTES
// ======================================================
async function loadReports() {
    const revenueEl = document.getElementById("totalRevenueReport");

    try {
        const data = await api.adminReportSummary();
        revenueEl.innerText = formatCurrency(data.total_revenue, "PEN");
    } catch (e) {
        showToast(e.message, "error");
    }
}
