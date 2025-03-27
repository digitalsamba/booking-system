<template>
  <div class="booking-card card">
    <div class="booking-header">
      <h3>{{ formattedDate }}</h3>
      <span class="booking-time">
        {{ formattedStartTime }} - {{ formattedEndTime }}
      </span>
      <span v-if="booking.status" class="booking-status" :class="booking.status">
        {{ booking.status }}
      </span>
    </div>
    
    <div class="booking-details">
      <p v-if="booking.notes" class="booking-notes">{{ booking.notes }}</p>
      
      <div class="booking-links" v-if="booking.customer_link || booking.provider_link">
        <a v-if="booking.customer_link" :href="booking.customer_link" target="_blank" class="btn">
          Join Meeting
        </a>
      </div>
      
      <div v-if="showActions" class="booking-actions">
        <slot name="actions">
          <button @click="$emit('cancel', booking._id)" class="btn btn-secondary">
            Cancel
          </button>
        </slot>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'BookingCard',
  props: {
    booking: {
      type: Object,
      required: true
    },
    showActions: {
      type: Boolean,
      default: true
    }
  },
  emits: ['cancel'],
  setup(props) {
    const formattedDate = computed(() => {
      return new Date(props.booking.start_time).toLocaleDateString()
    })
    
    const formattedStartTime = computed(() => {
      return new Date(props.booking.start_time).toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    })
    
    const formattedEndTime = computed(() => {
      return new Date(props.booking.end_time).toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    })
    
    return {
      formattedDate,
      formattedStartTime,
      formattedEndTime
    }
  }
}
</script>

<style scoped>
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

.booking-status.confirmed {
  background-color: var(--info-color);
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
</style>