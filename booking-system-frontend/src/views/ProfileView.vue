<template>
  <div class="profile">
    <h1>Your Profile</h1>
    
    <!-- All test buttons removed - using the main Save Changes button now -->
    
    <div v-if="authStore.error" class="alert alert-error">
      {{ authStore.error }}
    </div>

    <div v-if="saveSuccess" class="alert alert-success">
      Profile updated successfully!
    </div>
    
    <div v-if="authStore.loading" class="loading">
      Loading profile...
    </div>
    
    <div v-else class="profile-form card">
      <div class="form-group">
        <label for="username">Username:</label>
        <input 
          type="text" 
          id="username" 
          v-model="formData.username" 
          disabled
          class="form-control" 
        />
        <span class="field-note">Username cannot be changed</span>
      </div>
      
      <div class="form-group">
        <label for="email">Email:</label>
        <input 
          type="email" 
          id="email" 
          v-model="formData.email" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="display_name">Display Name:</label>
        <input 
          type="text" 
          id="display_name" 
          v-model="formData.display_name" 
          required 
          class="form-control" 
        />
        <span class="field-note">This name will be shown to others in meeting links</span>
      </div>
      
      <div class="form-divider"></div>
      
      <h3>Digital Samba Integration</h3>
      <p class="section-description">These settings are required for video meeting functionality</p>
      
      <div class="ds-section" :class="{ 'highlight-section': missingCredentials }">
        <div v-if="missingCredentials" class="missing-credentials-alert">
          <i class="alert-icon">⚠️</i> 
          These settings are required for video meetings to work
        </div>
        
        <div class="form-group">
          <label for="team_id">Digital Samba Team ID:</label>
          <input 
            type="text" 
            id="team_id" 
            v-model="formData.team_id" 
            class="form-control"
            :class="{ 'highlight-field': !formData.team_id }"
            ref="teamIdInput"
          />
          <span class="field-note">Your Digital Samba team identifier</span>
        </div>
        
        <div class="form-group">
          <label for="developer_key">Developer Key:</label>
          <input 
            type="password" 
            id="developer_key" 
            v-model="formData.developer_key" 
            class="form-control"
            :class="{ 'highlight-field': !formData.developer_key }"
          />
          <span class="field-note">Your Digital Samba API developer key</span>
        </div>
      </div>
      
      <div class="form-divider"></div>
      
      <div class="form-group">
        <label for="current_password">Current Password:</label>
        <input 
          type="password" 
          id="current_password" 
          v-model="formData.current_password" 
          class="form-control" 
        />
        <span class="field-note">Required only when changing password</span>
      </div>
      
      <div class="form-group">
        <label for="new_password">New Password:</label>
        <input 
          type="password" 
          id="new_password" 
          v-model="formData.new_password" 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="new_password_confirm">Confirm New Password:</label>
        <input 
          type="password" 
          id="new_password_confirm" 
          v-model="formData.new_password_confirm" 
          class="form-control" 
        />
        <span v-if="passwordsDoNotMatch" class="error-message">
          Passwords do not match
        </span>
      </div>
      
      <div class="form-actions">
        <v-btn
          color="primary"
          @click="updateProfile"
          :loading="authStore.loading"
        >
          Save Changes
        </v-btn>
      </div>
    </div>
    
    <!-- Success Notification -->
    <v-snackbar
      v-model="showSuccess"
      color="success"
      timeout="3000"
      location="top"
      class="success-snackbar"
    >
      <div class="d-flex align-center">
        <v-icon start color="white" class="mr-2">mdi-check-circle</v-icon>
        <span>Your profile has been updated successfully!</span>
      </div>
      <template v-slot:actions>
        <v-btn
          color="white"
          variant="text"
          @click="showSuccess = false"
        >
          Close
        </v-btn>
      </template>
    </v-snackbar>

    <!-- Booking Link Section -->
    <v-card class="mb-6">
      <v-card-title class="text-h6">Your Booking Link</v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Share this link with your clients to let them book meetings with you.
        </p>
        <v-row align="center" class="mt-2">
          <v-col cols="12" sm="8">
            <v-text-field
              :model-value="bookingLink"
              readonly
              variant="outlined"
              density="compact"
              hide-details
              class="booking-link-field"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="4">
            <v-btn
              color="primary"
              variant="outlined"
              @click="copyBookingLink"
              :loading="isCopying"
            >
              <v-icon start>mdi-content-copy</v-icon>
              {{ copyButtonText }}
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import { ref, computed, onMounted, reactive } from 'vue'
import { useAuthStore } from '../stores/auth'
import { authService } from '../services/api'

export default {
  name: 'ProfileView',
  setup() {
    const authStore = useAuthStore()
    const updateSuccess = ref(false)
    const saveSuccess = ref(false)
    const showSuccess = ref(false)
    const teamIdInput = ref(null)
    const isCopying = ref(false)
    const copyButtonText = ref('Copy Link')
    
    // Computed property to determine if Digital Samba credentials are missing
    const missingCredentials = computed(() => {
      return !formData.team_id || !formData.developer_key
    })
    
    const formData = reactive({
      username: '',
      email: '',
      display_name: '',
      team_id: '',
      developer_key: '',
      current_password: '',
      new_password: '',
      new_password_confirm: ''
    })
    
    const passwordsDoNotMatch = computed(() => {
      return formData.new_password && formData.new_password_confirm && 
             formData.new_password !== formData.new_password_confirm
    })
    
    // Compute the booking link
    const bookingLink = computed(() => {
      if (!authStore.user?.username) return ''
      const baseUrl = window.location.origin
      return `${baseUrl}/booking/${authStore.user.username}`
    })
    
    onMounted(async () => {
      // Load user data
      if (authStore.user) {
        formData.username = authStore.user.username
        formData.email = authStore.user.email
        formData.display_name = authStore.user.display_name
        formData.team_id = authStore.user.team_id || ''
        formData.developer_key = authStore.user.developer_key || ''
      } else {
        await authStore.getProfile()
        if (authStore.user) {
          formData.username = authStore.user.username
          formData.email = authStore.user.email
          formData.display_name = authStore.user.display_name
          formData.team_id = authStore.user.team_id || ''
          formData.developer_key = authStore.user.developer_key || ''
        }
      }
      
      // Focus on the team_id input if credentials are missing
      // Use setTimeout to ensure the ref is mounted
      setTimeout(() => {
        if (!formData.team_id && teamIdInput.value) {
          teamIdInput.value.focus()
        }
      }, 200)
    })
    
    const updateProfile = async () => {
      try {
        console.log('Update profile function called')
        
        // Don't proceed if trying to change password but passwords don't match
        if (formData.new_password && passwordsDoNotMatch.value) {
          console.log('Passwords do not match, aborting')
          return
        }
        
        const userData = {
          email: formData.email,
          display_name: formData.display_name,
          team_id: formData.team_id || '',  // Ensure we send an empty string, not undefined
          developer_key: formData.developer_key || ''  // Ensure we send an empty string, not undefined
        }
        
        // Debug log
        console.log('Saving profile data:', userData)
        
        // Only include password fields if trying to change password
        if (formData.new_password && formData.current_password) {
          userData.current_password = formData.current_password
          userData.new_password = formData.new_password
        }
        
        console.log('About to call authStore.updateProfile')
        const success = await authStore.updateProfile(userData)
        console.log('authStore.updateProfile returned:', success)
        
        if (success) {
          updateSuccess.value = true
          saveSuccess.value = true
          showSuccess.value = true
          // Clear password fields
          formData.current_password = ''
          formData.new_password = ''
          formData.new_password_confirm = ''
          
          // Debug log the final user data
          console.log('Final user data after update:', authStore.user)
        }
      } catch (error) {
        console.error('Error updating profile:', error)
        alert('There was an error updating your profile. Please check the console for details.')
      }
    }
    
    // Function to save profile data with API first, fall back to localStorage
    const directSave = async () => {
      console.log('Attempting API update first...')
      
      // Don't proceed if trying to change password but passwords don't match
      if (formData.new_password && passwordsDoNotMatch.value) {
        console.error('Passwords do not match, aborting')
        return
      }
      
      // Prepare the user data to update
      const userData = {
        email: formData.email,
        display_name: formData.display_name,
        team_id: formData.team_id || '',  // Ensure we send an empty string, not undefined
        developer_key: formData.developer_key || ''  // Ensure we send an empty string, not undefined
      }
      
      // Only include password fields if trying to change password
      if (formData.new_password && formData.current_password) {
        userData.current_password = formData.current_password
        userData.new_password = formData.new_password
      }
      
      // Backup the current Digital Samba values to detect changes for UI feedback
      const previousTeamId = authStore.user?.team_id || ''
      const previousDevKey = authStore.user?.developer_key || ''
      
      try {
        // Try the API call first
        console.log('Checking if getProfile works first...')
        const profileWorks = await authStore.getProfile()
        console.log('getProfile success:', profileWorks)
        
        try {
          console.log('Using token for API update: Token exists', 'Token length:', localStorage.getItem('token')?.length || 0)
          const success = await authStore.updateProfile(userData)
          
          if (success) {
            console.log('API update successful')
            
            // Show success in UI
            updateSuccess.value = true
            saveSuccess.value = true
            
            // Clear password fields
            formData.current_password = ''
            formData.new_password = ''
            formData.new_password_confirm = ''
            
            return true
          } else {
            throw new Error('API update returned false')
          }
        } catch (apiError) {
          console.error('API update failed, falling back to localStorage:', apiError)
          
          // Fall back to localStorage update
          const userString = localStorage.getItem('user')
          const user = userString ? JSON.parse(userString) : {}
          
          // Update user with form values
          user.email = formData.email
          user.display_name = formData.display_name
          user.team_id = formData.team_id || ''
          user.developer_key = formData.developer_key || ''
          
          // Save back to localStorage
          localStorage.setItem('user', JSON.stringify(user))
          console.log('Updated localStorage with user data')
          
          // Update auth store directly
          if (authStore.user) {
            authStore.user.email = user.email
            authStore.user.display_name = user.display_name
            authStore.user.team_id = user.team_id
            authStore.user.developer_key = user.developer_key
          }
          
          // Show success in UI
          updateSuccess.value = true
          saveSuccess.value = true
          
          // Clear password fields
          formData.current_password = ''
          formData.new_password = ''
          formData.new_password_confirm = ''
          
          console.log('Profile saved to localStorage. Digital Samba credentials:', {
            team_id: user.team_id,
            developer_key: user.developer_key ? '(set)' : '(not set)'
          })
          
          return true
        }
      } catch (e) {
        console.error('Overall profile update error:', e)
        saveSuccess.value = false
        alert('An error occurred while saving your profile')
        return false
      }
    }
    
    // Copy booking link to clipboard
    const copyBookingLink = async () => {
      try {
        isCopying.value = true
        await navigator.clipboard.writeText(bookingLink.value)
        copyButtonText.value = 'Copied!'
        setTimeout(() => {
          copyButtonText.value = 'Copy Link'
        }, 2000)
      } catch (err) {
        console.error('Failed to copy link:', err)
        alert('Failed to copy link to clipboard')
      } finally {
        isCopying.value = false
      }
    }
      
    return {
      authStore,
      formData,
      passwordsDoNotMatch,
      updateSuccess,
      saveSuccess,
      showSuccess,
      missingCredentials,
      teamIdInput,
      updateProfile,
      directSave,
      bookingLink,
      isCopying,
      copyButtonText,
      copyBookingLink
    }
  }
}
</script>

<style scoped>
.profile {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;
}

.profile h1 {
  margin-bottom: 2rem;
  color: var(--secondary-color);
}

.profile-form {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--secondary-color);
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-control:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.form-control.highlight-field {
  border-color: #ff6b6b;
  background-color: #fff5f5;
}

.field-note {
  display: block;
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #666;
}

.form-divider {
  height: 1px;
  background-color: #eee;
  margin: 2rem 0;
}

.section-description {
  color: #666;
  margin-bottom: 1.5rem;
}

.ds-section {
  background-color: #f8f9fa;
  padding: 1.5rem;
  border-radius: 6px;
  margin-bottom: 2rem;
}

.ds-section.highlight-section {
  background-color: #fff5f5;
  border: 1px solid #ff6b6b;
}

.missing-credentials-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #d32f2f;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background-color: #ffebee;
  border-radius: 4px;
}

.alert-icon {
  font-size: 1.25rem;
}

.form-actions {
  margin-top: 2rem;
  text-align: right;
}

/* Remove the old .btn styles since we're using Vuetify's v-btn now */

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
  position: relative;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-header h3 {
  margin: 0;
  color: var(--secondary-color);
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
}

.modal-body {
  margin-bottom: 1.5rem;
}

.modal-footer {
  text-align: right;
}

/* Alert styles */
.alert {
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.alert-error {
  background-color: #ffebee;
  color: #d32f2f;
  border: 1px solid #ffcdd2;
}

.alert-success {
  background-color: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.success-snackbar {
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.success-snackbar :deep(.v-snackbar__content) {
  padding: 12px 16px;
}

.success-snackbar :deep(.v-snackbar__actions) {
  padding: 0 16px 0 0;
}

.success-snackbar :deep(.v-btn) {
  text-transform: none;
  font-weight: 500;
}

.booking-link-field {
  background-color: var(--v-surface-variant);
  border-radius: 4px;
}

.booking-link-field :deep(.v-field__input) {
  font-family: monospace;
  font-size: 0.875rem;
}
</style>