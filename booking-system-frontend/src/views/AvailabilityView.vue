<template>
  <div class="availability">
    <h1>Manage Your Availability</h1>
    
    <div v-if="message" class="alert" :class="message.type">
      {{ message.text }}
    </div>
    
    <div class="availability-controls card">
      <h2>Generate Availability</h2>
      <p class="section-description">Quickly create multiple time slots based on your schedule</p>
      
      <div class="form-group">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" v-model="generateForm.start_date" class="form-control" />
      </div>
      
      <div class="form-group">
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" v-model="generateForm.end_date" class="form-control" />
      </div>
      
      <div class="form-group">
        <label for="daily_start_time">Daily Start Time:</label>
        <input type="time" id="daily_start_time" v-model="generateForm.daily_start_time" class="form-control" />
      </div>
      
      <div class="form-group">
        <label for="daily_end_time">Daily End Time:</label>
        <input type="time" id="daily_end_time" v-model="generateForm.daily_end_time" class="form-control" />
      </div>
      
      <div class="form-group">
        <label for="slot_duration">Slot Duration (minutes):</label>
        <select id="slot_duration" v-model="generateForm.slot_duration" class="form-control">
          <option value="15">15 minutes</option>
          <option value="30">30 minutes</option>
          <option value="45">45 minutes</option>
          <option value="60">60 minutes</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Days of Week:</label>
        <div class="days-selector">
          <div 
            v-for="(day, index) in weekdays" 
            :key="index"
            class="day-checkbox"
            :class="{ selected: generateForm.days_of_week.includes(index) }"
            @click="toggleDay(index)"
          >
            {{ day }}
          </div>
        </div>
      </div>
      
      <div class="form-actions">
        <v-btn
          color="primary"
          @click="generateSlots"
          :disabled="isGenerating"
          class="generate-btn"
        >
          {{ isGenerating ? 'Generating...' : 'Generate Time Slots' }}
        </v-btn>
        
        <v-btn
          color="error"
          @click="deleteAllSlots"
          :disabled="loading || slots.length === 0"
          class="delete-btn"
          size="small"
          variant="outlined"
        >
          Delete All
        </v-btn>
      </div>
    </div>
    
    <div class="availability-calendar card">
      <div class="calendar-header">
        <h2>Current Availability</h2>
      </div>
      <p class="section-description">These are your currently available time slots</p>
      
      <div v-if="loading" class="loading">
        Loading your availability...
      </div>
      
      <div v-else-if="slots.length === 0" class="no-slots">
        You don't have any available time slots. Use the form above to generate some.
      </div>
      
      <div v-else class="slots-container">
        <div v-for="(daySlots, date) in groupedSlots" :key="date" class="date-group">
          <h3>{{ formatDate(date) }}</h3>
          <div class="slot-list">
            <div v-for="slot in daySlots" :key="slot.id" class="slot-item">
              <span class="slot-time">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</span>
              <button class="delete-btn" @click="deleteSlot(slot)" :disabled="!slot.id">×</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Delete Slot Confirmation Modal -->
    <v-dialog v-model="deleteDialog.show" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">
          Delete Time Slot
        </v-card-title>
        <v-card-text>
          <p>Are you sure you want to delete this time slot?</p>
          <div class="mt-4">
            <p><strong>Date:</strong> {{ formatDate(deleteDialog.slot?.start_time) }}</p>
            <p><strong>Time:</strong> {{ formatTime(deleteDialog.slot?.start_time) }} - {{ formatTime(deleteDialog.slot?.end_time) }}</p>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="deleteDialog.show = false">
            Cancel
          </v-btn>
          <v-btn color="error" variant="text" @click="confirmDeleteSlot">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Delete All Slots Confirmation Modal -->
    <v-dialog v-model="deleteAllDialog.show" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">
          Delete All Availability
        </v-card-title>
        <v-card-text>
          <p class="text-error">Warning: This action cannot be undone!</p>
          <p>Are you sure you want to delete all your availability slots?</p>
          <div class="mt-4">
            <p><strong>Total slots to delete:</strong> {{ slots.length }}</p>
            <p><strong>Date range:</strong> {{ formatDate(slots[0]?.start_time) }} to {{ formatDate(slots[slots.length - 1]?.start_time) }}</p>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="deleteAllDialog.show = false">
            Cancel
          </v-btn>
          <v-btn color="error" variant="text" @click="confirmDeleteAllSlots">
            Delete All
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'AvailabilityView',
  setup() {
    const authStore = useAuthStore()
    const slots = ref([])
    const loading = ref(true)
    const message = ref(null)
    const isGenerating = ref(false)
    const deleteDialog = ref({
      show: false,
      slot: null
    })
    const deleteAllDialog = ref({
      show: false
    })
    
    // Default form values with some reasonable defaults
    const generateForm = reactive({
      start_date: new Date().toISOString().split('T')[0], // Today
      end_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 2 weeks from now
      daily_start_time: '09:00',
      daily_end_time: '17:00',
      slot_duration: '30',
      days_of_week: [1, 2, 3, 4, 5] // Mon-Fri by default
    })
    
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    
    // Group slots by date for display
    const groupedSlots = computed(() => {
      const grouped = {}
      
      slots.value.forEach(slot => {
        const date = slot.start_time.split(' ')[0]
        if (!grouped[date]) {
          grouped[date] = []
        }
        grouped[date].push(slot)
      })
      
      // Sort dates
      return Object.keys(grouped)
        .sort()
        .reduce((obj, key) => {
          obj[key] = grouped[key].sort((a, b) => 
            new Date(a.start_time) - new Date(b.start_time)
          )
          return obj
        }, {})
    })
    
    // Toggle day selection for days of week
    const toggleDay = (dayIndex) => {
      const index = generateForm.days_of_week.indexOf(dayIndex)
      if (index === -1) {
        generateForm.days_of_week.push(dayIndex)
      } else {
        generateForm.days_of_week.splice(index, 1)
      }
      // Sort days for consistent display
      generateForm.days_of_week.sort()
    }
    
    // Load existing slots
    const loadSlots = async () => {
      loading.value = true
      slots.value = [] // Reset slots before loading
      
      try {
        console.log('Loading slots...')
        const response = await fetch('/api/availability', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        console.log('Received slots data:', data)
        
        if (data.success) {
          console.log('Setting slots:', data.data)
          slots.value = data.data || [] // Access slots from data.data
          console.log('Updated slots value:', slots.value)
        } else {
          console.error('Failed to load slots:', data.error)
          message.value = {
            type: 'alert-error',
            text: data.error || 'Failed to load availability slots'
          }
          slots.value = [] // Reset on error
        }
      } catch (error) {
        console.error('Error loading slots:', error)
        message.value = {
          type: 'alert-error',
          text: 'Error loading availability slots'
        }
        slots.value = [] // Reset on error
      } finally {
        loading.value = false
      }
    }
    
    // Generate new slots
    const generateSlots = async () => {
      isGenerating.value = true
      message.value = null
      
      try {
        const requestData = {
          start_date: generateForm.start_date,
          end_date: generateForm.end_date,
          daily_start_time: generateForm.daily_start_time,
          daily_end_time: generateForm.daily_end_time,
          slot_duration: parseInt(generateForm.slot_duration),
          days_of_week: generateForm.days_of_week
        }
        
        console.log('Sending request data:', requestData)
        
        const response = await fetch('/api/availability/generate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify(requestData)
        })
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        console.log('Received response:', data)
        
        if (data.success) {
          message.value = {
            type: 'alert-success',
            text: `${data.message} (${data.count} slots created)`
          }
          await loadSlots() // Refresh the slots display
        } else {
          message.value = {
            type: 'alert-error',
            text: data.error || 'Failed to generate slots'
          }
        }
      } catch (error) {
        console.error('Error generating slots:', error)
        message.value = {
          type: 'alert-error',
          text: 'Error generating availability slots'
        }
      } finally {
        isGenerating.value = false
      }
    }
    
    // Delete a slot - updated to use DELETE method
    const deleteSlot = (slot) => {
      deleteDialog.value.slot = slot
      deleteDialog.value.show = true
    }
    
    const confirmDeleteSlot = async () => {
      if (!deleteDialog.value.slot) return
      
      try {
        loading.value = true
        message.value = null
        
        // Use id instead of _id
        const slotId = deleteDialog.value.slot.id
        if (!slotId) {
          throw new Error('Invalid slot ID')
        }
        
        const response = await fetch(`/api/availability/deleteSlot?id=${slotId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to delete time slot')
        }
        
        // Remove the deleted slot from the list using id instead of _id
        slots.value = slots.value.filter(s => s.id !== slotId)
        message.value = {
          type: 'alert-success',
          text: 'Time slot deleted successfully'
        }
        
        // Close the dialog
        deleteDialog.value.show = false
        deleteDialog.value.slot = null
      } catch (err) {
        console.error('Error deleting slot:', err)
        message.value = {
          type: 'alert-error',
          text: err.message || 'Failed to delete time slot'
        }
      } finally {
        loading.value = false
      }
    }
    
    // Update deleteAllSlots to show modal instead of using confirm
    const deleteAllSlots = () => {
      deleteAllDialog.value.show = true
    }
    
    const confirmDeleteAllSlots = async () => {
      try {
        loading.value = true
        message.value = null
        
        const response = await fetch('/api/availability/deleteAll', {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to delete all slots')
        }
        
        const data = await response.json()
        
        if (data.success) {
          message.value = {
            type: 'alert-success',
            text: data.message || 'All availability slots deleted successfully'
          }
          slots.value = [] // Clear the slots array
        } else {
          throw new Error(data.error || 'Failed to delete all slots')
        }
      } catch (error) {
        console.error('Error deleting all slots:', error)
        message.value = {
          type: 'alert-error',
          text: error.message || 'Error deleting all availability slots'
        }
      } finally {
        loading.value = false
        deleteAllDialog.value.show = false
      }
    }
    
    // Format helpers
    const formatDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
    }
    
    const formatTime = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true
      })
    }
    
    // Load slots on mount
    onMounted(() => {
      loadSlots()
    })
    
    return {
      slots,
      loading,
      message,
      generateForm,
      isGenerating,
      weekdays,
      groupedSlots,
      toggleDay,
      generateSlots,
      deleteSlot,
      confirmDeleteSlot,
      deleteAllSlots,
      formatDate,
      formatTime,
      deleteDialog,
      deleteAllDialog,
      confirmDeleteAllSlots
    }
  }
}
</script>

<style scoped>
.availability {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  margin-bottom: 2rem;
}

.section-description {
  font-size: 0.9rem;
  color: #666;
  margin-top: -0.5rem;
  margin-bottom: 1rem;
}

.days-selector {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
}

.day-checkbox {
  padding: 8px 12px;
  border-radius: 4px;
  background-color: #eee;
  cursor: pointer;
  user-select: none;
  text-align: center;
  min-width: 50px;
}

.day-checkbox.selected {
  background-color: var(--primary-color);
  color: white;
}

.alert {
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.alert-success {
  color: #155724;
  background-color: #d4edda;
}

.alert-error {
  color: #721c24;
  background-color: #f8d7da;
}

.slots-container {
  max-height: 600px;
  overflow-y: auto;
}

.date-group {
  margin-bottom: 1.5rem;
}

.date-group h3 {
  margin-bottom: 0.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #eee;
}

.slot-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
}

.slot-item {
  padding: 12px;
  background-color: #f8f9fa;
  border-radius: 4px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.slot-time {
  font-size: 0.9rem;
}

.delete-btn {
  background: none;
  border: none;
  color: var(--error-color);
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.no-slots {
  padding: 2rem;
  text-align: center;
  color: #666;
}

.loading {
  text-align: center;
  padding: 2rem;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  align-items: center;
}

.generate-btn, .delete-btn {
  min-width: 120px;
  text-transform: none;
  font-weight: 500;
  letter-spacing: 0.5px;
  font-size: 0.875rem;
}

.text-error {
  color: var(--v-error-base);
  font-weight: 500;
}
</style>