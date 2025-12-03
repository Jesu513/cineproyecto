// ============================================
// frontend/components/PaymentForm.jsx
// Formulario de pago con Stripe + API propia
// ============================================

import React, { useEffect, useRef, useState } from "react";
import { api } from "../utils/api.js";
import { formatCurrency } from "../utils/helpers.js";

/**
 * Props:
 * - bookingId: ID de la reserva
 * - amount: monto final a pagar (para mostrar solo)
 * - stripePublicKey: public key de Stripe (pk_...)
 * - onSuccess(payment): callback al completar correctamente
 * - onError(errorMsg): callback en caso de error
 */
export default function PaymentForm({
  bookingId,
  amount,
  stripePublicKey,
  onSuccess,
  onError,
}) {
  const [isLoading, setIsLoading] = useState(false);
  const [clientSecret, setClientSecret] = useState(null);
  const [errorMsg, setErrorMsg] = useState(null);

  const cardElementRef = useRef(null);
  const stripeRef = useRef(null);
  const elementsRef = useRef(null);
  const cardRef = useRef(null);

  // 1. Crear intención de pago al montar
  useEffect(() => {
    let mounted = true;

    async function initPayment() {
      try {
        setIsLoading(true);
        const res = await api.createPaymentIntent(bookingId);
        if (!mounted) return;

        setClientSecret(res.clientSecret);

        if (!window.Stripe || !stripePublicKey) {
          throw new Error("Stripe no está configurado correctamente.");
        }

        const stripe = window.Stripe(stripePublicKey);
        const elements = stripe.elements();

        const card = elements.create("card", {
          hidePostalCode: true,
        });

        card.mount(cardElementRef.current);

        stripeRef.current = stripe;
        elementsRef.current = elements;
        cardRef.current = card;
      } catch (err) {
        console.error(err);
        setErrorMsg(err.message || "No se pudo inicializar el pago.");
        onError && onError(err.message || "No se pudo inicializar el pago.");
      } finally {
        if (mounted) setIsLoading(false);
      }
    }

    initPayment();

    return () => {
      mounted = false;
      if (cardRef.current) {
        cardRef.current.unmount();
      }
    };
  }, [bookingId, stripePublicKey, onError]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!stripeRef.current || !cardRef.current || !clientSecret) return;

    setIsLoading(true);
    setErrorMsg(null);

    try {
      const stripe = stripeRef.current;
      const card = cardRef.current;

      const { error, paymentIntent } = await stripe.confirmCardPayment(
        clientSecret,
        {
          payment_method: {
            card,
          },
        }
      );

      if (error) {
        console.error(error);
        setErrorMsg(error.message || "Error al procesar el pago.");
        onError && onError(error.message || "Error al procesar el pago.");
        setIsLoading(false);
        return;
      }

      if (paymentIntent.status === "succeeded") {
        // Confirmar pago en tu backend
        const payment = await api.confirmPayment(
          bookingId,
          paymentIntent.id
        );

        onSuccess && onSuccess(payment);
      } else {
        setErrorMsg("El pago no se completó correctamente.");
        onError && onError("El pago no se completó correctamente.");
      }
    } catch (err) {
      console.error(err);
      setErrorMsg(err.message || "Error inesperado.");
      onError && onError(err.message || "Error inesperado.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form
      className="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-xl flex flex-col gap-4"
      onSubmit={handleSubmit}
    >
      <h2 className="text-base font-semibold text-white">
        Pago con tarjeta
      </h2>

      <div className="flex justify-between text-sm text-slate-200 mb-1">
        <span>Total a pagar:</span>
        <span className="font-semibold text-emerald-400">
          {formatCurrency(amount ?? 0)}
        </span>
      </div>

      <div className="text-xs text-slate-400">
        Introduce los datos de tu tarjeta de crédito o débito:
      </div>

      <div className="bg-slate-800/70 rounded-lg px-3 py-3">
        <div
          id="card-element"
          ref={cardElementRef}
          className="min-h-[40px]"
        />
      </div>

      {errorMsg && (
        <p className="text-xs text-red-400">
          {errorMsg}
        </p>
      )}

      <button
        type="submit"
        disabled={isLoading || !clientSecret}
        className="mt-1 w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isLoading ? "Procesando..." : "Pagar ahora"}
      </button>

      <p className="text-[10px] text-slate-500 text-center mt-1">
        Tus datos son procesados de forma segura por Stripe.
      </p>
    </form>
  );
}
