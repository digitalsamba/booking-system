import axios from 'axios'
import { API_URL } from '../config'

// Derive the base public URL by removing /api if present
const PUBLIC_URL = API_URL.endsWith('/api') ? API_URL.slice(0, -4) : API_URL;

// Create axios instance with default config (for authenticated API calls)
const api = axios.create({
  baseURL: API_URL, // e.g., http://localhost:3002/api
  headers: {
    'Content-Type': 'application/json'
  }
})

// Create a separate axios instance for public calls (no /api prefix)
const publicApi = axios.create({
  baseURL: PUBLIC_URL, // e.g., http://localhost:3002
  headers: {
    'Content-Type': 'application/json'
  }
});

// Add request interceptor to add auth token (ONLY for the authenticated instance)
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Add response interceptor to handle errors (Apply to BOTH instances? Or just auth?)
// Let's apply 401 handling only to the authenticated instance for now.
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Public response interceptor (optional - could add generic error logging)
publicApi.interceptors.response.use(
  response => response,
  error => {
    // Minimal error handling for public routes if needed
    console.error('Public API Error:', error.response?.status, error.message);
    return Promise.reject(error);
  }
);

// Auth service
export const authService = {
  login: (credentials) => api.post('/auth/login', credentials),
  register: (userData) => api.post('/auth/register', userData),
  logout: () => {
    localStorage.removeItem('token')
    window.location.href = '/login'
  }
}

// User service
export const userService = {
  getProfile: () => api.get('/user/profile'),
  updateProfile: (data) => api.put('/user/profile', data)
}

// Availability service
export const availabilityService = {
  getSlots: () => api.get('/availability'),
  addSlots: (slots) => api.post('/availability/slots', slots),
  deleteSlot: (slotId) => api.delete(`/availability/slots/${slotId}`),
  deleteAllSlots: () => api.delete('/availability/slots')
}

// Booking service
export const bookingService = {
  getBookings: () => api.get('/bookings'),
  getBooking: (id) => api.get(`/bookings/${id}`),
  createBooking: (data) => api.post('/bookings', data),
  updateBooking: (id, data) => api.put(`/bookings/${id}`, data),
  deleteBooking: (id) => api.delete(`/bookings/${id}`)
}

// Public booking service (use publicApi instance)
export const publicBookingService = {
  getProviderDetails: (username) => publicApi.get(`/public/user/${username}`),
  getAvailableSlots: (username, startDate) => {
    // Calculate end date as start date + 7 days
    const endDate = new Date(startDate)
    endDate.setDate(endDate.getDate() + 7)
    
    return publicApi.get(`/public/availability`, {
      params: {
        username,
        start_date: startDate,
        end_date: endDate.toISOString().split('T')[0]
      }
    })
  },
  createBooking: (username, data) => {
    if (!data.slot) {
      console.error('Missing slot data!', data);
      return Promise.reject(new Error('Booking requires a valid slot selection'));
    }
    
    // Use original_id if available, otherwise fall back to id
    const slotId = data.slot.original_id || data.slot.id;
    
    if (!slotId) {
      console.error('Missing slot ID!', data.slot);
      return Promise.reject(new Error('Booking requires a valid slot ID'));
    }
    
    // Ensure data is properly formatted
    const bookingData = {
      provider_username: username,
      slot_id: slotId,
      customer: {
        name: data.name,
        email: data.email
      },
      notes: data.notes || ''
    };
    
    console.log('Creating booking with formatted data:', bookingData);
    
    return publicApi.post('/public/booking', bookingData);
  },

  /**
   * Fetches the public branding settings for a specific provider.
   * @param {string} userId The provider's user ID.
   * @returns {Promise<object>} The branding settings data.
   */
  getBrandingSettings(userId) {
    if (!userId) {
      console.error('getBrandingSettings requires a userId');
      return Promise.reject(new Error('User ID is required to fetch branding settings'));
    }
    return publicApi.get(`/public/branding/${userId}`);
  }
}

// Export the authenticated instance as default if needed elsewhere, or export both
export { api, publicApi } // Export both if needed
export default api // Keep default export as the authenticated one