// ============================================
// frontend/components/SeatMap.jsx
// Mapa de asientos interactivo por filas
// ============================================

import React, { useMemo } from "react";

/**
 * Props:
 * - seats: array de asientos:
 *   {
 *     id, row_label, seat_number,
 *     status: 'available' | 'occupied' | 'unavailable' | 'reserved' | 'pending'
 *   }
 * - selectedSeatIds: array de IDs de asiento seleccionados
 * - onToggleSeat(seat): callback para seleccionar/deseleccionar
 */
export default function SeatMap({ seats = [], selectedSeatIds = [], onToggleSeat }) {
  const groupedSeats = useMemo(() => {
    const groups = {};
    seats.forEach((seat) => {
      const row = seat.row_label || "?";
      if (!groups[row]) groups[row] = [];
      groups[row].push(seat);
    });

    // Ordenar filas (A, B, C, ...)
    const sortedRows = Object.keys(groups).sort((a, b) =>
      a.localeCompare(b, "en", { sensitivity: "base" })
    );

    // Ordenar asientos por número
    sortedRows.forEach((row) => {
      groups[row].sort((a, b) => a.seat_number - b.seat_number);
    });

    return { groups, sortedRows };
  }, [seats]);

  const getSeatStyles = (seat) => {
    const isSelected = selectedSeatIds.includes(seat.id);
    const base =
      "w-8 h-8 flex items-center justify-center rounded-md text-[11px] font-semibold border transition";

    if (seat.status === "occupied") {
      return `${base} bg-red-500/80 border-red-400 text-white cursor-not-allowed`;
    }

    if (seat.status === "unavailable") {
      return `${base} bg-slate-700 border-slate-600 text-slate-400 cursor-not-allowed`;
    }

    if (seat.status === "reserved" || seat.status === "pending") {
      return `${base} bg-yellow-400 border-yellow-300 text-slate-900 cursor-not-allowed`;
    }

    if (isSelected) {
      return `${base} bg-blue-500 border-blue-400 text-white shadow`;
    }

    // available
    return `${base} bg-emerald-500/90 border-emerald-400 text-white hover:bg-emerald-400`;
  };

  const isSeatClickable = (seat) =>
    seat.status === "available" || selectedSeatIds.includes(seat.id);

  return (
    <div className="w-full flex flex-col gap-4">
      {/* Pantalla */}
      <div className="w-full flex justify-center">
        <div className="w-2/3 max-w-md h-2 bg-slate-300 rounded-full mb-4" />
      </div>
      <p className="text-center text-xs text-slate-400 mb-1">
        Pantalla
      </p>

      {/* Mapa */}
      <div className="flex flex-col gap-2 items-center">
        {groupedSeats.sortedRows.map((rowLabel) => (
          <div
            key={rowLabel}
            className="flex items-center gap-2 justify-center"
          >
            <span className="w-5 text-xs text-slate-400 text-right mr-1">
              {rowLabel}
            </span>

            <div className="flex gap-1">
              {groupedSeats.groups[rowLabel].map((seat) => (
                <button
                  key={seat.id}
                  type="button"
                  className={getSeatStyles(seat)}
                  disabled={!isSeatClickable(seat)}
                  onClick={() => {
                    if (!isSeatClickable(seat)) return;
                    onToggleSeat && onToggleSeat(seat);
                  }}
                >
                  {seat.seat_number}
                </button>
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* Leyenda */}
      <div className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] text-slate-300">
        <div className="flex items-center gap-2">
          <span className="w-4 h-4 rounded bg-emerald-500" />
          <span>Disponible</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="w-4 h-4 rounded bg-blue-500" />
          <span>Seleccionado</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="w-4 h-4 rounded bg-red-500" />
          <span>Ocupado</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="w-4 h-4 rounded bg-yellow-400" />
          <span>Reservado / pendiente</span>
        </div>
      </div>
    </div>
  );
}
