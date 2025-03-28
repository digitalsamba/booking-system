<template>
  <div class="login">
    <div class="logo-container">
      <AppLogo :logoUrl="logoUrl" />
    </div>
    
    <h1>Login</h1>
    
    <div v-if="authStore.error" class="alert alert-error">
      {{ authStore.error }}
    </div>
    
    <form @submit.prevent="handleLogin" class="login-form">
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
        <label for="password">Password:</label>
        <input 
          type="password" 
          id="password" 
          v-model="password" 
          required 
          class="form-control" 
        />
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn" :disabled="authStore.loading">
          {{ authStore.loading ? 'Logging in...' : 'Login' }}
        </button>
      </div>
      
      <div class="form-footer">
        <p>Don't have an account? <router-link to="/register">Register here</router-link></p>
      </div>
    </form>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AppLogo from '../components/AppLogo.vue'

export default {
  name: 'LoginView',
  components: {
    AppLogo
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const username = ref('')
    const password = ref('')
    const logoUrl = ref('/assets/logo.svg') // Path to your logo once uploaded
    
    const handleLogin = async () => {
      const credentials = {
        username: username.value,
        password: password.value
      }
      
      const success = await authStore.login(credentials)
      
      if (success) {
        router.push({ name: 'home' })
      }
    }
    
    return {
      username,
      password,
      authStore,
      handleLogin,
      logoUrl
    }
  }
}
</script>

<style scoped>
.login {
  max-width: 500px;
  margin: 0 auto;
  padding: 2rem;
}

.logo-container {
  display: flex;
  justify-content: center;
  margin-bottom: 2rem;
}

.login-form {
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
</style>