<template>
  <v-app>
    <v-app-bar color="primary" dark>
      <v-app-bar-title>
        <img src="/assets/digitalsamba-logo.svg" alt="Digital Samba" height="24">
      </v-app-bar-title>
      <v-spacer></v-spacer>
      <v-btn v-if="isLoggedIn" to="/" text>Home</v-btn>
      <v-btn v-if="isLoggedIn" to="/bookings" text>Bookings</v-btn>
      <v-btn v-if="isLoggedIn" to="/availability" text>Availability</v-btn>
      <v-btn v-if="isLoggedIn" to="/profile" text>Profile</v-btn>
      <v-btn v-if="isLoggedIn" @click="logout" text>Logout</v-btn>
      <v-btn v-else to="/login" text>Login</v-btn>
      <v-btn v-else to="/register" text>Register</v-btn>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <router-view />
      </v-container>
    </v-main>

    <v-footer app color="primary" class="d-flex flex-column">
      <div class="d-flex align-center">
        <span class="text-caption mr-2">Powered by</span>
        <img src="/assets/digitalsamba-logo.svg" alt="Digital Samba" height="20">
      </div>
    </v-footer>

    <!-- Digital Samba Setup Reminder Modal -->
    <v-dialog v-model="showSetupReminder" max-width="500">
      <v-card>
        <v-card-title class="text-h5">
          Digital Samba Setup Required
          <v-spacer></v-spacer>
          <v-btn icon @click="closeReminder">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <p>To enable video meetings in this application, you need to set up your Digital Samba credentials.</p>
          <p>Please go to your profile and add your:</p>
          <ul>
            <li>Digital Samba Team ID</li>
            <li>Developer Key</li>
          </ul>
          <p>Without these settings, your video meeting functionality will not work properly.</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="closeReminder">Remind Me Later</v-btn>
          <v-btn color="primary" @click="goToProfile">Set Up Now</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
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
    
    const isLoggedIn = computed(() => authStore.isLoggedIn)
    
    // Check if Digital Samba credentials are missing
    const checkDigitalSambaSetup = () => {
      if (authStore.isLoggedIn && authStore.user) {
        const missingCredentials = !authStore.user.team_id || 
                                  authStore.user.team_id === '' ||
                                  !authStore.user.developer_key ||
                                  authStore.user.developer_key === '';
        
        if (missingCredentials && router.currentRoute.value.path === '/') {
          showSetupReminder.value = true
        }
      }
    }
    
    onMounted(() => {
      checkDigitalSambaSetup()
    })
    
    watch(
      () => authStore.isLoggedIn,
      (isLoggedIn) => {
        if (isLoggedIn) {
          setTimeout(() => {
            checkDigitalSambaSetup()
          }, 500)
        }
      }
    )
    
    const closeReminder = () => {
      showSetupReminder.value = false
      localStorage.setItem('setupReminderDismissed', Date.now().toString())
    }
    
    const goToProfile = () => {
      closeReminder()
      router.push('/profile')
    }
    
    const logout = async () => {
      await authStore.logout()
      router.push('/login')
    }
    
    return {
      isLoggedIn,
      showSetupReminder,
      closeReminder,
      goToProfile,
      logout
    }
  }
}
</script>

<style>
/* Remove any conflicting styles */
</style>