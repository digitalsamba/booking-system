<template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" class="text-center">
        <template v-if="user">
          <img src="/assets/logo.svg" alt="SambaConnect" height="48" class="mb-6">
          <v-card class="mb-8 pa-4">
            <h2 class="text-h4 mb-2">Hello, {{ user.display_name || user.username }}!</h2>
            <p class="text-body-1">Manage your video meetings with Digital Samba's powerful platform.</p>
          </v-card>
        </template>
        <template v-else>
          <h1 class="text-h3 mb-6">Welcome to Digital Samba</h1>
        </template>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col cols="12" md="4">
        <v-card to="/bookings" class="dashboard-card" height="100%">
          <v-card-item>
            <v-card-title>
              <v-icon start color="primary" class="mr-2">mdi-calendar</v-icon>
              Manage Bookings
            </v-card-title>
            <v-card-text>
              View, create, or cancel your bookings
            </v-card-text>
          </v-card-item>
        </v-card>
      </v-col>
      
      <v-col cols="12" md="4">
        <v-card to="/profile" class="dashboard-card" height="100%">
          <v-card-item>
            <v-card-title>
              <v-icon start color="primary" class="mr-2">mdi-account</v-icon>
              Your Profile
            </v-card-title>
            <v-card-text>
              Update your profile information
            </v-card-text>
          </v-card-item>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card to="/availability" class="dashboard-card" height="100%">
          <v-card-item>
            <v-card-title>
              <v-icon start color="primary" class="mr-2">mdi-clock-outline</v-icon>
              Manage Availability
            </v-card-title>
            <v-card-text>
              Set up your schedule and available time slots
            </v-card-text>
          </v-card-item>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
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
.dashboard-card {
  transition: transform 0.3s ease;
  cursor: pointer;
}

.dashboard-card:hover {
  transform: translateY(-5px);
}
</style>