<template>
  <div class="register">
    <h1>Register Account</h1>
    
    <div v-if="authStore.error" class="alert alert-error">
      {{ authStore.error }}
    </div>
    
    <form @submit.prevent="handleRegister" class="register-form">
      <div class="form-group">
        <label for="username">Username:</label>
        <input 
          type="text" 
          id="username" 
          v-model="username" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="email">Email:</label>
        <input 
          type="email" 
          id="email" 
          v-model="email" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="display_name">Display Name:</label>
        <input 
          type="text" 
          id="display_name" 
          v-model="display_name" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="password">Password:</label>
        <input 
          type="password" 
          id="password" 
          v-model="password" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-group">
        <label for="password_confirm">Confirm Password:</label>
        <input 
          type="password" 
          id="password_confirm" 
          v-model="password_confirm" 
          required 
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
          :disabled="authStore.loading || passwordsDoNotMatch"
        >
          {{ authStore.loading ? 'Registering...' : 'Register' }}
        </button>
      </div>
      
      <div class="form-footer">
        <p>Already have an account? <router-link to="/login">Login here</router-link></p>
      </div>
    </form>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'RegisterView',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const username = ref('')
    const email = ref('')
    const display_name = ref('')
    const password = ref('')
    const password_confirm = ref('')
    
    const passwordsDoNotMatch = computed(() => {
      return password.value && password_confirm.value && 
             password.value !== password_confirm.value
    })
    
    const handleRegister = async () => {
      if (passwordsDoNotMatch.value) {
        return
      }
      
      const userData = {
        username: username.value,
        email: email.value,
        display_name: display_name.value,
        password: password.value
      }
      
      const success = await authStore.register(userData)
      
      if (success) {
        router.push({ name: 'home' })
      }
    }
    
    return {
      username,
      email,
      display_name,
      password,
      password_confirm,
      passwordsDoNotMatch,
      authStore,
      handleRegister
    }
  }
}
</script>

<style scoped>
.register {
  max-width: 500px;
  margin: 0 auto;
  padding: 2rem;
}

.register-form {
  margin-top: 2rem;
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-actions {
  margin-top: 2rem;
}

.form-footer {
  margin-top: 1.5rem;
  text-align: center;
}

.form-footer a {
  color: var(--primary-color);
  text-decoration: none;
}

.error-message {
  color: var(--error-color);
  font-size: 0.85rem;
  display: block;
  margin-top: 0.5rem;
}
</style>