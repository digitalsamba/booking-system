<template>
  <div class="app-logo" :class="{ 'small': small }">
    <img v-if="logoUrl" :src="logoUrl" alt="SambaConnect Logo" />
    <div v-else class="text-logo">
      <span class="primary">Samba</span><span class="secondary">Connect</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AppLogo',
  props: {
    small: {
      type: Boolean,
      default: false
    },
    logoUrl: {
      type: String,
      default: ''
    }
  }
}
</script>

<style scoped>
.app-logo {
  display: flex;
  align-items: center;
  font-weight: bold;
  font-size: 1.8rem;
}

.app-logo.small {
  font-size: 1.2rem;
}

.app-logo img {
  max-height: 50px;
}

.app-logo.small img {
  max-height: 30px;
}

.text-logo {
  font-family: 'Inter', sans-serif;
}

.text-logo .primary {
  color: var(--primary-color);
}

.text-logo .secondary {
  color: var(--secondary-color);
}
</style>