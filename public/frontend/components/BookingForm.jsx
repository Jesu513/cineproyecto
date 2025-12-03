// ============================================
// frontend/components/BookingForm.jsx
// Resumen de la reserva + cupón + continuar
// ============================================

import React, { useState } from "react";
import { formatCurrency, formatDate, formatTime } from "../utils/helpers.js";

/**
 * Props:
 * - movie: { title, poster_url, classification }
 * - showtime: { show_date, show_time, room_name }
 * - selectedSeats: array de asientos { row_label, seat_number }
 * - pricing: {
 *     total_amount,
 *     discount_amount,
 *     final_amount
 *   }
 * - onApplyCoupon(code): Promise<{discount, final_amount}> (o manejas error)
 * - onConfirm(): callback al hacer click en "Confirmar reserva"
 */
export default function BookingForm({
  movie,
  showtime,
  selectedSeats = [],
  pricing,
  onApplyCoupon,
  onConfirm,
}) {
  const [couponCode, setCouponCode] = useState("");
  const [isApplying, setIsApplying] = useState(false);
  const [applyError, setApplyError] = useState(null);

  const handleApplyCoupon = async (e) => {
    e.preventDefault();
    if (!couponCode || !onApplyCoupon) return;

    setIsApplying(true);
    setApplyError(null);
    try {
      await onApplyCoupon(couponCode.trim());
    } catch (err) {
      setApplyError(err.message || "No se pudo aplicar el cupón");
    } finally {
      setIsApplying(false);
    }
  };

  const seatsText =
    selectedSeats.length > 0
      ? selectedSeats
          .map((s) => `${s.row_label}${s.seat_number}`)
          .join(", ")
      : "Sin asientos seleccionados";

  return (
    <div className="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-xl flex flex-col gap-4">
      {/* Película + función */}
      <div className="flex gap-3">
        {movie?.poster_url && (
          <img
            src={movie.poster_url}
            alt={movie.title}
            className="w-20 h-28 object-cover rounded-lg"
          />
        )}
        <div className="flex-1 flex flex-col gap-1">
          <h2 className="text-base font-semibold text-white">
            {movie?.title || "Película"}
          </h2>
          {movie?.classification && (
            <span className="inline-flex items-center px-2 py-0.5 text-[10px] rounded-full bg-slate-800 text-slate-200 w-fit">
              {movie.classification}
            </span>
          )}
          <p className="text-xs text-slate-300 mt-1">
            {showtime && (
              <>
                <span className="block">
                  {formatDate(showtime.show_date)} —{" "}
                  {formatTime(showtime.show_time)}
                </span>
                {showtime.room_name && (
                  <span className="block text-slate-400">
                    Sala: {showtime.room_name}
                  </span>
                )}
              </>
            )}
          </p>
        </div>
      </div>

      {/* Asientos */}
      <div className="bg-slate-800/60 rounded-xl px-3 py-2 text-xs text-slate-100">
        <p className="font-semibold mb-1">Asientos seleccionados</p>
        <p className="text-slate-200">{seatsText}</p>
        <p className="text-slate-400 mt-1">
          Total asientos: {selectedSeats.length}
        </p>
      </div>

      {/* Cupón */}
      {onApplyCoupon && (
        <form
          className="flex flex-col sm:flex-row gap-2 items-stretch sm:items-end"
          onSubmit={handleApplyCoupon}
        >
          <div className="flex-1">
            <label className="block text-[11px] text-slate-300 mb-1">
              Cupón de descuento
            </label>
            <input
              type="text"
              className="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
              placeholder="PROMO2X1, DESCUENTO10..."
              value={couponCode}
              onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
            />
            {applyError && (
              <p className="mt-1 text-[11px] text-red-400">{applyError}</p>
            )}
          </div>
          <button
            type="submit"
            disabled={isApplying || !couponCode.trim()}
            className="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-xs font-semibold text-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isApplying ? "Aplicando..." : "Aplicar cupón"}
          </button>
        </form>
      )}

      {/* Totales */}
      <div className="bg-slate-800/60 rounded-xl px-3 py-3 text-xs text-slate-100">
        <div className="flex justify-between mb-1">
          <span>Subtotal</span>
          <span>{formatCurrency(pricing?.total_amount ?? 0)}</span>
        </div>
        <div className="flex justify-between mb-1">
          <span>Descuento</span>
          <span className="text-emerald-400">
            - {formatCurrency(pricing?.discount_amount ?? 0)}
          </span>
        </div>
        <div className="border-t border-slate-700 mt-2 pt-2 flex justify-between text-sm font-semibold">
          <span>Total a pagar</span>
          <span className="text-emerald-400">
            {formatCurrency(pricing?.final_amount ?? 0)}
          </span>
        </div>
      </div>

      <button
        type="button"
        onClick={onConfirm}
        disabled={!selectedSeats.length}
        className="mt-1 w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-900 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Confirmar reserva y continuar al pago
      </button>
    </div>
  );
}
