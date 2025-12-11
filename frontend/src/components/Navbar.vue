<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid px-4">
      <a class="navbar-brand fw-bold" href="#">
        <span class="brand-text">University Resources</span>
      </a>

      <div class="d-flex align-items-center">
        <span class="me-3 text-muted">{{ userName }}</span>
        <button 
          class="btn btn-outline-danger btn-sm" 
          @click="handleLogout"
          :disabled="isLoggingOut"
        >
          <i class="bi bi-box-arrow-right me-1"></i>
          {{ isLoggingOut ? 'Logging Out...' : 'Logout' }}
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios'; // 🛑 REQUIRED: Import Axios for API calls

const router = useRouter();
const userName = ref(localStorage.getItem('userName') || 'User');
const isLoggingOut = ref(false); // State for button loading/disabling

const API_LOGOUT_URL = 'http://localhost:8000/api/logout';

// Helper to reliably get the token
const getAuthToken = (): string | null => {
    return localStorage.getItem('authToken') || 
           localStorage.getItem('auth_token') || 
           localStorage.getItem('token');
};

const handleLogout = async () => {
    const token = getAuthToken();
    isLoggingOut.value = true;

    // 1. Send request to the API Gateway to invalidate the token
    if (token) {
        try {
            await axios.post(
                API_LOGOUT_URL,
                {}, // Empty body for POST request
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    }
                }
            );
            // If the request succeeds (200, 204), the token is revoked.
            console.log('Server-side logout successful.');

        } catch (error: any) {
            // Log the error, but proceed with local logout anyway.
            // If the server is unreachable (401, 503 timeout), we must still clear the local token.
            console.error('Logout API call failed:', error.response?.data || error.message);
        }
    }

    // 2. Clear local storage and redirect (Always run this step)
    localStorage.clear();
    router.push('/login');
    isLoggingOut.value = false;
};
</script>

<style scoped>
/* ... (existing styles) ... */
/* Add disabled styling for the button if needed */
.btn-outline-danger:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}
</style>