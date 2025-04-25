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

  // TODO: Add method for uploading logo (e.g., uploadLogo(file))
}; 