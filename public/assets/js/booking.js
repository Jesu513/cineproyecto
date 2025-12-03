// ===========================================
// assets/js/booking.js
// Lógica para la página booking.html
// ===========================================

import { api } from "../../frontend/utils/api.js";
import { getQueryParam, showToast, formatCurrency } from "../../frontend/utils/helpers.js";
import { auth } from "../../frontend/utils/auth.js";

document.addEventListener("DOMContentLoaded", () => {
    auth.requireAuth(); // Solo usuarios logueados

    loadBookingSummary();

    document.getElementById("applyCouponBtn")?.addEventListener("click", applyCoupon);
    document.getElementById("confirmBookingBtn")?.addEventListener("click", createTemporaryBooking);
});

// Datos temporales del booking en esta página
let tempBooking = {
    showtime_id: null,
    seat_ids: [],
    subtotal: 0,
    discount: 0,
    total: 0,
    coupon: null
};

/**
 * Cargar resumen de la reserva
 */
async function loadBookingSummary() {
    const showtimeId = getQueryParam("showtime_id");
    const seats = getQueryParam("seats");

    if (!showtimeId || !seats) {
        showToast("Datos incompletos para la reserva", "error");
        return;
    }

    tempBooking.showtime_id = parseInt(showtimeId);
    tempBooking.seat_ids = seats.split(",").map(Number);

    try {
        const seatMap = await api.getSeatsByShowtime(showtimeId);
        const seatData = filterSelectedSeats(seatMap);
        
        renderSummary(seatData);

    } catch (error) {
        showToast(error.message, "error");
    }
}

/**
 * Filtrar solo los asientos seleccionados por el usuario
 */
function filterSelectedSeats(seatMap) {
    return seatMap.filter(seat => tempBooking.seat_ids.includes(seat.id));
}

/**
 * Mostrar resumen en pantalla
 */
function renderSummary(selectedSeats) {
    const list = document.getElementById("seatSummary");
    const subtotalEl = document.getElementById("subtotal");
    const discountEl = document.getElementById("discount");
    const totalEl = document.getElementById("total");

    list.innerHTML = "";

    let subtotal = 0;

    selectedSeats.forEach(seat => {
        subtotal += seat.price_modifier * 20; // ejemplo: precio base 20

        const item = document.createElement("div");
        item.className = "flex justify-between border-b border-gray-700 py-2";
        item.innerHTML = `
            <span>Fila ${seat.row_label} - Asiento ${seat.seat_number}</span>
            <span>${formatCurrency(seat.price_modifier * 20, "PEN")}</span>
        `;
        list.appendChild(item);
    });

    tempBooking.subtotal = subtotal;
    tempBooking.total = subtotal;

    subtotalEl.innerText = formatCurrency(subtotal, "PEN");
    discountEl.innerText = formatCurrency(0, "PEN");
    totalEl.innerText = formatCurrency(subtotal, "PEN");
}

/**
 * Aplicar cupón de descuento
 */
async function applyCoupon() {
    const code = document.getElementById("couponInput").value.trim();
    if (!code) return showToast("Ingresa un código de cupón", "error");

    try {
        const response = await api.validateCoupon(code, 0); // booking no creado aún

        tempBooking.discount = response.discount_value;
        tempBooking.total = tempBooking.subtotal - response.discount_value;
        tempBooking.coupon = code;

        document.getElementById("discount").innerText = formatCurrency(tempBooking.discount, "PEN");
        document.getElementById("total").innerText = formatCurrency(tempBooking.total, "PEN");

        showToast("Cupón aplicado correctamente", "success");

    } catch (error) {
        showToast(error.message, "error");
    }
}

/**
 * Crear reserva temporal (pendiente por pago)
 */
async function createTemporaryBooking() {
    try {
        const result = await api.reserveSeats(tempBooking.showtime_id, tempBooking.seat_ids);

        showToast("Reserva temporal creada. Tienes 10 minutos para pagar.", "success");

        window.location.href = `/SisCine/public/frontend/pages/payment.html?booking_id=${result.booking_id}`;

    } catch (error) {
        showToast(error.message, "error");
    }
}
