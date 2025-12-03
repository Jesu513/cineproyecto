// ============================================
// Navbar.jsx — versión UMD compatible
// SIN imports — trabaja con window.auth
// ============================================

const { useState, useEffect } = React;

function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [user, setUser] = useState(null);

  useEffect(() => {
    if (window.auth) {
      setUser(window.auth.getUser());
    }
  }, []);

  const handleLogout = () => {
    if (window.auth) {
      window.auth.logout();
    }
    window.location.href = "/SisCine/public/frontend/pages/login.html";
  };

  return (
    <header className="w-full bg-slate-900/80 backdrop-blur-md border-b border-slate-800 fixed top-0 left-0 z-50">
      <nav className="max-w-7xl mx-auto flex justify-between items-center px-4 py-3">
        {/* LOGO */}
        <a
          href="/SisCine/public/frontend/pages/index.html"
          className="text-2xl font-bold text-emerald-400"
        >
          🎬 SisCine
        </a>

        {/* Desktop Menu */}
        <div className="hidden md:flex gap-6 items-center">
          <a href="/SisCine/public/frontend/pages/movies.html" className="text-slate-300 hover:text-white">Cartelera</a>

          <a href="/SisCine/public/frontend/pages/recommendations.html" className="text-slate-300 hover:text-white">Recomendaciones</a>

          {user ? (
            <>
              <a href="/SisCine/public/frontend/pages/my-bookings.html" className="text-slate-300 hover:text-white">Mis Reservas</a>

              <a href="/SisCine/public/frontend/pages/profile.html" className="flex items-center gap-2 text-slate-300 hover:text-white">
                <i className="fa-solid fa-user-circle text-xl"></i>
                {user.name}
              </a>

              <button
                onClick={handleLogout}
                className="px-4 py-1.5 bg-red-600 hover:bg-red-700 rounded-lg text-white text-sm"
              >
                Cerrar sesión
              </button>
            </>
          ) : (
            <>
              <a href="/SisCine/public/frontend/pages/login.html" className="text-slate-300 hover:text-white">Iniciar sesión</a>

              <a href="/SisCine/public/frontend/pages/register.html" className="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-white text-sm">
                Registrarse
              </a>
            </>
          )}
        </div>

        {/* Mobile Button */}
        <button className="md:hidden text-slate-300 text-2xl" onClick={() => setMenuOpen(!menuOpen)}>
          <i className="fa-solid fa-bars"></i>
        </button>
      </nav>

      {/* Mobile Menu */}
      {menuOpen && (
        <div className="md:hidden bg-slate-900 px-4 py-4 border-t border-slate-800">
          <a href="/SisCine/public/frontend/pages/movies.html" className="block py-2 text-slate-300 hover:text-white">Cartelera</a>

          <a href="/SisCine/public/frontend/pages/recommendations.html" className="block py-2 text-slate-300 hover:text-white">Recomendaciones</a>

          {user ? (
            <>
              <a href="/SisCine/public/frontend/pages/my-bookings.html" className="block py-2 text-slate-300">Mis Reservas</a>

              <a href="/SisCine/public/frontend/pages/profile.html" className="block py-2 text-slate-300">Perfil</a>

              <button
                className="w-full mt-3 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-white"
                onClick={handleLogout}
              >
                Cerrar sesión
              </button>
            </>
          ) : (
            <>
              <a href="/SisCine/public/frontend/pages/login.html" className="block py-2 text-slate-300">Iniciar sesión</a>

              <a href="/SisCine/public/frontend/pages/register.html" className="block py-2 text-emerald-400 font-semibold">
                Registrarse
              </a>
            </>
          )}
        </div>
      )}
    </header>
  );
}

// Hacerlo accesible globalmente
window.Navbar = Navbar;
