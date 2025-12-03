// ================================
// frontend/utils/api.js
// Cliente API centralizado
// ================================

const API_BASE_URL = `${window.location.origin}/SisCine/api`; 
// Ajusta /SisCine si tu proyecto está en otra carpeta

function getAuthToken() {
  return localStorage.getItem('auth_token') || null;
}

async function request(endpoint, options = {}) {
  const token = getAuthToken();

  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {})
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const config = {
    method: options.method || 'GET',
    headers,
  };

  if (options.body) {
    config.body = JSON.stringify(options.body);
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, config);

  let data;
  try {
    data = await response.json();
  } catch (e) {
    data = null;
  }

  if (!response.ok) {
    const message = data?.message || `Error ${response.status}`;
    throw new Error(message);
  }

  return data;
}

export const api = {
  // =========== AUTH ===========
  login: (email, password) =>
    request('/auth/login', {
      method: 'POST',
      body: { email, password }
    }),

  register: (payload) =>
    request('/auth/register', {
      method: 'POST',
      body: payload
    }),

  me: () => request('/auth/me'),

  logout: () =>
    request('/auth/logout', {
      method: 'POST'
    }),

  // =========== MOVIES ===========
  getMovies: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return request(`/movies${query ? `?${query}` : ''}`);
  },

  getMovie: (id) => request(`/movies/${id}`),

  getMovieShowtimes: (movieId, date = null) => {
    const query = new URLSearchParams();
    if (date) query.append('date', date);
    return request(`/movies/${movieId}/showtimes?${query.toString()}`);
  },

  // =========== SHOWTIMES / SEATS ===========
  getSeatsByShowtime: (showtimeId) =>
    request(`/showtimes/${showtimeId}/seats`),

  // =========== BOOKINGS ===========
  reserveSeats: (showtimeId, seatIds) =>
    request('/bookings/reserve', {
      method: 'POST',
      body: { showtime_id: showtimeId, seat_ids: seatIds }
    }),

  confirmBooking: (bookingId) =>
    request(`/bookings/${bookingId}/confirm`, {
      method: 'POST'
    }),

  cancelBooking: (bookingId) =>
    request(`/bookings/${bookingId}/cancel`, {
      method: 'DELETE'
    }),

  getMyBookings: () => request('/bookings/my-bookings'),

  // =========== PAYMENTS ===========
  createPaymentIntent: (bookingId) =>
    request('/payments/create-intent', {
      method: 'POST',
      body: { booking_id: bookingId }
    }),

  confirmPayment: (bookingId, paymentIntentId) =>
    request(`/payments/${bookingId}/confirm`, {
      method: 'POST',
      body: { payment_intent_id: paymentIntentId }
    }),

  // =========== PROMOTIONS ===========
  validateCoupon: (code, bookingId) =>
    request('/promotions/validate', {
      method: 'POST',
      body: { code, booking_id: bookingId }
    }),

  getActivePromotions: () => request('/promotions/active'),

  // =========== RATINGS & RECOMMENDATIONS ===========
  rateMovie: (movieId, rating, review = '') =>
    request('/ratings', {
      method: 'POST',
      body: { movie_id: movieId, rating, review }
    }),

  getRecommendations: () => request('/recommendations'),

  // =========== PROFILE ===========
  getProfile: () => request('/users/me'),

  updateProfile: (payload) =>
    request('/users/me', {
      method: 'PUT',
      body: payload
    }),

  // =========== ADMIN ===========
  adminDashboard: () => request('/admin/dashboard'),

  adminGetMovies: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/movies${query ? `?${query}` : ''}`);
  },

  adminCreateMovie: (payload) =>
    request('/admin/movies', {
      method: 'POST',
      body: payload
    }),

  adminUpdateMovie: (id, payload) =>
    request(`/admin/movies/${id}`, {
      method: 'PUT',
      body: payload
    }),

  adminToggleMovieStatus: (id) =>
    request(`/admin/movies/${id}/status`, {
      method: 'PUT'
    }),

  // etc. (showtimes, rooms, users, reports...)
};
