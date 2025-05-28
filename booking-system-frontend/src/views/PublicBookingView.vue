<template>
  <v-container :style="pageStyle">
    <v-row justify="center">
      <v-col cols="12" md="8">
        <component :is="'style'" v-if="brandingSettings.customCss">
          {{ brandingSettings.customCss }}
        </component>

        <v-card class="pa-6" :style="cardStyle">
          <div class="text-center mb-6">
            <img 
              v-if="brandingSettings.logoUrl"
              :src="brandingSettings.logoUrl" 
              alt="Provider Logo" 
              style="max-height: 120px; max-width: 300px; object-fit: contain;"
              class="mb-4"
            >
            <img 
              v-else
              src="/assets/logo.svg" 
              alt="SambaConnect" 
              height="120" 
              class="mb-4"
            >
            
            <template v-if="!bookingCompleted">
              <h1 class="text-h4 mb-2" :style="{ color: brandingSettings.textColor }">
                <template v-if="provider && (provider.display_name || provider.username)">
                  Book a Meeting with {{ provider.display_name || provider.username }}
                </template>
                <template v-else>
                  <v-progress-circular indeterminate :color="brandingSettings.primaryColor"></v-progress-circular>
                </template>
              </h1>
              <p class="text-body-1 text-medium-emphasis" :style="{ color: brandingSettings.textColor, opacity: 0.8 }">Select an available time slot below</p>
            </template>

            <template v-else>
              <div class="funky-success-vibes">
                Made with vibes
              </div>
            </template>

          </div>

          <v-alert
            v-if="error"
            type="error"
            class="mb-6"
          >
            {{ error }}
          </v-alert>

          <v-alert
            v-if="success"
            type="success"
            class="mb-6"
          >
            {{ success }}
          </v-alert>

          <template v-if="!bookingCompleted">
            <v-card class="mb-6">
              <v-card-title class="text-h6" :style="{ color: brandingSettings.textColor }">Select Date</v-card-title>
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
                      :color="brandingSettings.primaryColor" 
                      :header-color="brandingSettings.primaryColor"
                      :day-format="(date) => {
                        const formattedDate = new Date(date).toLocaleDateString('en-CA')
                        return availableDates.has(formattedDate) ? '●' : ''
                      }"
                      :day-props="(date) => {
                        const formattedDate = new Date(date).toLocaleDateString('en-CA')
                        return {
                          class: availableDates.has(formattedDate) ? 'available-date' : '',
                          style: availableDates.has(formattedDate) ? { '--v-primary-base': brandingSettings.primaryColor } : {}
                        }
                      }"
                    ></v-date-picker>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <v-card v-if="selectedDate" class="mb-6">
              <v-card-title class="text-h6" :style="{ color: brandingSettings.textColor }">Available Time Slots</v-card-title>
              <v-card-text>
                <v-row>
                  <v-col v-for="slot in availableSlots" :key="slot.id" cols="12" sm="6" md="4">
                    <v-btn
                      block
                      :color="slot.id === selectedSlot?.id ? brandingSettings.primaryColor : 'default'"
                      :style="slot.id !== selectedSlot?.id ? { borderColor: brandingSettings.secondaryColor, color: brandingSettings.secondaryColor } : {}"
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

            <v-card v-if="selectedSlot" class="mb-6">
              <v-card-title class="text-h6" :style="{ color: brandingSettings.textColor }">Booking Details</v-card-title>
              <v-card-text>
                <v-form ref="form" v-model="isFormValid">
                  <v-text-field
                    v-model="bookingForm.name"
                    label="Your Name"
                    :rules="[v => !!v || 'Name is required']"
                    required
                    class="mb-4"
                    :color="brandingSettings.primaryColor"
                    :base-color="brandingSettings.textColor"
                    :label-color="brandingSettings.textColor"
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
                    :color="brandingSettings.primaryColor"
                     :base-color="brandingSettings.textColor"
                    :label-color="brandingSettings.textColor"
                  ></v-text-field>

                  <v-textarea
                    v-model="bookingForm.notes"
                    label="Meeting Notes (Optional)"
                    rows="3"
                    class="mb-4"
                    :color="brandingSettings.primaryColor"
                    :base-color="brandingSettings.textColor"
                    :label-color="brandingSettings.textColor"
                  ></v-textarea>

                  <v-btn
                    :color="brandingSettings.primaryColor" 
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
          </template>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { ref, computed, onMounted, reactive } from 'vue'
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
    const bookingCompleted = ref(false)
    
    // Provider details
    const provider = ref({})
    const providerUserId = ref(null)

    // Branding Settings (use reactive)
    const brandingSettings = reactive({
      logoUrl: '',
      primaryColor: '#1976D2', // Default Vuetify blue
      secondaryColor: '#424242', // Default Vuetify grey
      backgroundColor: '#FFFFFF',
      textColor: '#000000',
      fontFamily: '', // Default browser font
      customCss: ''
    })
    const brandingLoading = ref(false);
    const brandingError = ref(null);

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

    // Fetch branding settings
    const loadBrandingSettings = async (userId) => {
      if (!userId) return;
      brandingLoading.value = true;
      brandingError.value = null;
      try {
        const response = await publicBookingService.getBrandingSettings(userId);
        const fetchedData = response.data; // Assuming direct data return now
        if (fetchedData && typeof fetchedData === 'object') {
            console.log('Fetched public branding settings:', fetchedData);
            brandingSettings.logoUrl = fetchedData.logoUrl ?? '';
            brandingSettings.primaryColor = fetchedData.primaryColor ?? '#1976D2';
            brandingSettings.secondaryColor = fetchedData.secondaryColor ?? '#424242';
            brandingSettings.backgroundColor = fetchedData.backgroundColor ?? '#FFFFFF';
            brandingSettings.textColor = fetchedData.textColor ?? '#000000';
            brandingSettings.fontFamily = fetchedData.fontFamily ?? '';
            brandingSettings.customCss = fetchedData.customCss ?? '';
        } else {
            console.log(`No specific branding settings found for userId ${userId}, using defaults.`);
            // Keep defaults if no settings found
        }
      } catch (err) {
        if (err.response && err.response.status === 404) {
             console.log(`No branding settings found for userId ${userId} (404). Using defaults.`);
             // Keep defaults
        } else {
            console.error('Error loading branding settings:', err);
            brandingError.value = 'Could not load branding appearance.'; // User-friendly error
        }
      } finally {
        brandingLoading.value = false;
      }
    };

    // Load provider details AND branding
    const loadProviderDetails = async () => {
      error.value = null; // Clear main error
      try {
        const username = route.params.username
        if (!username) {
          error.value = 'Invalid provider link'
          return
        }

        const response = await publicBookingService.getProviderDetails(username)
        console.log('Provider details response:', response.data)
        provider.value = response.data?.data ?? response.data // Handle potential nesting
        providerUserId.value = provider.value?.userId // Get userId from response

        if (!providerUserId.value) {
            console.error('Provider details response missing userId!', provider.value);
            error.value = 'Could not load provider information correctly.';
            return; // Cannot load branding without userId
        }

        // Load available dates after getting provider details
        await loadAvailableDates()
        // Load branding settings using the fetched userId
        await loadBrandingSettings(providerUserId.value)

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
      // Use validate method on the form reference directly
      const { valid } = await form.value.validate()
      if (!valid) return
      
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
        bookingCompleted.value = true
        
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

    // Computed styles for applying branding
    const pageStyle = computed(() => ({
      backgroundColor: brandingSettings.backgroundColor,
      fontFamily: brandingSettings.fontFamily || 'inherit' // Apply font globally if set
    }));

    const cardStyle = computed(() => ({
        // Could add card-specific background if needed, but pageStyle might suffice
        // color: brandingSettings.textColor // Set base text color for card? Handled inline mostly
    }));

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
      submitBooking,
      bookingCompleted,
      brandingSettings,
      brandingLoading,
      brandingError,
      pageStyle,
      cardStyle
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
  background-color: var(--v-primary-base, #1976D2);
  border-radius: 50%;
}

.funky-success-vibes {
  font-size: 3rem; /* Large */
  font-weight: bold;
  margin-top: 2rem;
  margin-bottom: 2rem;
  /* Garish gradient text effect */
  background: linear-gradient(90deg, #ff00ff, #00ffff, #ffff00, #ff00ff);
  background-size: 200% auto;
  color: #fff;
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: vibes-animation 4s linear infinite;
  /* Add some text shadow for extra pop */
  text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
}

@keyframes vibes-animation {
  0% { background-position: 0% center; }
  50% { background-position: 100% center; }
  100% { background-position: 0% center; }
}
</style> 