import { defineStore } from 'pinia'
import { authService } from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),
  
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
        
        this.token = response.data.token
        this.user = response.data.user
        
        // Store in localStorage
        localStorage.setItem('token', this.token)
        localStorage.setItem('user', JSON.stringify(this.user))
        
        return true
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to login'
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
      if (!this.token) return false
      
      this.loading = true
      
      try {
        const response = await authService.getProfile()
        this.user = response.data.user
        localStorage.setItem('user', JSON.stringify(this.user))
        return true
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to get profile'
        return false
      } finally {
        this.loading = false
      }
    },
    
    async updateProfile(userData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await authService.updateProfile(userData)
        this.user = response.data.user
        localStorage.setItem('user', JSON.stringify(this.user))
        return true
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to update profile'
        return false
      } finally {
        this.loading = false
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