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
      
      <button class="btn btn-primary" @click="generateSlots" :disabled="isGenerating">
        {{ isGenerating ? 'Generating...' : 'Generate Time Slots' }}
      </button>
    </div>
    
    <div class="availability-calendar card">
      <h2>Current Availability</h2>
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
              <span class="slot-id">ID: {{ slot.id ? slot.id.substring(0, 6) + '...' : 'missing' }}</span>
              <button class="delete-btn" @click="deleteSlot(slot.id)" :disabled="!slot.id">×</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <button @click="testEndpoint" class="btn btn-secondary">Test API Parameters</button>
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
      
      try {
        const response = await fetch('/api/availability', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        const data = await response.json()
        
        if (data.success) {
          slots.value = data.slots
          
          // Add debug logging to examine the slot structure
          console.log('Loaded slots:', slots.value);
          if (slots.value.length > 0) {
            console.log('Sample slot structure:', slots.value[0]);
          }
        } else {
          message.value = {
            type: 'alert-error',
            text: data.error || 'Failed to load availability slots'
          }
        }
      } catch (error) {
        console.error('Error loading slots:', error)
        message.value = {
          type: 'alert-error',
          text: 'Error loading availability slots'
        }
      } finally {
        loading.value = false
      }
    }
    
    // Generate new slots
    const generateSlots = async () => {
      isGenerating.value = true
      message.value = null
      
      try {
        const response = await fetch('/api/availability/generate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify({
            start_date: generateForm.start_date,
            end_date: generateForm.end_date,
            daily_start_time: generateForm.daily_start_time,
            daily_end_time: generateForm.daily_end_time,
            slot_duration: parseInt(generateForm.slot_duration),
            days_of_week: generateForm.days_of_week
          })
        })
        
        const data = await response.json()
        
        if (data.message) {
          message.value = {
            type: 'alert-success',
            text: `${data.message}: ${data.count} slots created`
          }
          loadSlots() // Refresh the slots display
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
    const deleteSlot = async (slotId) => {
      // Check if ID is valid before proceeding
      if (!slotId) {
        message.value = {
          type: 'alert-error',
          text: 'Cannot delete slot: Invalid ID'
        };
        console.error('Attempted to delete slot with invalid ID:', slotId);
        return;
      }
      
      if (!confirm('Are you sure you want to delete this time slot?')) {
        return;
      }
      
      try {
        // Log the request for debugging
        console.log(`Deleting slot with ID: ${slotId}`);
        
        // Use DELETE method with the slot ID in the URL path
        const response = await fetch(`/api/availability/${slotId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        
        const data = await response.json();
        
        if (data.success) {
          message.value = {
            type: 'alert-success',
            text: data.message || 'Slot deleted successfully'
          };
          // Remove the slot from the array
          slots.value = slots.value.filter(slot => slot.id !== slotId);
        } else {
          message.value = {
            type: 'alert-error',
            text: data.error || 'Failed to delete slot'
          };
        }
      } catch (error) {
        console.error('Error deleting slot:', error);
        message.value = {
          type: 'alert-error',
          text: 'Error deleting availability slot'
        };
      }
    }

    // Add this method for testing
    const testEndpoint = async () => {
      const testId = '12345';
      
      console.log("Testing endpoint with different URL patterns...");
      
      // Test 1: Path parameter style
      try {
        const response1 = await fetch(`/api/availability/testParams/${testId}`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        const data1 = await response1.json();
        console.log("Test 1 (Path parameter):", data1);
      } catch (e) {
        console.error("Test 1 failed:", e);
      }
      
      // Test 2: Query parameter style
      try {
        const response2 = await fetch(`/api/availability/testParams?id=${testId}`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        const data2 = await response2.json();
        console.log("Test 2 (Query parameter):", data2);
      } catch (e) {
        console.error("Test 2 failed:", e);
      }
    }
    
    // Format helpers
    const formatDate = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', { 
        weekday: 'short', 
        month: 'short', 
        day: 'numeric' 
      })
    }
    
    const formatTime = (timeString) => {
      const date = new Date(timeString)
      return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
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
      formatDate,
      formatTime,
      testEndpoint
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

.slot-id {
  font-size: 0.8rem;
  color: #666;
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
</style>