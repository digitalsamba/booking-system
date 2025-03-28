<template>
  <div class="home">
    <h1>Welcome to SambaConnect</h1>
    <div v-if="user" class="welcome-message">
      <h2>Hello, {{ user.display_name || user.username }}!</h2>
      <p>Manage your video meetings with Digital Samba's powerful platform.</p>
    </div>
    
    <div class="dashboard-cards">
      <router-link to="/bookings" class="card dashboard-card">
        <h3>Manage Bookings</h3>
        <p>View, create, or cancel your bookings</p>
      </router-link>
      
      <router-link to="/profile" class="card dashboard-card">
        <h3>Your Profile</h3>
        <p>Update your profile information</p>
      </router-link>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'HomeView',
  setup() {
    const authStore = useAuthStore()
    
    return {
      user: computed(() => authStore.user)
    }
  }
}
</script>

<style scoped>
.home {
  text-align: center;
  padding: 2rem 0;
}

.welcome-message {
  margin: 2rem 0;
}

.dashboard-cards {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin-top: 2rem;
}

.dashboard-card {
  flex: 1;
  max-width: 300px;
  text-align: center;
  cursor: pointer;
  color: var(--secondary-color);
  text-decoration: none;
  transition: transform 0.3s ease;
}

.dashboard-card:hover {
  transform: translateY(-5px);
}

.dashboard-card h3 {
  color: var(--primary-color);
  margin-bottom: 0.5rem;
}
</style>