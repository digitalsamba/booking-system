import axios from 'axios'

// Create axios instance
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Add request interceptor for JWT token
api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// Add response interceptor for error handling
api.interceptors.response.use(
  response => response,
  error => {
    // Handle 401 Unauthorized errors (token expired, etc.)
    if (error.response && error.response.status === 401) {
      // Clear local storage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      
      // Redirect to login if needed
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// Auth services
export const authService = {
  login: (credentials) => api.post('/auth/login', credentials),
  register: (userData) => api.post('/auth/register', userData),
  getProfile: () => api.get('/auth/profile'),
  updateProfile: (userData) => api.post('/auth/profile', userData)
}

// Booking services
export const bookingService = {
  getBookings: () => api.get('/bookings'),
  getBooking: (id) => api.get(`/bookings/${id}`),
  createBooking: (bookingData) => api.post('/bookings', bookingData),
  updateBooking: (id, bookingData) => api.put(`/bookings/${id}`, bookingData),
  deleteBooking: (id) => api.delete(`/bookings/${id}`),
  getMeetingLinks: (id) => api.get(`/booking/${id}/meeting-links`)
}

// Availability services
export const availabilityService = {
  getAvailability: () => api.get('/availability'),
  createAvailability: (availabilityData) => api.post('/availability', availabilityData),
  updateAvailability: (id, availabilityData) => api.put(`/availability/${id}`, availabilityData),
  deleteAvailability: (id) => api.delete(`/availability/${id}`)
}

// Service listing services
export const serviceListingService = {
  getServices: () => api.get('/services'),
  getService: (id) => api.get(`/services/${id}`),
  createService: (serviceData) => api.post('/services', serviceData),
  updateService: (id, serviceData) => api.put(`/services/${id}`, serviceData),
  deleteService: (id) => api.delete(`/services/${id}`)
}

export default api