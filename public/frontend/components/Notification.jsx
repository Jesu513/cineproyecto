// ============================================
// Notification.jsx — Toast de notificaciones
// ============================================

import React, { useEffect } from "react";

export default function Notification({
  message = "",
  type = "info", // info | success | error
  duration = 3000,
  onClose,
}) {
  useEffect(() => {
    if (!onClose) return;
    const timer = setTimeout(onClose, duration);
    return () => clearTimeout(timer);
  }, [duration, onClose]);

  const colors = {
    info: "bg-blue-600",
    success: "bg-emerald-600",
    error: "bg-red-600",
  };

  return (
    <div className="fixed bottom-5 right-5 z-50">
      <div
        className={`px-4 py-3 rounded-lg shadow-lg text-white text-sm ${colors[type]}`}
      >
        {message}
      </div>
    </div>
  );
}
