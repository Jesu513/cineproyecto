// ============================================
// MovieCard.jsx — versión UMD compatible
// Adaptado desde tu código original
// ============================================

// IMPORTS REEMPLAZADOS POR ACCESOS GLOBALES
// helpers.js debe estar cargado ANTES de este archivo
const { formatDate } = window.helpers;
const { useState } = React;

function MovieCard({ movie, onClick }) {
  const {
    title,
    poster_url,
    rating,
    genres,
    release_date,
    duration,
  } = movie;

  const genreText = Array.isArray(genres)
    ? genres.join(", ")
    : (genres || "");

  return (
    <div
      className="bg-slate-900 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl hover:-translate-y-1 transition cursor-pointer flex flex-col"
      onClick={onClick}
    >
      {/* Poster */}
      <div className="relative w-full aspect-[2/3] bg-slate-800">
        {poster_url ? (
          <img
            src={poster_url}
            alt={title}
            className="w-full h-full object-cover"
            loading="lazy"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-slate-400 text-sm">
            Sin poster
          </div>
        )}

        {rating != null && (
          <div className="absolute top-2 right-2 bg-yellow-400 text-slate-900 text-xs font-bold px-2 py-1 rounded-full">
            ⭐ {Number(rating).toFixed(1)}
          </div>
        )}
      </div>

      {/* Texto */}
      <div className="p-3 flex flex-col gap-2 flex-1">
        <h3 className="text-sm font-semibold text-white line-clamp-2">
          {title}
        </h3>

        {genreText && (
          <p className="text-[11px] text-slate-400 line-clamp-1">
            {genreText}
          </p>
        )}

        <div className="mt-auto flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-800">
          <span className="flex items-center gap-1">
            <i className="fa-regular fa-calendar text-[10px]" />
            {release_date ? formatDate(release_date) : "Próximamente"}
          </span>

          {duration && (
            <span className="flex items-center gap-1">
              <i className="fa-regular fa-clock text-[10px]" />
              {duration} min
            </span>
          )}
        </div>
      </div>
    </div>
  );
}

// Registrar en el ámbito global para usar desde HTML
window.MovieCard = MovieCard;
