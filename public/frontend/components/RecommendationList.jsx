// ============================================
// RecommendationList.jsx
// Lista de recomendaciones: grilla horizontal estilo Netflix
// ============================================

import React from "react";
import MovieCard from "./MovieCard.jsx";

/**
 * Props:
 * - title: Título del bloque ("Recomendado para ti")
 * - movies: array de películas
 * - onSelect(movie): callback
 */
export default function RecommendationList({ title, movies = [], onSelect }) {
  return (
    <div className="w-full mb-6">
      <h2 className="text-lg font-semibold text-white mb-3">
        {title}
      </h2>

      {movies.length === 0 ? (
        <p className="text-slate-400 text-sm">Sin recomendaciones disponibles.</p>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          {movies.map((movie) => (
            <MovieCard
              key={movie.id}
              movie={movie}
              onClick={() => onSelect && onSelect(movie)}
            />
          ))}
        </div>
      )}
    </div>
  );
}
