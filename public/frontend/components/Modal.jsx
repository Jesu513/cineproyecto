// ============================================
// Modal.jsx — Modal reutilizable
// ============================================

import React from "react";

export default function Modal({ open, title, children, onClose }) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
      <div className="bg-slate-900 border border-slate-700 rounded-xl w-full max-w-md p-5 shadow-2xl relative">
        {/* Botón cerrar */}
        <button
          className="absolute right-4 top-4 text-slate-400 hover:text-white"
          onClick={onClose}
        >
          <i className="fa-solid fa-xmark text-xl"></i>
        </button>

        {/* Título */}
        {title && (
          <h2 className="text-lg font-semibold text-white mb-4">
            {title}
          </h2>
        )}

        {/* Contenido dinámico */}
        <div className="text-slate-200 text-sm">{children}</div>
      </div>
    </div>
  );
}
