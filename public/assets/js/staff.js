// ===========================================================
// assets/js/staff.js
// Controlador principal del Panel STAFF
// ===========================================================

import { api } from "../../frontend/utils/api.js";
import { auth } from "../../frontend/utils/auth.js";
import {
    showToast,
    getQueryParam,
    formatDate,
    formatTime,
    formatCurrency
} from "../../frontend/utils/helpers.js";

document.addEventListener("DOMContentLoaded", () => {
    auth.requireRole(["staff", "admin"], "/SisCine/public/frontend/pages/index.html");

    const page = document.body.dataset.page;

    switch (page) {
        case "staff-dashboard":
            loadStaffDashboard();
            break;

        case "staff-validate-booking":
            setupBookingValidation();
            break;

        case "staff-manual-booking":
            setupManualBooking();
            break;

        default:
            console.warn("Página staff no reconocida.");
    }
});


// ===========================================================
// 1. STAFF DASHBOARD
// ===========================================================

async function loadStaffDashboard() {
    const todayShowtimesEl = document.getElementById("todayShowtimes");
    const pendingBookingsEl = document.getElementById("pendingBookings");

    try {
        const data = await api.staffDashboard();

        // Render Showtimes
        todayShowtimesEl.innerHTML = "";
        data.showtimes?.forEach(st => {
            todayShowtimesEl.innerHTML += `
                <div class="p-3 bg-gray-800 rounded shadow mb-2">
                    <p class="font-bold">${st.movie_title}</p>
                    <p>Sala: ${st.room_name}</p>
                    <p>${formatDate(st.show_date)} - ${st.show_time}</p>
                    <p>Asientos ocupados: ${st.occupied}/${st.capacity}</p>
                </div>
            `;
        });

        // Render Pending Bookings
        pendingBookingsEl.innerHTML = "";
        data.pending?.forEach(b => {
            pendingBookingsEl.innerHTML += `
                <div class="bg-gray-700 p-3 rounded mb-2">
                    <p><b>Código:</b> ${b.booking_code}</p>
                    <p><b>Cliente:</b> ${b.customer_name}</p>
                    <p><b>Total:</b> ${formatCurrency(b.final_amount, "PEN")}</p>
                </div>
            `;
        });

    } catch (error) {
        showToast(error.message, "error");
    }
}



// ===========================================================
// 2. VALIDACIÓN DE RESERVA
// staff/validate-booking.html
// ===========================================================

function setupBookingValidation() {
    const searchBtn = document.getElementById("searchBookingBtn");
    const codeInput = document.getElementById("bookingCodeInput");

    searchBtn?.addEventListener("click", () => searchBooking(codeInput.value.trim()));
}

async function searchBooking(code) {
    if (!code) return showToast("Ingresa un código de reserva", "error");

    const detailsEl = document.getElementById("bookingDetails");

    try {
        const booking = await api.staffFindBooking(code);

        detailsEl.innerHTML = `
            <div class="bg-gray-800 p-4 rounded shadow text-white">
                <p><b>Cliente:</b> ${booking.customer_name}</p>
                <p><b>Película:</b> ${booking.movie_title}</p>
                <p><b>Fecha:</b> ${formatDate(booking.show_date)} ${booking.show_time}</p>
                <p><b>Pago:</b> ${booking.payment_status}</p>
                <p><b>Estado:</b> ${booking.status}</p>
                <p><b>Total:</b> ${formatCurrency(booking.final_amount, "PEN")}</p>

                <div class="mt-4 flex gap-2">
                    <button id="confirmCashBtn" class="bg-green-600 px-3 py-1 rounded">Confirmar pago efectivo</button>
                    <button id="changeSeatsBtn" class="bg-yellow-500 px-3 py-1 rounded">Cambiar asientos</button>
                    <button id="refundBtn" class="bg-red-500 px-3 py-1 rounded">Reembolso</button>
                    <button id="printBtn" class="bg-blue-500 px-3 py-1 rounded">Imprimir</button>
                </div>
            </div>
        `;

        // Event Listeners
        document.getElementById("confirmCashBtn").addEventListener("click", () => confirmCash(booking.id));
        document.getElementById("refundBtn").addEventListener("click", () => refundBooking(booking.id));
        document.getElementById("printBtn").addEventListener("click", () => printTicket(booking.id));

    } catch (error) {
        showToast(error.message, "error");
    }
}

async function confirmCash(id) {
    try {
        await api.staffConfirmCash(id);
        showToast("Pago confirmado ✔️", "success");
    } catch (e) {
        showToast(e.message, "error");
    }
}

async function refundBooking(id) {
    try {
        await api.staffRefund(id);
        showToast("Reembolso realizado ✔️", "success");
    } catch (e) {
        showToast(e.message, "error");
    }
}

function printTicket(id) {
    window.open(`/SisCine/api/bookings/${id}/ticket`, "_blank");
}



// ===========================================================
// 3. RESERVA MANUAL
// staff/manual-booking.html
// ===========================================================

function setupManualBooking() {
    loadMoviesForManualBooking();

    document.getElementById("movieSelect")?.addEventListener("change", loadShowtimesForMovie);
    document.getElementById("showtimeSelect")?.addEventListener("change", loadSeatsForManualBooking);

    document.getElementById("confirmManualBookingBtn")?.addEventListener("click", confirmManualBooking);
}

// ---------- Cargar Películas ----------
async function loadMoviesForManualBooking() {
    const select = document.getElementById("movieSelect");
    if (!select) return;

    try {
        const movies = await api.getMovies();
        select.innerHTML = `<option value="">Selecciona película</option>`;

        movies.forEach(m => {
            select.innerHTML += `<option value="${m.id}">${m.title}</option>`;
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}

// ---------- Cargar Horarios ----------
async function loadShowtimesForMovie() {
    const movieId = this.value;
    const select = document.getElementById("showtimeSelect");

    if (!movieId || !select) return;

    try {
        const showtimes = await api.getMovieShowtimes(movieId);
        select.innerHTML = `<option value="">Selecciona horario</option>`;

        showtimes.forEach(st => {
            select.innerHTML += `
                <option value="${st.id}">
                    ${formatDate(st.show_date)} - ${st.show_time} (Sala ${st.room_name})
                </option>
            `;
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}

// ---------- Cargar Mapa de Asientos ----------
async function loadSeatsForManualBooking() {
    const showtimeId = this.value;
    const container = document.getElementById("manualSeatMap");
    container.innerHTML = "";

    if (!showtimeId) return;

    try {
        const seats = await api.getSeatsByShowtime(showtimeId);
        const rows = groupSeatsByRow(seats);

        Object.keys(rows).forEach(row => {
            const rowEl = document.createElement("div");
            rowEl.className = "flex items-center gap-2 mb-2";

            const label = document.createElement("span");
            label.className = "text-gray-300 w-8 text-right";
            label.innerText = row;

            const seatRow = document.createElement("div");
            seatRow.className = "flex gap-2";

            rows[row].forEach(seat => {
                seatRow.appendChild(renderManualSeat(seat));
            });

            rowEl.appendChild(label);
            rowEl.appendChild(seatRow);
            container.appendChild(rowEl);
        });

    } catch (e) {
        showToast(e.message, "error");
    }
}

function groupSeatsByRow(seats) {
    return seats.reduce((acc, seat) => {
        if (!acc[seat.row_label]) acc[seat.row_label] = [];
        acc[seat.row_label].push(seat);
        return acc;
    }, {});
}

let manuallySelectedSeats = [];

function renderManualSeat(seat) {
    const btn = document.createElement("button");

    btn.className = `
        w-8 h-8 rounded text-xs flex items-center justify-center
        ${seat.status === "available" ? "bg-green-600 hover:bg-green-500" : "bg-gray-600 cursor-not-allowed"}
    `;

    btn.innerText = seat.seat_number;

    if (seat.status !== "available") {
        btn.disabled = true;
        return btn;
    }

    btn.addEventListener("click", () => {
        const index = manuallySelectedSeats.indexOf(seat.id);

        if (index >= 0) {
            manuallySelectedSeats.splice(index, 1);
            btn.classList.remove("bg-blue-600");
            btn.classList.add("bg-green-600");
        } else {
            manuallySelectedSeats.push(seat.id);
            btn.classList.remove("bg-green-600");
            btn.classList.add("bg-blue-600");
        }
    });

    return btn;
}

// ---------- Confirmar reserva ----------
async function confirmManualBooking() {
    const customerName = document.getElementById("manualCustomerName").value.trim();
    const showtimeId = document.getElementById("showtimeSelect").value;

    if (!customerName) return showToast("Debes ingresar el nombre del cliente", "error");
    if (!showtimeId) return showToast("Selecciona un horario", "error");
    if (manuallySelectedSeats.length === 0) return showToast("Selecciona al menos un asiento", "error");

    try {
        const result = await api.staffCreateManualBooking({
            customer_name: customerName,
            showtime_id: showtimeId,
            seat_ids: manuallySelectedSeats
        });

        showToast("Reserva creada ✔️", "success");

        window.open(`/SisCine/api/bookings/${result.booking_id}/ticket`, "_blank");

        manuallySelectedSeats = [];
        loadSeatsForManualBooking.call({ value: showtimeId });

    } catch (e) {
        showToast(e.message, "error");
    }
}
