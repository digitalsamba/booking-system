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
            <!-- Logo Section (Improved with exclusive options) -->
            <v-col cols="12">
              <v-label class="text-h6 mb-3">Logo Configuration</v-label>
              
              <!-- Current Logo Preview -->
              <v-card v-if="fullLogoUrl" outlined class="mb-4">
                <v-card-text class="d-flex align-center">
                  <v-img
                    :src="fullLogoUrl"
                    :alt="'Current Logo'"
                    max-height="80"
                    max-width="200"
                    contain
                    class="mr-4"
                    style="border: 1px solid #eee; background-color: #f9f9f9;"
                  ></v-img>
                  <div>
                    <div class="text-subtitle2 font-weight-bold">Current Logo</div>
                    <div class="text-caption text-medium-emphasis">
                      Source: {{ logoSource }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ currentLogoUrl }}
                    </div>
                  </div>
                </v-card-text>
              </v-card>
              
              <v-alert v-else type="info" variant="tonal" dense class="mb-4">
                No logo currently set. Choose an option below to add one.
              </v-alert>

              <!-- Logo Method Selection -->
              <v-radio-group 
                v-model="logoMethod" 
                @update:model-value="handleLogoMethodChange"
                class="mb-4"
              >
                <template #label>
                  <div class="text-subtitle1 font-weight-medium">Choose Logo Method</div>
                </template>
                <v-radio label="Upload a file from my computer" value="upload"></v-radio>
                <v-radio label="Use a URL from the internet" value="url"></v-radio>
              </v-radio-group>

              <!-- URL Input (only shown when URL method selected) -->
              <v-expand-transition>
                <div v-if="logoMethod === 'url'">
                  <v-text-field
                    v-model="logoUrlInput"
                    label="Logo URL"
                    placeholder="https://example.com/your-logo.png"
                    outlined
                    dense
                    hint="Enter a complete URL to your logo image (JPG, PNG, GIF, SVG)"
                    persistent-hint
                    class="mb-3"
                    @input="handleUrlInput"
                    :error-messages="urlError ? [urlError] : []"
                  >
                    <template #append>
                      <v-btn 
                        v-if="logoUrlInput && logoUrlInput !== settings.logoUrl"
                        @click="applyUrlLogo"
                        size="small"
                        color="primary"
                        variant="text"
                        :loading="applyingUrl"
                      >
                        Apply
                      </v-btn>
                    </template>
                  </v-text-field>
                </div>
              </v-expand-transition>

              <!-- File Upload (only shown when upload method selected) -->
              <v-expand-transition>
                <div v-if="logoMethod === 'upload'">
                  <v-file-input
                    v-model="selectedLogoFile"
                    label="Select Logo File"
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
                     Max 5MB. Supported formats: JPG, PNG, GIF, SVG.
                  </div>

                  <!-- Logo Upload Error -->
                  <v-alert v-if="logoUploadError" type="error" dense outlined class="mt-2 mb-4">
                    {{ logoUploadError }}
                  </v-alert>
                </div>
              </v-expand-transition>

              <!-- Remove Logo Option -->
              <div v-if="fullLogoUrl" class="mt-2 mb-4">
                <v-btn 
                  @click="removeLogo"
                  color="error"
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-delete"
                  :loading="removingLogo"
                >
                  Remove Current Logo
                </v-btn>
              </div>
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
                Save Color & Style Settings
              </v-btn>
            </v-col>
          </template>
        </v-row>
      </v-form>

      <!-- Preview Section -->
      <v-divider class="my-6"></v-divider>
      <h3 class="mb-4">Live Preview</h3>
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
      </v-card>

    </v-card>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import BrandingService from '@/services/BrandingService';
import { API_URL } from '@/config';

const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const successMessage = ref(null);
const selectedLogoFile = ref(null);
const uploadingLogo = ref(false);
const logoUploadError = ref(null);
const removingLogo = ref(false);

// Logo method selection
const logoMethod = ref('url'); // 'url' or 'upload'
const logoUrlInput = ref('');
const urlError = ref(null);
const applyingUrl = ref(false);

// Default settings structure
const settings = reactive({
  _id: null,
  userId: null,
  logoUrl: '',
  primaryColor: '#1976D2',
  secondaryColor: '#424242',
  backgroundColor: '#FFFFFF',
  textColor: '#000000',
  fontFamily: '',
  customCss: '',
});

// Computed properties for logo management
const currentLogoUrl = computed(() => {
  return settings.logoUrl || 'No logo set';
});

const logoSource = computed(() => {
  if (!settings.logoUrl) return 'None';
  if (settings.logoUrl.startsWith('http')) return 'External URL';
  if (settings.logoUrl.startsWith('/uploads/')) return 'Uploaded File';
  return 'URL';
});

const fullLogoUrl = computed(() => {
  if (!settings.logoUrl) return null;
  
  if (settings.logoUrl.startsWith('http')) {
    return settings.logoUrl;
  }
  
  const baseUrl = API_URL.endsWith('/api') ? API_URL.slice(0, -4) : API_URL;
  return baseUrl + settings.logoUrl;
});

// Fetch settings when component mounts
onMounted(async () => {
  loading.value = true;
  error.value = null;
  successMessage.value = null;
  logoUploadError.value = null;
  urlError.value = null;
  
  try {
    const response = await BrandingService.getSettings();
    const fetchedData = response.data?.data ?? response.data ?? null;

    if (fetchedData && typeof fetchedData === 'object') {
      settings.logoUrl = fetchedData.logoUrl ?? '';
      settings.primaryColor = fetchedData.primaryColor ?? '#1976D2';
      settings.secondaryColor = fetchedData.secondaryColor ?? '#424242';
      settings.backgroundColor = fetchedData.backgroundColor ?? '#FFFFFF';
      settings.textColor = fetchedData.textColor ?? '#000000';
      settings.fontFamily = fetchedData.fontFamily ?? '';
      settings.customCss = fetchedData.customCss ?? '';

      // Set initial values and method
      logoUrlInput.value = settings.logoUrl;
      logoMethod.value = settings.logoUrl.startsWith('/uploads/') ? 'upload' : 'url';
    } else {
      if (response.data?.success) {
        console.log('API reported success, but no branding data found');
      } else {
        console.log('No existing branding data found, using defaults.');
      }
    }

  } catch (err) {
    if (err.response && err.response.status === 404) {
      error.value = 'No branding settings found. Using default values. Save to create settings.';
    } else {
      console.error('Error fetching branding settings:', err);
      const errorMsg = err.response?.data?.data?.error || err.response?.data?.error || 'Failed to load branding settings. Please try again.';
      error.value = errorMsg;
    }
  } finally {
    loading.value = false;
  }
});

// Handle logo method change
const handleLogoMethodChange = (newMethod) => {
  selectedLogoFile.value = null;
  logoUploadError.value = null;
  urlError.value = null;
  
  if (newMethod === 'url') {
    logoUrlInput.value = settings.logoUrl;
  }
};

// Handle URL input
const handleUrlInput = () => {
  urlError.value = null;
  if (logoUrlInput.value && !logoUrlInput.value.startsWith('http')) {
    urlError.value = 'URL must start with http:// or https://';
  }
};

// Apply URL logo
const applyUrlLogo = async () => {
  if (!logoUrlInput.value || urlError.value) return;
  
  applyingUrl.value = true;
  try {
    await BrandingService.updateSettings({ logoUrl: logoUrlInput.value });
    settings.logoUrl = logoUrlInput.value;
    successMessage.value = 'Logo URL updated successfully!';
    setTimeout(() => { successMessage.value = null; }, 5000);
  } catch (err) {
    console.error('Error updating logo URL:', err);
    error.value = err.response?.data?.error || 'Failed to update logo URL. Please try again.';
  } finally {
    applyingUrl.value = false;
  }
};

// Save settings (for colors, fonts, CSS - logo is handled separately)
const saveSettings = async () => {
  saving.value = true;
  error.value = null;
  successMessage.value = null;

  const { _id, userId, logoUrl, ...updateData } = settings;

  try {
    await BrandingService.updateSettings(updateData);
    successMessage.value = 'Color and style settings saved successfully!';
  } catch (err) {
    console.error('Error saving branding settings:', err);
    error.value = err.response?.data?.error || 'Failed to save settings. Please try again.';
  } finally {
    saving.value = false;
    if (successMessage.value) {
      setTimeout(() => { successMessage.value = null; }, 5000);
    }
  }
};

// Handle Logo Upload
const handleLogoUpload = async () => {
  if (!selectedLogoFile.value || !selectedLogoFile.value[0]) {
    return;
  }

  const file = selectedLogoFile.value[0];
  uploadingLogo.value = true;
  logoUploadError.value = null;
  successMessage.value = null;

  try {
    const response = await BrandingService.uploadLogo(file);
    if (response.data?.success && response.data?.logoUrl) {
      settings.logoUrl = response.data.logoUrl;
      logoUrlInput.value = response.data.logoUrl;
      successMessage.value = 'Logo uploaded successfully!';
      selectedLogoFile.value = null;
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
  }
};

// Remove logo
const removeLogo = async () => {
  removingLogo.value = true;
  try {
    await BrandingService.updateSettings({ logoUrl: '' });
    settings.logoUrl = '';
    logoUrlInput.value = '';
    successMessage.value = 'Logo removed successfully!';
    setTimeout(() => { successMessage.value = null; }, 5000);
  } catch (err) {
    console.error('Error removing logo:', err);
    error.value = err.response?.data?.error || 'Failed to remove logo. Please try again.';
  } finally {
    removingLogo.value = false;
  }
};

// Computed style for preview
const previewStyle = computed(() => ({
  backgroundColor: settings.backgroundColor || '#FFFFFF',
  '--preview-text-color': settings.textColor || '#000000',
  color: settings.textColor || '#000000'
}));

</script>

<style scoped>
.v-label {
  margin-bottom: 8px;
  display: block;
  font-weight: 500;
}

.v-radio-group :deep(.v-selection-control-group) {
  margin-top: 8px;
}

.v-expand-transition-enter-active,
.v-expand-transition-leave-active {
  transition: all 0.3s ease;
}
</style>
