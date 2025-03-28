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
        <a 
          href="#"
          class="btn" 
          style="display: inline-block; text-decoration: none;"
          @click.prevent="updateProfile"
        >
          Save Changes
        </a>
      </div>
    </div>
    
    <!-- Success Modal -->
    <div v-if="updateSuccess" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>Profile Updated</h3>
          <button class="close-btn" @click="updateSuccess = false">×</button>
        </div>
        <div class="modal-body">
          <p>Your profile has been updated successfully!</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="updateSuccess = false">Close</button>
        </div>
      </div>
    </div>
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
    const teamIdInput = ref(null)
    
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
      
    return {
      authStore,
      formData,
      passwordsDoNotMatch,
      updateSuccess,
      saveSuccess,
      missingCredentials,
      teamIdInput,
      updateProfile,
      directSave
    }
  }
}
</script>

<style scoped>
.profile {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
}

.profile-form {
  margin-top: 2rem;
  padding: 2rem;
}

.form-divider {
  height: 1px;
  background-color: #eee;
  margin: 2rem 0;
}

.section-description {
  font-size: 0.9rem;
  color: #666;
  margin-top: -0.5rem;
  margin-bottom: 1rem;
}

.form-actions {
  margin-top: 2rem;
}

.field-note {
  display: block;
  font-size: 0.8rem;
  color: #666;
  margin-top: 0.25rem;
}

.error-message {
  color: var(--error-color);
  font-size: 0.85rem;
  display: block;
  margin-top: 0.5rem;
}

.alert-error {
  color: #721c24;
  background-color: #f8d7da;
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.alert-success {
  color: #155724;
  background-color: #d4edda;
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.loading {
  text-align: center;
  padding: 2rem;
}

/* Modal styles */
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
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  text-align: left;
}

.modal-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #42b983;
  color: white;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
}

.modal-body {
  padding: 1.5rem;
  line-height: 1.6;
}

.modal-body ul {
  margin-left: 1.5rem;
  margin-bottom: 1rem;
}

.modal-footer {
  padding: 1rem;
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #eee;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: white;
}

.btn-primary {
  background-color: #42b983;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.btn-primary:hover {
  opacity: 0.9;
}

.digital-samba-info {
  background-color: #f8f9fa;
  border-radius: 4px;
  padding: 1rem;
  margin-top: 1rem;
}

/* Digital Samba section highlighting */
.ds-section {
  padding: 1.5rem;
  border-radius: 5px;
  transition: all 0.3s ease;
}

.highlight-section {
  background-color: #fff8e1;
  border: 1px solid #ffecb3;
  box-shadow: 0 2px 5px rgba(255, 193, 7, 0.2);
}

.highlight-field {
  border-color: #ff9800 !important;
  background-color: #fff8e1 !important;
  box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.2) !important;
}

.missing-credentials-alert {
  background-color: #fff3cd;
  color: #856404;
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  border-radius: 4px;
  border-left: 4px solid #ffc107;
  display: flex;
  align-items: center;
}

.alert-icon {
  margin-right: 0.5rem;
  font-size: 1.25rem;
}
</style>