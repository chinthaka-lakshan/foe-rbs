<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-4">
      <a class="navbar-brand fw-bold" href="#">
        <span class="brand-text">Resource Booking System</span>
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
import axios from 'axios';

const router = useRouter();
const userName = ref(localStorage.getItem('userName') || 'User');
const isLoggingOut = ref(false);

const API_LOGOUT_URL = 'http://localhost:8000/api/logout';

const getAuthToken = (): string | null => {
    return localStorage.getItem('authToken') || 
           localStorage.getItem('auth_token') || 
           localStorage.getItem('token');
};

const handleLogout = async () => {
    const token = getAuthToken();
    isLoggingOut.value = true;

    if (token) {
        try {
            await axios.post(
                API_LOGOUT_URL,
                {},
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    }
                }
            );
            console.log('Server-side logout successful.');
        } catch (error: any) {
            console.error('Logout API call failed:', error.response?.data || error.message);
        }
    }

    localStorage.clear();
    router.push('/login');
    isLoggingOut.value = false;
};
</script>

<style scoped>
/* Add padding to body to prevent content from hiding under fixed navbar */
:global(body) {
  padding-top: 80px; /* Add space for fixed navbar */
}

/* Navbar fix කිරීමේ styles */
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1030;
  height: 70px; /* Fixed height for navbar */
  background-color: white !important;
  box-shadow: 0 2px 4px rgba(0,0,0,.1) !important;
}

/* Container styles */
.container-fluid {
  height: 100%;
  display: flex;
  align-items: center;
}

/* Brand text styles */
.brand-text {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
}

/* Username styles */
.text-muted {
  color: #6c757d !important;
  font-size: 0.95rem;
}

/* Logout button styles */
.btn-outline-danger:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.btn-outline-danger {
  transition: all 0.2s ease;
}

/* Responsive styles for mobile devices */
@media (max-width: 768px) {
  :global(body) {
    padding-top: 60px; /* Smaller padding for mobile */
  }
  
  .navbar {
    height: 60px; /* Smaller height for mobile */
  }
  
  .brand-text {
    font-size: 1rem; /* Smaller font for mobile */
  }
  
  .text-muted {
    font-size: 0.85rem; /* Smaller username font for mobile */
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
}

/* Optional: Add animation for navbar */
.navbar {
  transition: all 0.3s ease;
}

/* Optional: Add shadow effect when scrolling (if you want to enhance) */
.navbar.scrolled {
  box-shadow: 0 4px 10px rgba(0,0,0,.15) !important;
}
</style>