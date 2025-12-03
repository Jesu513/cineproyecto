<?php
// ============================================
// config/tmdb.php
// Configuración de The Movie Database (TMDb) API
// Documentación: https://developers.themoviedb.org/3
// ============================================

return [
    // API Key
    // Obtener en: https://www.themoviedb.org/settings/api
    'api_key' => $_ENV['TMDB_API_KEY'] ?? '',
    
    // URL base de la API
    'api_url' => $_ENV['TMDB_API_URL'] ?? 'https://api.themoviedb.org/3',
    
    // Versión de la API
    'api_version' => 3,
    
    // URL base para imágenes
    'image_base_url' => 'https://image.tmdb.org/t/p/',
    
    // Tamaños de imágenes disponibles
    'image_sizes' => [
        'poster' => [
            'w92',
            'w154',
            'w185',
            'w342',
            'w500',  // Recomendado para cartelera
            'w780',
            'original',
        ],
        'backdrop' => [
            'w300',
            'w780',
            'w1280',
            'original', // Recomendado para banners
        ],
        'profile' => [
            'w45',
            'w185',
            'h632',
            'original',
        ],
    ],
    
    // Tamaños preferidos
    'preferred_sizes' => [
        'poster' => 'w500',
        'backdrop' => 'original',
        'profile' => 'w185',
    ],
    
    // Idioma por defecto
    'language' => $_ENV['TMDB_LANGUAGE'] ?? 'es-ES',
    
    // Idiomas alternativos (fallback)
    'fallback_languages' => ['es-MX', 'en-US'],
    
    // Región para resultados de búsqueda
    'region' => 'PE', // Perú
    
    // Endpoints comunes
    'endpoints' => [
        'search_movie' => '/search/movie',
        'movie_details' => '/movie/{movie_id}',
        'movie_credits' => '/movie/{movie_id}/credits',
        'movie_videos' => '/movie/{movie_id}/videos',
        'movie_images' => '/movie/{movie_id}/images',
        'popular' => '/movie/popular',
        'now_playing' => '/movie/now_playing',
        'upcoming' => '/movie/upcoming',
        'top_rated' => '/movie/top_rated',
        'genres' => '/genre/movie/list',
    ],
    
    // Configuración de caché
    'cache' => [
        'enabled' => true,
        'ttl' => 86400, // 24 horas en segundos
        'prefix' => 'tmdb:',
        'keys' => [
            'movie_details' => 'movie:{id}',
            'search' => 'search:{query}',
            'genres' => 'genres',
            'popular' => 'popular',
        ],
    ],
    
    // Límites de rate limiting
    'rate_limit' => [
        'requests_per_second' => 4,
        'max_retries' => 3,
        'retry_delay' => 1000, // milisegundos
    ],
    
    // Timeout de conexión
    'timeout' => 10,
    
    // Campos a importar al sistema
    'import_fields' => [
        'title',
        'original_title',
        'overview' => 'synopsis',
        'runtime' => 'duration',
        'release_date',
        'poster_path' => 'poster_url',
        'backdrop_path' => 'backdrop_url',
        'vote_average' => 'rating',
        'original_language' => 'language',
        'genres',
        'adult',
    ],
    
    // Filtros de contenido
    'filters' => [
        // Incluir contenido para adultos
        'include_adult' => false,
        
        // Calificación mínima (0-10)
        'min_vote_average' => 0,
        
        // Número mínimo de votos
        'min_vote_count' => 10,
    ],
    
    // Géneros disponibles (se actualizan desde la API)
    'genre_mapping' => [
        28 => 'Acción',
        12 => 'Aventura',
        16 => 'Animación',
        35 => 'Comedia',
        80 => 'Crimen',
        99 => 'Documental',
        18 => 'Drama',
        10751 => 'Familia',
        14 => 'Fantasía',
        36 => 'Historia',
        27 => 'Terror',
        10402 => 'Música',
        9648 => 'Misterio',
        10749 => 'Romance',
        878 => 'Ciencia ficción',
        10770 => 'Película de TV',
        53 => 'Suspenso',
        10752 => 'Bélica',
        37 => 'Western',
    ],
    
    // Configuración de búsqueda
    'search' => [
        'default_page' => 1,
        'results_per_page' => 20,
        'max_results' => 100,
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'log_requests' => false,
        'log_responses' => false,
    ],
];