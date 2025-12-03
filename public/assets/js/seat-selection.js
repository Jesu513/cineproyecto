// ===========================================
// assets/js/seat-selection.js
// Lógica de selección de asientos
// ===========================================

import { api } from "../../frontend/utils/api.js";
import { getQueryParam, showToast } from "../../frontend/utils/helpers.js";
import { auth } from "../../frontend/utils/auth.js";

document.addEventListener("DOMContentLoaded", () => {
    auth.requireAuth();

    loadSeatMap();

    document.getElementById("continueBtn")?.addEventListener("click", goToBookingPage);
});

let selectedSeats = [];

/**
 * Cargar mapa de asientos
 */
async function loadSeatMap() {
    const showtimeId = getQueryParam("showtime_id");

    if (!showtimeId) {
        return showToast("Horario no encontrado", "error");
    }

    const container = document.getElementById("seatMap");
    const loader = document.getElementById("loader");

    loader.classList.remove("hidden");

    try {
        const seats = await api.getSeatsByShowtime(showtimeId);

        loader.classList.add("hidden");

        renderSeatMap(seats);

    } catch (error) {
        loader.classList.add("hidden");
        showToast(error.message, "error");
    }
}

/**
 * Renderizar mapa interactivo
 */
function renderSeatMap(seats) {
    const container = document.getElementById("seatMap");
    container.innerHTML = "";

    // Agrupar por filas
    const rows = groupByRow(seats);

    Object.keys(rows).forEach(rowLabel => {
        const rowWrapper = document.createElement("div");
        rowWrapper.className = "flex items-center gap-2 mb-2";

        const rowName = document.createElement("span");
        rowName.className = "text-gray-300 w-8 text-right";
        rowName.innerText = rowLabel;

        const seatsRow = document.createElement("div");
        seatsRow.className = "flex gap-2";

        rows[rowLabel].forEach(seat => {
            seatsRow.appendChild(renderSeatButton(seat));
        });

        rowWrapper.appendChild(rowName);
        rowWrapper.appendChild(seatsRow);
        container.appendChild(rowWrapper);
    });
}

/**
 * Agrupar asientos por fila
 */
function groupByRow(seats) {
    return seats.reduce((acc, seat) => {
        if (!acc[seat.row_label]) acc[seat.row_label] = [];
        acc[seat.row_label].push(seat);
        return acc;
    }, {});
}

/**
 * Renderizar un asiento individual
 */
function renderSeatButton(seat) {
    const btn = document.createElement("button");

    let seatColor = getSeatColor(seat.status);

    btn.className = `
        w-10 h-10 rounded 
        flex items-center justify-center 
        text-white text-sm font-bold
        ${seatColor}
        transition
    `;

    btn.innerText = seat.seat_number;

    // Deshabilitar si está ocupado
    if (seat.status !== "available") {
        btn.disabled = true;
        return btn;
    }

    // Evento de selección
    btn.addEventListener("click", () => toggleSeatSelection(seat, btn));

    return btn;
}

/**
 * Color según estado del asiento
 */
function getSeatColor(status) {
    return {
        "available": "bg-green-600 hover:bg-green-500",
        "occupied": "bg-red-600 cursor-not-allowed",
        "unavailable": "bg-gray-500 cursor-not-allowed",
        "reserved": "bg-yellow-500 cursor-not-allowed"
    }[status] || "bg-gray-700";
}

/**
 * Seleccionar / deselect asiento
 */
function toggleSeatSelection(seat, btn) {
    const index = selectedSeats.indexOf(seat.id);

    if (index >= 0) {
        // Quitar selección
        selectedSeats.splice(index, 1);
        btn.classList.remove("bg-blue-600");
        btn.classList.add("bg-green-600");
    } else {
        selectedSeats.push(seat.id);
        btn.classList.remove("bg-green-600");
        btn.classList.add("bg-blue-600");
    }

    updateSeatCounter();
}

/**
 * Mostrar número de asientos seleccionados
 */
function updateSeatCounter() {
    const counter = document.getElementById("selectedCount");
    counter.innerText = selectedSeats.length;
}

/**
 * Continuar a booking.html
 */
function goToBookingPage() {
    const showtimeId = getQueryParam("showtime_id");

    if (selectedSeats.length === 0) {
        return showToast("Selecciona al menos un asiento", "error");
    }

    const seatParam = selectedSeats.join(",");

    window.location.href =
        `/SisCine/public/frontend/pages/booking.html?showtime_id=${showtimeId}&seats=${seatParam}`;
}
