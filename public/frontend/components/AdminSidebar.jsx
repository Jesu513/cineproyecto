// ============================================
// AdminSidebar.jsx
// Sidebar reutilizable para el panel Admin
// ============================================

import React from "react";

const menuItems = [
  { label: "Dashboard", icon: "fa-solid fa-chart-line", href: "dashboard.html" },
  { label: "Películas", icon: "fa-solid fa-film", href: "movies.html" },
  { label: "Horarios", icon: "fa-solid fa-clock", href: "showtimes.html" },
  { label: "Salas", icon: "fa-solid fa-door-open", href: "rooms.html" },
  { label: "Reservas", icon: "fa-solid fa-ticket", href: "bookings.html" },
  { label: "Usuarios", icon: "fa-solid fa-users", href: "users.html" },
  { label: "Promociones", icon: "fa-solid fa-tags", href: "promotions.html" },
  { label: "Reportes", icon: "fa-solid fa-chart-pie", href: "reports.html" },
];

export default function AdminSidebar({ active = "" }) {
  return (
    <aside className="w-64 bg-slate-900 h-screen border-r border-slate-800 flex flex-col">
      <div className="p-4 border-b border-slate-800">
        <h1 className="text-xl font-bold text-white">🎬 SisCine Admin</h1>
      </div>

      <nav className="flex-1 overflow-y-auto py-4">
        {menuItems.map((item) => (
          <a
            key={item.href}
            href={item.href}
            className={`flex items-center gap-3 px-4 py-3 text-sm hover:bg-slate-800 transition ${
              active === item.href
                ? "bg-slate-800 text-emerald-400"
                : "text-slate-300"
            }`}
          >
            <i className={`${item.icon} w-5`}></i>
            {item.label}
          </a>
        ))}
      </nav>
    </aside>
  );
}
