<template>
  <v-container>
    <v-card elevation="2" class="pa-5">
      <v-card-title class="headline mb-4">Booking Form Branding</v-card-title>
      <v-card-subtitle class="mb-6">Customize the appearance of your public booking page.</v-card-subtitle>

      <v-alert v-if="error" type="error" dense outlined class="mb-4">
        {{ error }}
      </v-alert>
      <v-alert v-if="successMessage" type="success" dense outlined class="mb-4">
        {{ successMessage }}
      </v-alert>

      <v-form @submit.prevent="saveSettings">
        <v-row>
          <!-- Loading State -->
          <v-col v-if="loading" cols="12" class="text-center">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
            <p>Loading settings...</p>
          </v-col>

          <!-- Settings Form (shown when not loading) -->
          <template v-else>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="settings.logoUrl"
                label="Logo URL"
                placeholder="https://example.com/your-logo.png"
                outlined
                dense
                hint="Enter the full URL of your logo image. Upload functionality coming soon."
                persistent-hint
              ></v-text-field>
            </v-col>

            <v-col cols="12">
              <v-divider class="my-4"></v-divider>
              <h3 class="mb-3">Color Scheme</h3>
            </v-col>

            <v-col cols="12" sm="6" md="3">
              <v-label>Primary Color</v-label>
              <v-color-picker
                v-model="settings.primaryColor"
                hide-inputs
                show-swatches
                elevation="0"
                width="100%"
              ></v-color-picker>
              <v-text-field
                v-model="settings.primaryColor"
                dense
                outlined
                class="mt-2"
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="6" md="3">
              <v-label>Secondary Color</v-label>
              <v-color-picker
                v-model="settings.secondaryColor"
                hide-inputs
                show-swatches
                elevation="0"
                 width="100%"
              ></v-color-picker>
              <v-text-field
                v-model="settings.secondaryColor"
                dense
                outlined
                class="mt-2"
              ></v-text-field>
            </v-col>

             <v-col cols="12" sm="6" md="3">
              <v-label>Background Color</v-label>
              <v-color-picker
                v-model="settings.backgroundColor"
                hide-inputs
                show-swatches
                elevation="0"
                 width="100%"
              ></v-color-picker>
              <v-text-field
                v-model="settings.backgroundColor"
                dense
                outlined
                class="mt-2"
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="6" md="3">
              <v-label>Text Color</v-label>
              <v-color-picker
                v-model="settings.textColor"
                hide-inputs
                show-swatches
                elevation="0"
                 width="100%"
              ></v-color-picker>
              <v-text-field
                v-model="settings.textColor"
                dense
                outlined
                class="mt-2"
              ></v-text-field>
            </v-col>

            <v-col cols="12">
              <v-divider class="my-4"></v-divider>
              <h3 class="mb-3">Advanced (Optional)</h3>
            </v-col>

            <v-col cols="12" md="6">
               <v-text-field
                v-model="settings.fontFamily"
                label="Font Family"
                placeholder="Arial, sans-serif"
                outlined
                dense
                hint="Specify CSS font-family rules."
                persistent-hint
              ></v-text-field>
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="settings.customCss"
                label="Custom CSS"
                outlined
                rows="5"
                placeholder=".booking-title { font-weight: bold; }"
                hint="Add custom CSS rules to override default styles. Use with caution."
                persistent-hint
              ></v-textarea>
            </v-col>

            <v-col cols="12" class="d-flex justify-end">
              <v-btn type="submit" color="primary" :loading="saving" :disabled="loading">
                Save Settings
              </v-btn>
            </v-col>
          </template>
        </v-row>
      </v-form>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import BrandingService from '@/services/BrandingService';

const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const successMessage = ref(null);

// Default settings structure
const settings = reactive({
  _id: null,
  userId: null,
  logoUrl: '',
  primaryColor: '#1976D2', // Default Vuetify primary blue
  secondaryColor: '#424242', // Default Vuetify secondary grey
  backgroundColor: '#FFFFFF',
  textColor: '#000000',
  fontFamily: '',
  customCss: '',
});

// Fetch settings when component mounts
onMounted(async () => {
  loading.value = true;
  error.value = null;
  successMessage.value = null;
  try {
    const response = await BrandingService.getSettings();
    // Access the nested 'data' object from the response
    const fetchedData = response.data?.data; 

    // Explicitly assign fetched data to reactive state if available
    if (fetchedData && typeof fetchedData === 'object') {
        // console.log('Fetched branding data (actual settings):', fetchedData); // Removed diagnostic log

        settings.logoUrl = fetchedData.logoUrl || '';
        settings.primaryColor = fetchedData.primaryColor || '#1976D2';
        settings.secondaryColor = fetchedData.secondaryColor || '#424242';
        settings.backgroundColor = fetchedData.backgroundColor || '#FFFFFF';
        settings.textColor = fetchedData.textColor || '#000000';
        settings.fontFamily = fetchedData.fontFamily || '';
        settings.customCss = fetchedData.customCss || '';
        // settings._id = fetchedData.id || null; // id field is present
        // settings.userId = fetchedData.userId || null;

    } else {
        // Check if the response was successful but data was missing/null
        if (response.data?.success) {
            console.log('API reported success, but no branding data found in response.data.data');
        } else {
            console.log('No existing branding data found, using defaults.');
        }
    }

  } catch (err) {
    if (err.response && err.response.status === 404) {
        error.value = 'No branding settings found. Using default values. Save to create settings.';
        console.log('GET /api/branding returned 404.');
    } else {
        console.error('Error fetching branding settings:', err);
        // Check if error response has a nested data structure
        const errorMsg = err.response?.data?.data?.error || err.response?.data?.error || 'Failed to load branding settings. Please try again.';
        error.value = errorMsg;
    }
  } finally {
    loading.value = false;
  }
});

// Save settings
const saveSettings = async () => {
  saving.value = true;
  error.value = null;
  successMessage.value = null;

  // Prepare data to send (exclude internal fields like _id, userId)
  const { _id, userId, ...updateData } = settings;

  try {
    await BrandingService.updateSettings(updateData);
    successMessage.value = 'Branding settings saved successfully!';
    // Optionally re-fetch data or update local state if PUT returns updated object
  } catch (err) {
    console.error('Error saving branding settings:', err);
    error.value = err.response?.data?.error || 'Failed to save settings. Please try again.';
  } finally {
    saving.value = false;
    // Hide success message after a few seconds
    if (successMessage.value) {
      setTimeout(() => { successMessage.value = null; }, 5000);
    }
  }
};
</script>

<style scoped>
/* Add any component-specific styles here */
.v-label {
  margin-bottom: 8px;
  display: block;
  font-weight: 500;
}
</style> 