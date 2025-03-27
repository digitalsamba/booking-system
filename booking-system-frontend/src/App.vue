<template>
  <header>
    <nav v-if="isLoggedIn">
      <router-link to="/">Home</router-link> |
      <router-link to="/bookings">Bookings</router-link> |
      <router-link to="/profile">Profile</router-link> |
      <a href="#" @click.prevent="logout">Logout</a>
    </nav>
    <nav v-else>
      <router-link to="/login">Login</router-link> |
      <router-link to="/register">Register</router-link>
    </nav>
  </header>

  <main>
    <router-view />
  </main>
  
  <!-- Digital Samba Setup Reminder Modal -->
  <div v-if="showSetupReminder" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Digital Samba Setup Required</h3>
        <button class="close-btn" @click="closeReminder">×</button>
      </div>
      <div class="modal-body">
        <p>To enable video meetings in this application, you need to set up your Digital Samba credentials.</p>
        <p>Please go to your profile and add your:</p>
        <ul>
          <li>Digital Samba Team ID</li>
          <li>Developer Key</li>
        </ul>
        <p>Without these settings, your video meeting functionality will not work properly.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" @click="closeReminder">Remind Me Later</button>
        <button class="btn btn-primary" @click="goToProfile">Set Up Now</button>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, ref, watch, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'
import router from './router'

export default {
  name: 'App',
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const showSetupReminder = ref(false)
    
    // Check if Digital Samba credentials are missing
    const checkDigitalSambaSetup = () => {
      if (authStore.isLoggedIn && authStore.user) {
        // Console log for debugging
        console.log('Checking Digital Samba credentials:', { 
          team_id: authStore.user.team_id,
          developer_key: authStore.user.developer_key
        });
        
        // If user is logged in but missing credentials or they're empty strings
        const missingCredentials = !authStore.user.team_id || 
                                  authStore.user.team_id === '' ||
                                  !authStore.user.developer_key ||
                                  authStore.user.developer_key === '';
        
        // Only show reminder if on homepage and credentials are missing
        if (missingCredentials && router.currentRoute.value.path === '/') {
          showSetupReminder.value = true
        }
      }
    }
    
    // Check on mount and when auth state changes
    onMounted(() => {
      checkDigitalSambaSetup()
    })
    
    // Watch for login state changes
    watch(
      () => authStore.isLoggedIn,
      (isLoggedIn) => {
        if (isLoggedIn) {
          // When user logs in, check after a brief delay
          setTimeout(() => {
            checkDigitalSambaSetup()
          }, 500) // Small delay to ensure user data is loaded
        }
      }
    )
    
    // Close the reminder modal
    const closeReminder = () => {
      showSetupReminder.value = false
      // Store in localStorage to avoid showing too frequently
      localStorage.setItem('setupReminderDismissed', Date.now().toString())
    }
    
    // Navigate to profile page
    const goToProfile = () => {
      showSetupReminder.value = false
      router.push('/profile')
    }
    
    return {
      isLoggedIn: computed(() => authStore.isLoggedIn),
      showSetupReminder,
      closeReminder,
      goToProfile,
      logout: () => {
        authStore.logout()
        router.push('/login')
      }
    }
  }
}
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}

nav {
  padding: 30px;
}

nav a {
  font-weight: bold;
  color: #2c3e50;
  margin: 0 10px;
  text-decoration: none;
}

nav a.router-link-exact-active {
  color: #42b983;
}

main {
  max-width: 960px;
  margin: 0 auto;
  padding: 20px;
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
  gap: 10px;
  border-top: 1px solid #eee;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: white;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.btn-primary {
  background-color: #42b983;
  color: white;
}

.btn-secondary {
  background-color: #f8f9fa;
  color: #333;
  border: 1px solid #ddd;
}

.btn:hover {
  opacity: 0.9;
}
</style>