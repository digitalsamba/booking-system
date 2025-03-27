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
      // Add multiple header variants to handle case sensitivity issues
      config.headers['Authorization'] = `Bearer ${token}`
      config.headers['authorization'] = `Bearer ${token}`  // Lowercase variant
      config.headers['X-Auth-Token'] = token  // Alternative format some backends use
      console.log(`Adding multiple Authorization header variants with token: ${token.substring(0, 10)}...`)
    } else {
      console.log('No token found in localStorage!')
    }
    console.log(`API Request: ${config.method.toUpperCase()} ${config.url}`, config.data || '(no data)')
    return config
  },
  error => {
    console.error('API Request Error:', error)
    return Promise.reject(error)
  }
)

// Add response interceptor for error handling
api.interceptors.response.use(
  response => {
    console.log(`API Response: ${response.status}`, response.data || '(no data)')
    return response
  },
  error => {
    console.error('API Error Response:', error.response?.status, error.response?.data || error.message)
    
    // Handle 401 Unauthorized errors (token expired, etc.)
    if (error.response && error.response.status === 401) {
      console.log('401 Unauthorized error detected')
      console.log('Skipping redirect for all 401 errors during debugging')
      
      // DEBUGGING MODE: Don't redirect for ANY 401 errors temporarily
      // Just log extensive information about the error
      console.log('Error details:', {
        url: error.config?.url,
        method: error.config?.method,
        headers: error.config?.headers,
        data: error.config?.data,
        responseData: error.response?.data
      })
      
      return Promise.reject(error)
      
      /* DISABLED FOR DEBUGGING
      // Don't auto-redirect when updating profile
      const isProfileUpdate = error.config && 
                             error.config.url && 
                             error.config.url.includes('/auth/profile') && 
                             error.config.method === 'post'
      
      if (isProfileUpdate) {
        console.log('Profile update failed with 401, not redirecting')
        return Promise.reject(error)
      }
      
      // Clear local storage for other 401 errors
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      
      // Redirect to login if needed
      window.location.href = '/login'
      */
    }
    return Promise.reject(error)
  }
)

// Auth services
export const authService = {
  login: (credentials) => api.post('/auth/login', credentials),
  register: (userData) => api.post('/auth/register', userData),
  getProfile: () => {
    console.log('Calling getProfile API');
    // Include token directly in headers to ensure it's not expired/missing
    const token = localStorage.getItem('token');
    if (token) {
      console.log('Token for getProfile:', token.substring(0, 10) + '...');
    } else {
      console.log('No token for getProfile!');
    }
    
    // Set headers explicitly with multiple variants
    const headers = token ? {
      'Authorization': `Bearer ${token}`,
      'authorization': `Bearer ${token}`,
      'X-Auth-Token': token
    } : {};
    
    return api.get('/auth/profile', { headers });
  },
  updateProfile: (userData) => {
    console.log('Calling updateProfile API with data:', userData)
    
    // Include token directly in headers to ensure it's not expired/missing
    const token = localStorage.getItem('token')
    
    // Debug token validity
    if (token) {
      try {
        // Extract payload to check expiration
        const base64Url = token.split('.')[1]
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/')
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => {
          return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
        }).join(''))
        
        const payload = JSON.parse(jsonPayload)
        const now = Math.floor(Date.now() / 1000)
        
        if (payload.exp && payload.exp < now) {
          console.error('Token is expired! Exp:', payload.exp, 'Now:', now)
        } else {
          console.log('Token appears valid. Exp:', payload.exp, 'Now:', now)
        }
      } catch (e) {
        console.error('Error parsing token:', e)
      }
    }
    
    // Set headers explicitly with multiple variants to handle case sensitivity issues
    let headers = {}
    if (token) {
      // Try multiple header case variants to ensure compatibility
      headers = {
        'Authorization': `Bearer ${token}`,
        'authorization': `Bearer ${token}`, // Lowercase variant
        'X-Auth-Token': token // Alternative format some backends use
      }
    }
    console.log('Using extended headers for profile update')
    
    // Send the request with explicit headers
    return api.post('/auth/profile', userData, { headers })
  }
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