import axios from 'axios'
import { API_URL } from '../config'

// Create axios instance with default config
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Add request interceptor to add auth token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Add response interceptor to handle errors
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

// Public booking service
export const publicBookingService = {
  getProviderDetails: (username) => api.get(`/public/provider/${username}`),
  getAvailableSlots: (username, startDate) => {
    // Calculate end date as start date + 7 days
    const endDate = new Date(startDate)
    endDate.setDate(endDate.getDate() + 7)
    
    return api.get(`/public/availability`, {
      params: {
        username,
        start_date: startDate,
        end_date: endDate.toISOString().split('T')[0]
      }
    })
  },
  createBooking: (username, data) => 
    api.post('/public/booking', {
      provider_username: username,
      slot_id: data.slot.id,
      customer: {
        name: data.name,
        email: data.email
      },
      notes: data.notes
    })
}

export default api