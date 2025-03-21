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
</template>

<script>
import { computed } from 'vue'
import { useAuthStore } from './stores/auth'
import router from './router'

export default {
  name: 'App',
  setup() {
    const authStore = useAuthStore()
    
    return {
      isLoggedIn: computed(() => authStore.isLoggedIn),
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
</style>