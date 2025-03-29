<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" md="8">
        <v-card class="pa-6">
          <div class="text-center mb-6">
            <img src="/assets/logo.svg" alt="SambaConnect" height="48" class="mb-4">
            <h1 class="text-h4 mb-2">Book a Meeting with {{ provider.display_name || 'Loading...' }}</h1>
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
                    :color="slot.isSelected ? 'primary' : 'default'"
                    :variant="slot.isSelected ? 'flat' : 'outlined'"
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
import { useRoute } from 'vue-router'
import { providerService } from '../services/api'

export default {
  name: 'PublicBookingView',
  setup() {
    const route = useRoute()
    const form = ref(null)
    const isFormValid = ref(false)
    const isSubmitting = ref(false)
    const error = ref(null)
    
    // Provider details
    const provider = ref({})
    const selectedDate = ref(null)
    const selectedSlot = ref(null)
    const availableSlots = ref([])
    
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
      // Add any specific date restrictions here
      return true
    }

    // Load provider details
    const loadProviderDetails = async () => {
      try {
        const providerId = route.params.providerId
        if (!providerId) {
          error.value = 'Invalid provider link'
          return
        }

        const response = await providerService.getProviderDetails(providerId)
        provider.value = response.data
      } catch (err) {
        console.error('Error loading provider details:', err)
        error.value = 'Unable to load provider details. Please try again later.'
      }
    }

    // Load available slots for selected date
    const loadAvailableSlots = async () => {
      if (!selectedDate.value) return
      
      try {
        const response = await providerService.getAvailableSlots(route.params.providerId, selectedDate.value)
        availableSlots.value = response.data
      } catch (err) {
        console.error('Error loading available slots:', err)
        error.value = 'Unable to load available time slots. Please try again.'
      }
    }

    const selectTimeSlot = (slot) => {
      if (slot.isBooked) return
      selectedSlot.value = slot
    }

    const formatTime = (time) => {
      return time // TODO: Implement proper time formatting
    }

    const submitBooking = async () => {
      if (!form.value.validate()) return
      
      isSubmitting.value = true
      try {
        await providerService.createBooking(route.params.providerId, {
          ...bookingForm.value,
          slot: selectedSlot.value,
          date: selectedDate.value
        })
        
        // Show success message and redirect
        // TODO: Implement success handling
      } catch (err) {
        console.error('Booking failed:', err)
        error.value = 'Unable to create booking. Please try again.'
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
      bookingForm,
      form,
      isFormValid,
      isSubmitting,
      error,
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
</style> 