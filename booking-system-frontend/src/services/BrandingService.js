import apiClient from './api'; // Corrected import path to use api.js

const BASE_URL = '/branding'; // Base path added to apiClient's baseUrl (/api)

export default {
  /**
   * Fetches the current branding settings for the authenticated user.
   * @returns {Promise<object>} The branding settings data.
   */
  getSettings() {
    return apiClient.get(BASE_URL);
  },

  /**
   * Updates the branding settings for the authenticated user.
   * @param {object} settingsData The settings data to update.
   * @returns {Promise<object>} The API response.
   */
  updateSettings(settingsData) {
    // Filter out null or empty string values before sending? Or let backend handle it.
    // For now, send everything provided.
    return apiClient.put(BASE_URL, settingsData);
  },

  /**
   * Uploads a logo file for the authenticated user.
   * @param {File} file The logo file to upload.
   * @returns {Promise<object>} The API response containing the new logo URL.
   */
  uploadLogo(file) {
    const formData = new FormData();
    formData.append('logoFile', file); // Key must match backend expectation

    return apiClient.post(`${BASE_URL}/logo`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
  },

  // TODO: Add method for uploading logo (e.g., uploadLogo(file))
}; 