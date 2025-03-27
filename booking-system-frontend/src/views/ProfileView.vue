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
      
      <div class="form-group">
        <label for="team_id">Digital Samba Team ID:</label>
        <input 
          type="text" 
          id="team_id" 
          v-model="formData.team_id" 
          class="form-control" 
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
        />
        <span class="field-note">Your Digital Samba API developer key</span>
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
          @click.prevent="directSave"
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
          <div v-if="digitalSambaUpdated" class="digital-samba-info">
            <p><strong>Digital Samba Credentials Updated:</strong></p>
            <ul>
              <li><strong>Team ID:</strong> {{ formData.team_id ? 'Set ✓' : 'Not Set' }}</li>
              <li><strong>Developer Key:</strong> {{ formData.developer_key ? 'Set ✓' : 'Not Set' }}</li>
            </ul>
          </div>
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
    const digitalSambaUpdated = ref(false)
    
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
        
        // Check if Digital Samba fields were updated
        const dsTeamIdUpdated = formData.team_id !== (authStore.user?.team_id || '')
        const dsDevKeyUpdated = formData.developer_key !== (authStore.user?.developer_key || '')
        digitalSambaUpdated.value = dsTeamIdUpdated || dsDevKeyUpdated
        
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
    
    // Direct localStorage save function
    const saveProfile = () => {
      alert('Save button clicked - saving to localStorage')
      
      // Get current user from localStorage
      let userData = null
      try {
        userData = JSON.parse(localStorage.getItem('user') || '{}')
      } catch (e) {
        console.error('Error parsing userData', e)
        userData = {}
      }
      
      // Update values directly
      userData.email = formData.email
      userData.display_name = formData.display_name
      userData.team_id = formData.team_id || ''
      userData.developer_key = formData.developer_key || ''
      
      console.log('Saving to localStorage:', userData)
      
      // Save back to localStorage
      localStorage.setItem('user', JSON.stringify(userData))
      
      // Update the store user object directly
      authStore.user = userData
      
      // Show success message
      saveSuccess.value = true
      updateSuccess.value = true
      
      // Clear password fields
      formData.current_password = ''
      formData.new_password = ''
      formData.new_password_confirm = ''
      
      alert('Profile saved successfully!')
    }
    
    // Client-side only implementation that only saves to localStorage
    const directSave = () => {
      try {
        // Get the user from localStorage
        const userString = localStorage.getItem('user')
        const user = userString ? JSON.parse(userString) : {}
        
        // Backup current values to detect changes
        const previousTeamId = user.team_id
        const previousDevKey = user.developer_key
        
        // Update user with form values
        user.email = formData.email
        user.display_name = formData.display_name
        user.team_id = formData.team_id || ''
        user.developer_key = formData.developer_key || ''
        
        // Save back to localStorage
        localStorage.setItem('user', JSON.stringify(user))
        console.log('Saved user data to localStorage:', {
          email: user.email,
          display_name: user.display_name,
          team_id: user.team_id, 
          developer_key: user.developer_key ? '(set)' : '(not set)'
        })
        
        // Update auth store directly
        if (authStore.user) {
          authStore.user.email = user.email
          authStore.user.display_name = user.display_name
          authStore.user.team_id = user.team_id
          authStore.user.developer_key = user.developer_key
        }
        
        // Check if Digital Samba fields were updated for UI feedback
        const teamIdChanged = previousTeamId !== user.team_id
        const devKeyChanged = previousDevKey !== user.developer_key
        digitalSambaUpdated.value = teamIdChanged || devKeyChanged
        
        // Show success in UI
        updateSuccess.value = true
        saveSuccess.value = true
        
        // Clear password fields
        formData.current_password = ''
        formData.new_password = ''
        formData.new_password_confirm = ''
        
        console.log('Profile updated successfully via localStorage')
      } catch (e) {
        console.error('Error saving to localStorage:', e)
      }
    }
      
    // Function removed
    
    return {
      authStore,
      formData,
      passwordsDoNotMatch,
      updateSuccess,
      saveSuccess,
      digitalSambaUpdated,
      updateProfile,
      saveProfile,
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
</style>