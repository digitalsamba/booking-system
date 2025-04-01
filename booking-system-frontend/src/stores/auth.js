import { defineStore } from 'pinia'
import { authService } from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => {
    let user = null;
    try {
      const userData = localStorage.getItem('user');
      if (userData) {
        user = JSON.parse(userData);
      }
    } catch (e) {
      // Invalid JSON in localStorage, use null instead
      localStorage.removeItem('user');
    }
    
    return {
      user,
      token: localStorage.getItem('token') || null,
      loading: false,
      error: null
    };
  },
  
  getters: {
    isLoggedIn: (state) => !!state.token,
    userFullName: (state) => state.user ? `${state.user.display_name || state.user.username}` : ''
  },
  
  actions: {
    async login(credentials) {
      this.loading = true
      this.error = null
      
      try {
        const response = await authService.login(credentials)
        console.log('Login response:', response.data)
        
        if (response.data.success && response.data.data) {
          // Extract token and user from the data object
          const { token, user } = response.data.data
          
          if (token && user) {
            this.token = token
            this.user = user
            
            // Store in localStorage
            localStorage.setItem('token', this.token)
            localStorage.setItem('user', JSON.stringify(this.user))
            
            console.log('Login successful, token and user stored')
            return true
          }
        }
        
        console.error('Invalid login response:', response.data)
        this.error = 'Invalid response from server'
        return false
      } catch (error) {
        console.error('Login error:', error)
        this.error = error.response?.data?.error || 'Failed to login'
        return false
      } finally {
        this.loading = false
      }
    },
    
    async register(userData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await authService.register(userData)
        
        this.token = response.data.token
        this.user = response.data.user
        
        // Store in localStorage
        localStorage.setItem('token', this.token)
        localStorage.setItem('user', JSON.stringify(this.user))
        
        return true
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to register'
        return false
      } finally {
        this.loading = false
      }
    },
    
    async getProfile() {
      if (!this.token) {
        console.log('No token available for getProfile')
        return false
      }
      
      this.loading = true
      console.log('Auth store getProfile called with token:', this.token.substring(0, 10) + '...')
      
      try {
        const response = await authService.getProfile()
        console.log('GetProfile response:', response.data)
        this.user = response.data.user
        localStorage.setItem('user', JSON.stringify(this.user))
        return true
      } catch (error) {
        console.error('GetProfile error:', error)
        // Don't set error to avoid side effects during debugging
        // this.error = error.response?.data.error?.message || 'Failed to get profile'
        return false
      } finally {
        this.loading = false
        console.log('GetProfile completed')
      }
    },
    
    async updateProfile(userData) {
      console.log('Auth store updateProfile called with:', userData)
      this.loading = true
      this.error = null
      
      try {
        // Use the authService directly
        const response = await authService.updateProfile(userData)
        
        // Log what we received from the server
        console.log('Server response:', response.data.user);
        
        // Create a new user object combining the response with our sent data
        const updatedUser = {
          ...response.data.user,
          // Always include Digital Samba fields from userData, overriding any values from server
          team_id: userData.team_id || '',
          developer_key: userData.developer_key || ''
        }
        
        console.log('Auth store created updated user:', updatedUser)
        this.user = updatedUser
        
        // Store in localStorage
        console.log('Storing in localStorage')
        localStorage.setItem('user', JSON.stringify(this.user))
        console.log('Saved to localStorage, returning true')
        
        return true
      } catch (error) {
        console.error('Auth store update profile error:', error)
        this.error = error.response?.data.error?.message || 'Failed to update profile'
        
        // For 401 errors, don't trigger any UI state changes that might cause redirects
        if (error.response && error.response.status === 401) {
          console.log('Ignoring 401 error in auth store')
        }
        
        return false
      } finally {
        this.loading = false
        console.log('Auth store updateProfile completed')
      }
    },
    
    logout() {
      // Clear state
      this.user = null
      this.token = null
      
      // Clear localStorage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }
})