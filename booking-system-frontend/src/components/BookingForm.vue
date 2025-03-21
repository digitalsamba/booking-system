<template>
  <div class="booking-form">
    <h2>{{ editing ? 'Edit Booking' : 'New Booking' }}</h2>
    
    <form @submit.prevent="submitForm">
      <div class="form-group">
        <label for="start_time">Start Time:</label>
        <input 
          type="datetime-local" 
          id="start_time" 
          v-model="formData.start_time" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="end_time">End Time:</label>
        <input 
          type="datetime-local" 
          id="end_time" 
          v-model="formData.end_time" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="notes">Notes:</label>
        <textarea 
          id="notes" 
          v-model="formData.notes" 
          class="form-control" 
          rows="3"
        ></textarea>
      </div>
      
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" @click="$emit('cancel')">
          Cancel
        </button>
        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? (editing ? 'Updating...' : 'Booking...') : (editing ? 'Update' : 'Book') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { reactive, ref, onMounted } from 'vue'

export default {
  name: 'BookingForm',
  props: {
    booking: {
      type: Object,
      default: null
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['submit', 'cancel'],
  setup(props, { emit }) {
    const editing = ref(!!props.booking)
    
    const formData = reactive({
      start_time: '',
      end_time: '',
      notes: ''
    })
    
    onMounted(() => {
      if (props.booking) {
        // Format dates for datetime-local input
        const formatDate = (dateString) => {
          const date = new Date(dateString)
          return date.toISOString().slice(0, 16) // Format: YYYY-MM-DDTHH:MM
        }
        
        formData.start_time = formatDate(props.booking.start_time)
        formData.end_time = formatDate(props.booking.end_time)
        formData.notes = props.booking.notes || ''
      }
    })
    
    const submitForm = () => {
      emit('submit', {
        ...formData,
        // Convert string dates to Date objects
        start_time: new Date(formData.start_time),
        end_time: new Date(formData.end_time)
      })
    }
    
    return {
      formData,
      editing,
      submitForm
    }
  }
}
</script>

<style scoped>
.booking-form {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

textarea.form-control {
  resize: vertical;
}
</style>