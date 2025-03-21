<template>
  <div class="profile">
    <h1>Your Profile</h1>
    
    <div v-if="authStore.error" class="alert alert-error">
      {{ authStore.error }}
    </div>
    
    <div v-if="updateSuccess" class="alert alert-success">
      Profile updated successfully!
    </div>
    
    <div v-if="authStore.loading" class="loading">
      Loading profile...
    </div>
    
    <form v-else @submit.prevent="updateProfile" class="profile-form card">
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
        <button 
          type="submit" 
          class="btn" 
          :disabled="authStore.loading || (formData.new_password && passwordsDoNotMatch)"
        >
          {{ authStore.loading ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { ref, computed, onMounted, reactive } from 'vue'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'ProfileView',
  setup() {
    const authStore = useAuthStore()
    const updateSuccess = ref(false)
    
    const formData = reactive({
      username: '',
      email: '',
      display_name: '',
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
      } else {
        await authStore.getProfile()
        if (authStore.user) {
          formData.username = authStore.user.username
          formData.email = authStore.user.email
          formData.display_name = authStore.user.display_name
        }
      }
    })
    
    const updateProfile = async () => {
      // Don't proceed if trying to change password but passwords don't match
      if (formData.new_password && passwordsDoNotMatch.value) {
        return
      }
      
      const userData = {
        email: formData.email,
        display_name: formData.display_name
      }
      
      // Only include password fields if trying to change password
      if (formData.new_password && formData.current_password) {
        userData.current_password = formData.current_password
        userData.new_password = formData.new_password
      }
      
      const success = await authStore.updateProfile(userData)
      
      if (success) {
        updateSuccess.value = true
        // Clear password fields
        formData.current_password = ''
        formData.new_password = ''
        formData.new_password_confirm = ''
        
        // Hide success message after 3 seconds
        setTimeout(() => {
          updateSuccess.value = false
        }, 3000)
      }
    }
    
    return {
      authStore,
      formData,
      passwordsDoNotMatch,
      updateSuccess,
      updateProfile
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

.loading {
  text-align: center;
  padding: 2rem;
}
</style>