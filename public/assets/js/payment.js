// ===========================================
// assets/js/payment.js
// Lógica para la página payment.html
// ===========================================

import { api } from "../../frontend/utils/api.js";
import { auth } from "../../frontend/utils/auth.js";
import {
    getQueryParam,
    showToast,
    formatCurrency
} from "../../frontend/utils/helpers.js";

let stripe = null;
let cardElement = null;
let currentBooking = null;
let bookingId = null;

document.addEventListener("DOMContentLoaded", () => {
    auth.requireAuth();

    bookingId = getQueryParam("booking_id");
    if (!bookingId) {
        showToast("Reserva no encontrada", "error");
        return;
    }

    initStripe();
    loadBookingInfo();

    const form = document.getElementById("paymentForm");
    if (form) {
        form.addEventListener("submit", handlePaymentSubmit);
    }
});

/**
 * Inicializar Stripe usando la clave pública del <meta name="stripe-key">
 */
function initStripe() {
    const key = document.querySelector('meta[name="stripe-key"]')?.content;

    if (!key || !window.Stripe) {
        console.warn("Stripe no está configurado correctamente.");
        showToast("El pago con tarjeta no está disponible por el momento.", "error");
        return;
    }

    stripe = Stripe(key);
    const elements = stripe.elements();
    cardElement = elements.create("card");
    cardElement.mount("#card-element");
}

/**
 * Cargar información de la reserva (desde /bookings/my-bookings)
 */
async function loadBookingInfo() {
    const bookingInfoContainer = document.getElementById("bookingInfo");
    const amountEl = document.getElementById("amountToPay");

    if (!bookingInfoContainer || !amountEl) return;

    try {
        const bookings = await api.getMyBookings();
        const idNum = Number(bookingId);

        currentBooking = (bookings || []).find(b => Number(b.id) === idNum);

        if (!currentBooking) {
            showToast("Reserva no encontrada", "error");
            return;
        }

        bookingInfoContainer.innerHTML = `
            <p><span class="font-semibold">Código de reserva:</span> ${currentBooking.booking_code}</p>
            <p><span class="font-semibold">Película:</span> ${currentBooking.movie_title || "-"}</p>
            <p><span class="font-semibold">Fecha:</span> ${currentBooking.show_date || "-"}</p>
            <p><span class="font-semibold">Hora:</span> ${currentBooking.show_time || "-"}</p>
            <p><span class="font-semibold">Asientos:</span> ${(currentBooking.seats || []).join(", ")}</p>
        `;

        amountEl.innerText = formatCurrency(currentBooking.final_amount ?? currentBooking.total_amount ?? 0, "PEN");

    } catch (error) {
        showToast(error.message, "error");
    }
}

/**
 * Manejar envío del formulario de pago
 */
async function handlePaymentSubmit(e) {
    e.preventDefault();

    const methodSelect = document.getElementById("paymentMethod");
    const payButton = document.getElementById("payButton");

    if (!methodSelect || !payButton) return;

    const method = methodSelect.value;

    if (!currentBooking) {
        showToast("No se pudo obtener información de la reserva", "error");
        return;
    }

    // Bloquear botón mientras procesa
    payButton.disabled = true;
    payButton.innerText = "Procesando...";

    try {
        if (method === "card") {
            await handleCardPayment();
        } else if (method === "qr") {
            // Aquí podrías integrar un flujo de QR real.
            showToast("Simulación de pago por QR completada.", "info");
            // Si quieres marcarlo como pagado en backend, podrías tener otro endpoint.
            // Por ahora solo redirigimos.
        } else if (method === "cash") {
            showToast("Pago en efectivo seleccionado. Deberás completar el pago en taquilla.", "info");
            // No confirmamos el pago aún. El staff lo hará en su panel.
        } else {
            showToast("Método de pago no soportado.", "error");
        }

        // Redirigir a Mis Reservas
        window.location.href = "/SisCine/public/frontend/pages/my-bookings.html";

    } catch (error) {
        showToast(error.message, "error");
    } finally {
        payButton.disabled = false;
        payButton.innerText = "Pagar ahora";
    }
}

/**
 * Flujo de pago con tarjeta (Stripe)
 */
async function handleCardPayment() {
    if (!stripe || !cardElement) {
        throw new Error("Stripe no está disponible.");
    }

    // 1. Crear intención de pago en el backend
    const intent = await api.createPaymentIntent(Number(bookingId));
    // Esperamos algo tipo: { client_secret, payment_intent_id, amount, currency }

    const clientSecret = intent.client_secret;
    const paymentIntentId = intent.payment_intent_id;

    if (!clientSecret) {
        throw new Error("No se pudo crear la intención de pago.");
    }

    // 2. Confirmar el pago con Stripe
    const result = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
            card: cardElement,
        }
    });

    if (result.error) {
        // Error al confirmar el pago
        console.error(result.error);
        throw new Error(result.error.message || "Error al procesar el pago.");
    }

    if (result.paymentIntent && result.paymentIntent.status === "succeeded") {
        // 3. Notificar al backend que el pago se completó
        await api.confirmPayment(
            Number(bookingId),
            paymentIntentId || result.paymentIntent.id
        );

        showToast("Pago realizado con éxito 🎉", "success");
    } else {
        throw new Error("El pago no se completó correctamente.");
    }
}
