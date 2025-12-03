// ===========================================
// assets/js/movies.js
// Lógica para la Cartelera de Películas
// ===========================================

import { api } from "../../frontend/utils/api.js";
import { showToast } from "../../frontend/utils/helpers.js";

document.addEventListener("DOMContentLoaded", () => {
    loadMovies();

    document.getElementById("filterForm")?.addEventListener("submit", (e) => {
        e.preventDefault();
        loadMovies();
    });
});

/**
 * Cargar películas con filtros
 */
async function loadMovies() {
    const listContainer = document.getElementById("moviesList");
    const loader = document.getElementById("loader");
    const emptyMessage = document.getElementById("noMoviesMessage");

    if (!listContainer) return;

    listContainer.innerHTML = "";
    emptyMessage.classList.add("hidden");
    loader.classList.remove("hidden");

    // Leer filtros del formulario
    const params = getFilters();

    try {
        const movies = await api.getMovies(params);

        loader.classList.add("hidden");

        if (!movies || movies.length === 0) {
            emptyMessage.classList.remove("hidden");
            return;
        }

        movies.forEach(movie => {
            listContainer.appendChild(renderMovieCard(movie));
        });

    } catch (error) {
        loader.classList.add("hidden");
        showToast(error.message, "error");
    }
}

/**
 * Extraer filtros desde el formulario
 */
function getFilters() {
    const form = document.getElementById("filterForm");
    if (!form) return {};

    return {
        search: form.search?.value || "",
        genre: form.genre?.value || "",
        rating_min: form.rating?.value || "",
        date: form.date?.value || ""
    };
}

/**
 * Renderizar la tarjeta de película
 */
function renderMovieCard(movie) {
    const card = document.createElement("div");

    card.className =
        "bg-gray-800 rounded-md shadow hover:shadow-lg transition p-3 cursor-pointer";

    card.innerHTML = `
        <img 
            src="${movie.poster_url || "/SisCine/public/assets/images/placeholder.jpg"}" 
            alt="${movie.title}" 
            class="w-full h-72 object-cover rounded"
        >

        <div class="mt-3">
            <h3 class="text-lg font-semibold text-white">${movie.title}</h3>

            <p class="text-sm text-gray-300">
                ⭐ ${movie.rating ?? "N/A"} &nbsp; • &nbsp; ${movie.duration || "?"} min
            </p>

            <button 
                class="mt-3 w-full bg-yellow-500 text-black py-2 rounded hover:bg-yellow-400"
                data-id="${movie.id}"
            >
                Ver detalles
            </button>
        </div>
    `;

    // Evento: ir al detalle
    card.querySelector("button").addEventListener("click", () => {
        window.location.href =
            `/SisCine/public/frontend/pages/movie-detail.html?id=${movie.id}`;
    });

    return card;
}
