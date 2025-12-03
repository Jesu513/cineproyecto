// ================================
// frontend/utils/helpers.js
// Funciones auxiliares para el frontend
// ================================

export function formatCurrency(value, currency = 'USD') {
  if (value == null) return '-';
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency
  }).format(value);
}

export function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-PE', {
    year: 'numeric',
    month: 'short',
    day: '2-digit'
  });
}

export function formatTime(timeStr) {
  if (!timeStr) return '-';
  const d = new Date(`1970-01-01T${timeStr}`);
  return d.toLocaleTimeString('es-PE', {
    hour: '2-digit',
    minute: '2-digit'
  });
}

export function formatDateTime(dateTimeStr) {
  if (!dateTimeStr) return '-';
  const d = new Date(dateTimeStr);
  return d.toLocaleString('es-PE', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Toast simple (puedes conectar con tu componente Notification.jsx luego)
export function showToast(message, type = 'info') {
  alert(`${type.toUpperCase()}: ${message}`);
}

// Obtener parámetro de la URL (ej: ?movie_id=10)
export function getQueryParam(key) {
  const params = new URLSearchParams(window.location.search);
  return params.get(key);
}
window.helpers = {
  formatDate,
  formatCurrency,
  formatTime,
  formatDateTime,
  showToast,
  getQueryParam
};
