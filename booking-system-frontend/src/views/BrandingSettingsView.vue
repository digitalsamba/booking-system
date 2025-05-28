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
            <!-- Logo Section (Moved to Top) -->
            <v-col cols="12" md="6">
              <v-label>Logo</v-label>
              <!-- Logo Preview -->
              <v-img
                v-if="fullLogoUrl"
                :src="fullLogoUrl"
                :alt="'Current Logo'"
                max-height="100"
                max-width="250"
                contain
                class="mb-3 mt-1 elevation-1"
                style="border: 1px solid #eee; background-color: #f9f9f9;"
              ></v-img>
              <v-alert v-else type="info" variant="tonal" dense class="mb-3 mt-1">
                No logo set. Upload one below or paste a URL.
              </v-alert>

              <!-- Logo URL Input -->
              <v-text-field
                v-model="settings.logoUrl"
                label="Logo URL"
                placeholder="https://example.com/your-logo.png or upload below"
                outlined
                dense
                hint="Paste a URL or upload a file. Upload will overwrite the URL."
                persistent-hint
                class="mb-3"
              ></v-text-field>

              <!-- Logo File Upload -->
              <v-file-input
                v-model="selectedLogoFile"
                label="Upload Logo File"
                accept="image/png, image/jpeg, image/gif, image/svg+xml"
                outlined
                dense
                prepend-icon="mdi-camera"
                :loading="uploadingLogo"
                :disabled="uploadingLogo || loading"
                @update:model-value="handleLogoUpload"
                class="mb-2"
                hide-details="auto" 
              ></v-file-input>
              <div class="v-messages__message text-caption mb-2" style="padding-left: 16px; padding-right: 16px;">
                 Max 5MB. Allowed: JPG, PNG, GIF, SVG.
              </div>

              <!-- Logo Upload Error -->
              <v-alert v-if="logoUploadError" type="error" dense outlined class="mt-2 mb-4">
                {{ logoUploadError }}
              </v-alert>
            </v-col>

            <!-- Placeholder Column if needed for layout -->
            <v-col cols="12" md="6"></v-col> 

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
                Save Color & Style Settings
              </v-btn>
            </v-col>
          </template>
        </v-row>
      </v-form>

      <!-- Preview Section -->
      <v-divider class="my-6"></v-divider>
      <h3 class="mb-4">Live Preview (Approximate)</h3>
      <v-card 
        elevation="3"
        class="pa-5 mx-auto"
        max-width="500px"
        :style="previewStyle"
        >
        <div class="text-center mb-4">
          <img 
            v-if="fullLogoUrl" 
            :src="fullLogoUrl" 
            alt="Logo Preview" 
            style="max-height: 60px; max-width: 150px; object-fit: contain;"
            class="mb-3"
          >
          <div v-else style="height: 60px; display: flex; align-items: center; justify-content: center; color: var(--preview-text-color);" class="mb-3">[Your Logo Here]</div>
          <h4 :style="{ color: 'var(--preview-text-color)', fontFamily: settings.fontFamily || 'inherit' }" class="text-h5">
            Book a Meeting
          </h4>
          <p :style="{ color: 'var(--preview-text-color)', opacity: 0.8, fontFamily: settings.fontFamily || 'inherit' }">
            Select a date and time
          </p>
        </div>
        <v-btn :color="settings.primaryColor" block class="mb-2">
            Example Button (Primary)
        </v-btn>
         <v-btn :color="settings.secondaryColor" variant="outlined" block>
            Example Button (Secondary)
        </v-btn>
        <!-- Add more preview elements as needed -->
      </v-card>

    </v-card>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import BrandingService from '@/services/BrandingService';
import { API_URL } from '@/config';

const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const successMessage = ref(null);
const selectedLogoFile = ref(null);
const uploadingLogo = ref(false);
const logoUploadError = ref(null);

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
  logoUploadError.value = null;
  try {
    const response = await BrandingService.getSettings();
    // Use optional chaining and nullish coalescing
    const fetchedData = response.data?.data ?? response.data ?? null;

    // Explicitly assign fetched data to reactive state if available
    if (fetchedData && typeof fetchedData === 'object') {
        // console.log('Fetched branding data (actual settings):', fetchedData); // Removed diagnostic log
        settings.logoUrl = fetchedData.logoUrl ?? '';
        settings.primaryColor = fetchedData.primaryColor ?? '#1976D2';
        settings.secondaryColor = fetchedData.secondaryColor ?? '#424242';
        settings.backgroundColor = fetchedData.backgroundColor ?? '#FFFFFF';
        settings.textColor = fetchedData.textColor ?? '#000000';
        settings.fontFamily = fetchedData.fontFamily ?? '';
        settings.customCss = fetchedData.customCss ?? '';
        // No need to set _id or userId on the reactive settings for update

    } else {
        // Check if the response was successful but data was missing/null
        if (response.data?.success) {
            console.log('API reported success, but no branding data found in response data property');
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

// Save settings (for colors, fonts, CSS - logo is handled separately)
const saveSettings = async () => {
  saving.value = true;
  error.value = null;
  successMessage.value = null;

  // Prepare data to send (exclude logoUrl, _id, userId)
  const { _id, userId, logoUrl, ...updateData } = settings;

  // If logoUrl wasn't fetched (e.g., new user), don't send null/empty
  if (settings.logoUrl === '') {
    // Backend should handle missing logoUrl if needed, but we prevent sending it if it was never set
  } else {
     // Include logoUrl only if it has a value (set by fetch or upload)
     // updateData.logoUrl = settings.logoUrl;
     // Decision: Let backend keep existing logoUrl if not provided in PUT
  }

  try {
    await BrandingService.updateSettings(updateData);
    successMessage.value = 'Color and style settings saved successfully!';
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

// Handle Logo Upload
const handleLogoUpload = async () => {
  if (!selectedLogoFile.value || !selectedLogoFile.value[0]) {
    // No file selected or selection cleared
    return;
  }

  const file = selectedLogoFile.value[0];
  uploadingLogo.value = true;
  logoUploadError.value = null;
  successMessage.value = null; // Clear previous messages

  try {
    const response = await BrandingService.uploadLogo(file);
    if (response.data?.success && response.data?.logoUrl) {
      settings.logoUrl = response.data.logoUrl; // Update the displayed URL
      successMessage.value = 'Logo uploaded successfully!';
      selectedLogoFile.value = null; // Clear file input
    } else {
        logoUploadError.value = response.data?.error || 'Failed to upload logo. Unknown error.';
    }
  } catch (err) {
    console.error('Error uploading logo:', err);
    logoUploadError.value = err.response?.data?.error || 'Failed to upload logo. Please check file type/size and try again.';
  } finally {
    uploadingLogo.value = false;
     if (successMessage.value) {
       setTimeout(() => { successMessage.value = null; }, 5000);
     }
     // Don't clear error message immediately
  }
};

// Computed style for preview
const previewStyle = computed(() => ({
  backgroundColor: settings.backgroundColor || '#FFFFFF',
  '--preview-text-color': settings.textColor || '#000000', // Use CSS variable for text color
  color: settings.textColor || '#000000' // Set default text color for the card itself
}));

// Computed property for full logo URL
const fullLogoUrl = computed(() => {
  if (!settings.logoUrl) return null;
  
  // If it's already a full URL, return as-is
  if (settings.logoUrl.startsWith('http')) {
    return settings.logoUrl;
  }
  
  // Convert API URL to base URL and append the relative path
  const baseUrl = API_URL.endsWith('/api') ? API_URL.slice(0, -4) : API_URL;
  return baseUrl + settings.logoUrl;
});

</script>

<style scoped>
/* Add any component-specific styles here */
.v-label {
  margin-bottom: 8px;
  display: block;
  font-weight: 500;
}
</style> 