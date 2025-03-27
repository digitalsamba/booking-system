import { defineStore } from 'pinia'
import { bookingService } from '../services/api'

export const useBookingsStore = defineStore('bookings', {
  state: () => ({
    bookings: [],
    currentBooking: null,
    loading: false,
    error: null
  }),
  
  getters: {
    upcomingBookings: (state) => {
      return state.bookings.filter(booking => 
        new Date(booking.start_time) >= new Date() && 
        booking.status !== 'cancelled'
      ).sort((a, b) => new Date(a.start_time) - new Date(b.start_time))
    },
    
    pastBookings: (state) => {
      return state.bookings.filter(booking => 
        new Date(booking.start_time) < new Date() || 
        booking.status === 'cancelled'
      ).sort((a, b) => new Date(b.start_time) - new Date(a.start_time))
    }
  },
  
  actions: {
    async fetchBookings() {
      this.loading = true
      this.error = null
      
      try {
        const response = await bookingService.getBookings()
        this.bookings = response.data.bookings
        return this.bookings
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to fetch bookings'
        return []
      } finally {
        this.loading = false
      }
    },
    
    async fetchBooking(id) {
      this.loading = true
      this.error = null
      
      try {
        const response = await bookingService.getBooking(id)
        this.currentBooking = response.data.booking
        return this.currentBooking
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to fetch booking details'
        return null
      } finally {
        this.loading = false
      }
    },
    
    async createBooking(bookingData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await bookingService.createBooking(bookingData)
        const newBooking = response.data.booking
        this.bookings.push(newBooking)
        return newBooking
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to create booking'
        return null
      } finally {
        this.loading = false
      }
    },
    
    async updateBooking(id, bookingData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await bookingService.updateBooking(id, bookingData)
        const updatedBooking = response.data.booking
        
        // Update in the array
        const index = this.bookings.findIndex(booking => booking._id === id)
        if (index !== -1) {
          this.bookings[index] = updatedBooking
        }
        
        if (this.currentBooking && this.currentBooking._id === id) {
          this.currentBooking = updatedBooking
        }
        
        return updatedBooking
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to update booking'
        return null
      } finally {
        this.loading = false
      }
    },
    
    async cancelBooking(id) {
      this.loading = true
      this.error = null
      
      try {
        const response = await bookingService.deleteBooking(id)
        
        // Update booking status in the array
        const index = this.bookings.findIndex(booking => booking._id === id)
        if (index !== -1) {
          this.bookings[index].status = 'cancelled'
        }
        
        if (this.currentBooking && this.currentBooking._id === id) {
          this.currentBooking.status = 'cancelled'
        }
        
        return true
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to cancel booking'
        return false
      } finally {
        this.loading = false
      }
    },
    
    async getMeetingLinks(id) {
      this.loading = true
      this.error = null
      
      try {
        const response = await bookingService.getMeetingLinks(id)
        return response.data.links
      } catch (error) {
        this.error = error.response?.data.error?.message || 'Failed to get meeting links'
        return null
      } finally {
        this.loading = false
      }
    }
  }
})