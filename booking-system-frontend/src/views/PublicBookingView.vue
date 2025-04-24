<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card class="pa-6">
          <div class="text-center mb-6">
            <img src="/assets/logo.svg" alt="SambaConnect" height="120" class="mb-4">
            <h1 class="text-h4 mb-2">
              <template v-if="provider && (provider.display_name || provider.username)">
                Book a Meeting with {{ provider.display_name || provider.username }}
              </template>
              <template v-else>
                <v-progress-circular indeterminate></v-progress-circular>
              </template>
            </h1>
            <p class="text-body-1 text-medium-emphasis">Select an available time slot below</p>
          </div>

          <!-- Error Message -->
          <v-alert
            v-if="error"
            type="error"
            class="mb-6"
          >
            {{ error }}
          </v-alert>

          <!-- Success Message -->
          <v-alert
            v-if="success"
            type="success"
            class="mb-6"
          >
            {{ success }}
          </v-alert>

          <!-- Date Selection -->
          <v-card class="mb-6">
            <v-card-title class="text-h6">Select Date</v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12">
                  <v-date-picker
                    v-model="selectedDate"
                    :min="minDate"
                    :max="maxDate"
                    :allowed-dates="allowedDates"
                    @update:model-value="loadAvailableSlots"
                    class="mt-2"
                    full-width
                    elevation="0"
                    :day-format="(date) => {
                      const formattedDate = new Date(date).toLocaleDateString('en-CA')
                      return availableDates.has(formattedDate) ? '●' : ''
                    }"
                    :day-props="(date) => {
                      const formattedDate = new Date(date).toLocaleDateString('en-CA')
                      return {
                        class: availableDates.has(formattedDate) ? 'available-date' : ''
                      }
                    }"
                  ></v-date-picker>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Time Slots -->
          <v-card v-if="selectedDate" class="mb-6">
            <v-card-title class="text-h6">Available Time Slots</v-card-title>
            <v-card-text>
              <v-row>
                <v-col v-for="slot in availableSlots" :key="slot.id" cols="12" sm="6" md="4">
                  <v-btn
                    block
                    :color="slot.id === selectedSlot?.id ? 'primary' : 'default'"
                    :variant="slot.id === selectedSlot?.id ? 'flat' : 'outlined'"
                    @click="selectTimeSlot(slot)"
                    :disabled="slot.isBooked"
                  >
                    {{ formatTime(slot.startTime) }} - {{ formatTime(slot.endTime) }}
                  </v-btn>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Booking Form -->
          <v-card v-if="selectedSlot" class="mb-6">
            <v-card-title class="text-h6">Booking Details</v-card-title>
            <v-card-text>
              <v-form ref="form" v-model="isFormValid">
                <v-text-field
                  v-model="bookingForm.name"
                  label="Your Name"
                  :rules="[v => !!v || 'Name is required']"
                  required
                  class="mb-4"
                ></v-text-field>

                <v-text-field
                  v-model="bookingForm.email"
                  label="Your Email"
                  :rules="[
                    v => !!v || 'Email is required',
                    v => /.+@.+\..+/.test(v) || 'Email must be valid'
                  ]"
                  required
                  class="mb-4"
                ></v-text-field>

                <v-textarea
                  v-model="bookingForm.notes"
                  label="Meeting Notes (Optional)"
                  rows="3"
                  class="mb-4"
                ></v-textarea>

                <v-btn
                  color="primary"
                  block
                  :loading="isSubmitting"
                  :disabled="!isFormValid"
                  @click="submitBooking"
                >
                  Confirm Booking
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { publicBookingService } from '../services/api'

export default {
  name: 'PublicBookingView',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const form = ref(null)
    const isFormValid = ref(false)
    const isSubmitting = ref(false)
    const error = ref(null)
    const success = ref(null)
    
    // Provider details
    const provider = ref({})
    const selectedDate = ref(null)
    const selectedSlot = ref(null)
    const availableSlots = ref([])
    const availableDates = ref(new Set()) // Store dates that have availability
    
    // Form data
    const bookingForm = ref({
      name: '',
      email: '',
      notes: ''
    })

    // Date picker constraints
    const minDate = computed(() => {
      const today = new Date()
      return today.toISOString().split('T')[0]
    })

    const maxDate = computed(() => {
      const date = new Date()
      date.setDate(date.getDate() + 30) // Allow booking up to 30 days in advance
      return date.toISOString().split('T')[0]
    })

    const allowedDates = (date) => {
      // Convert the date to YYYY-MM-DD format for comparison
      const formattedDate = new Date(date).toLocaleDateString('en-CA')
      return availableDates.value.has(formattedDate)
    }

    // Load available dates for the current month
    const loadAvailableDates = async () => {
      try {
        const today = new Date()
        const startDate = today.toLocaleDateString('en-CA')
        const endDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 30).toLocaleDateString('en-CA')
        
        console.log('Loading available dates from', startDate, 'to', endDate)
        
        const response = await publicBookingService.getAvailableSlots(
          route.params.username,
          startDate,
          endDate
        )
        
        if (response.data?.data?.slots) {
          // Extract unique dates from slots
          const dates = new Set()
          response.data.data.slots.forEach(slot => {
            const slotDate = new Date(slot.start_time).toLocaleDateString('en-CA')
            dates.add(slotDate)
          })
          availableDates.value = dates
          console.log('Available dates:', Array.from(dates))
        }
      } catch (err) {
        console.error('Error loading available dates:', err)
      }
    }

    // Load provider details
    const loadProviderDetails = async () => {
      try {
        const username = route.params.username
        if (!username) {
          error.value = 'Invalid provider link'
          return
        }

        const response = await publicBookingService.getProviderDetails(username)
        console.log('Provider details response:', response.data)
        provider.value = response.data.data // Access the nested data property
        // Load available dates after getting provider details
        await loadAvailableDates()
      } catch (err) {
        console.error('Error loading provider details:', err)
        error.value = 'Unable to load provider details. Please try again later.'
      }
    }

    // Load available slots for selected date
    const loadAvailableSlots = async () => {
      if (!selectedDate.value) return
      
      try {
        // Format the selected date to YYYY-MM-DD in UTC
        const date = new Date(selectedDate.value)
        const formattedDate = date.toLocaleDateString('en-CA')
        console.log('Loading slots for date:', formattedDate)
        
        const response = await publicBookingService.getAvailableSlots(
          route.params.username,
          formattedDate,
          formattedDate // Use the same date for both start and end
        )
        
        console.log('Response from backend:', response.data)
        
        // Transform slots data for display
        if (response.data?.data?.slots) {
          // Filter slots to only include those for the selected date
          const selectedDateStr = new Date(selectedDate.value).toLocaleDateString('en-CA')
          availableSlots.value = response.data.data.slots
            .filter(slot => {
              const slotDate = new Date(slot.start_time).toLocaleDateString('en-CA')
              return slotDate === selectedDateStr
            })
            .map(slot => ({
              id: slot.id,
              original_id: slot._id || slot.id, // Keep the original MongoDB ID
              startTime: new Date(slot.start_time),
              endTime: new Date(slot.end_time),
              isBooked: !slot.is_available
            }))
          console.log('Transformed slots:', availableSlots.value)
          error.value = null
        } else {
          availableSlots.value = []
          error.value = 'No available slots found for this date.'
        }
      } catch (err) {
        console.error('Error loading available slots:', err)
        error.value = err.response?.data?.error || 'Unable to load available time slots. Please try again.'
        availableSlots.value = []
      }
    }

    const selectTimeSlot = (slot) => {
      if (slot.isBooked) return
      selectedSlot.value = slot
    }

    const formatTime = (date) => {
      return new Date(date).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      })
    }

    const submitBooking = async () => {
      if (!form.value.validate()) return
      
      isSubmitting.value = true
      error.value = null
      success.value = null
      
      try {
        console.log('Submitting booking with data:', {
          username: route.params.username,
          bookingData: {
            ...bookingForm.value,
            slot: selectedSlot.value
          }
        })
        
        const response = await publicBookingService.createBooking(
          route.params.username,
          {
            ...bookingForm.value,
            slot: selectedSlot.value
          }
        )
        
        console.log('Booking response:', response.data)
        
        success.value = 'Booking created successfully! You will receive a confirmation email shortly with your meeting link.'
        
        // Clear form and selection
        bookingForm.value = {
          name: '',
          email: '',
          notes: ''
        }
        selectedSlot.value = null
        
        // Reload available slots to update the UI
        await loadAvailableSlots()
      } catch (err) {
        console.error('Booking failed:', err)
        const errorMessage = err.response?.data?.error || err.message || 'Unable to create booking. Please try again.'
        error.value = errorMessage
      } finally {
        isSubmitting.value = false
      }
    }

    onMounted(async () => {
      await loadProviderDetails()
    })

    return {
      provider,
      selectedDate,
      selectedSlot,
      availableSlots,
      availableDates,
      bookingForm,
      form,
      isFormValid,
      isSubmitting,
      error,
      success,
      minDate,
      maxDate,
      allowedDates,
      loadAvailableSlots,
      selectTimeSlot,
      formatTime,
      submitBooking
    }
  }
}
</script>

<style scoped>
.v-date-picker {
  width: 100%;
}

.available-date {
  position: relative;
}

.available-date::after {
  content: '';
  position: absolute;
  bottom: 2px;
  left: 50%;
  transform: translateX(-50%);
  width: 4px;
  height: 4px;
  background-color: var(--v-primary-base);
  border-radius: 50%;
}
</style> 