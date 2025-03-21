<template>
  <div class="bookings">
    <h1>Your Bookings</h1>
    
    <div v-if="bookingsStore.error" class="alert alert-error">
      {{ bookingsStore.error }}
    </div>
    
    <div v-if="bookingsStore.loading" class="loading">
      Loading bookings...
    </div>
    
    <div v-else>
      <h2>Upcoming Bookings</h2>
      
      <div v-if="upcomingBookings.length === 0" class="empty-state">
        <p>You have no upcoming bookings.</p>
        <button @click="showBookingForm = true" class="btn">Book an Appointment</button>
      </div>
      
      <div v-else class="booking-list">
        <BookingCard 
          v-for="booking in upcomingBookings" 
          :key="booking._id" 
          :booking="booking"
          @cancel="cancelBooking"
        />
      </div>
      
      <h2>Past Bookings</h2>
      
      <div v-if="pastBookings.length === 0" class="empty-state">
        <p>You have no past bookings.</p>
      </div>
      
      <div v-else class="booking-list">
        <BookingCard 
          v-for="booking in pastBookings" 
          :key="booking._id" 
          :booking="booking"
          :showActions="false"
        />
      </div>
    </div>
    
    <!-- New Booking Form Modal -->
    <div v-if="showBookingForm" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>Book Appointment</h2>
          <button @click="showBookingForm = false" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
          <BookingForm 
            :loading="bookingsStore.loading" 
            @submit="createBooking" 
            @cancel="showBookingForm = false"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useBookingsStore } from '../stores/bookings'
import BookingCard from '../components/BookingCard.vue'
import BookingForm from '../components/BookingForm.vue'

export default {
  name: 'BookingsView',
  components: {
    BookingCard,
    BookingForm
  },
  setup() {
    const bookingsStore = useBookingsStore()
    const showBookingForm = ref(false)
    
    onMounted(async () => {
      await bookingsStore.fetchBookings()
    })
    
    const formatTime = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    
    const cancelBooking = async (id) => {
      if (confirm('Are you sure you want to cancel this booking?')) {
        await bookingsStore.cancelBooking(id)
      }
    }
    
    const createBooking = async (bookingData) => {
      const success = await bookingsStore.createBooking(bookingData)
      if (success) {
        showBookingForm.value = false
      }
    }
    
    return {
      bookingsStore,
      upcomingBookings: computed(() => bookingsStore.upcomingBookings),
      pastBookings: computed(() => bookingsStore.pastBookings),
      showBookingForm,
      formatTime,
      cancelBooking,
      createBooking
    }
  }
}
</script>

<style scoped>
.bookings {
  padding: 1rem;
}

.booking-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin: 1.5rem 0 3rem;
}

.booking-card {
  display: flex;
  flex-direction: column;
}

.booking-header {
  border-bottom: 1px solid #eee;
  padding-bottom: 0.75rem;
  margin-bottom: 0.75rem;
}

.booking-time {
  font-size: 0.9rem;
  color: #666;
  display: block;
  margin-top: 0.25rem;
}

.booking-status {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  margin-top: 0.5rem;
}

.booking-status.completed {
  background-color: var(--success-color);
  color: white;
}

.booking-status.cancelled {
  background-color: var(--error-color);
  color: white;
}

.booking-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.booking-notes {
  margin-bottom: 1rem;
}

.booking-links {
  margin-bottom: 1rem;
}

.booking-actions {
  margin-top: auto;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  background: #f9f9f9;
  border-radius: 8px;
  margin: 1.5rem 0 3rem;
}

.empty-state p {
  margin-bottom: 1rem;
}

.loading {
  text-align: center;
  padding: 2rem;
}

/* Modal styling */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 100;
}

.modal-container {
  background-color: white;
  max-width: 500px;
  width: 90%;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.modal-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 1.5rem;
}
</style>