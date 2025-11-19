<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="text-center mb-4">
        <img src="../assets/logo.png" alt="University Logo" class="auth-logo mb-3">
        <h2 class="auth-title">FOE</h2>
        <h4 class="auth-title">Resource Booking System</h4>
        <p class="text-muted">Sign in to your account</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input
            type="email"
            class="form-control"
            id="email"
            v-model="email"
            required
            placeholder="Enter your email"
          >
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input
            type="password"
            class="form-control"
            id="password"
            v-model="password"
            required
            placeholder="Enter your password"
          >
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" v-model="rememberMe">
            <label class="form-check-label" for="remember">
              Remember me
            </label>
          </div>
          <router-link to="/forgot-password" class="text-decoration-none">Forgot Password?</router-link>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3" :disabled="!email || !password || isLoading">
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Signing In...' : 'Sign In' }}
        </button>

        <div v-if="loginError" class="alert alert-danger text-center" role="alert">
            {{ loginError }}
        </div>

        <div class="text-center">
          <span class="text-muted">Don't have an account? </span>
          <router-link to="/register" class="text-decoration-none">Register</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios'; // <-- ADDED AXIOS

const router = useRouter();
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const loginError = ref(''); 
const isLoading = ref(false); // New state for loading indicator

// Assuming API Gateway is running on port 8000 and exposes the /login endpoint
const API_URL = 'http://localhost:8000/api/login'; // <-- POINTING TO API GATEWAY

const handleLogin = async () => {
    loginError.value = '';
    isLoading.value = true;
    
    const credentials = {
        email: email.value,
        password: password.value,
        remember: rememberMe.value
    };

    try {
        // 2. Make API call to the backend login endpoint
        const response = await axios.post(API_URL, credentials);
        const data = response.data;

        // Since the backend proxy returns only JSON body on success/failure, 
        // a successful HTTP status (200) means login was successful.
        if (data.token) {
            // 3. Store authentication data and roles
            localStorage.setItem('isAuthenticated', 'true');
            localStorage.setItem('authToken', data.token);
            localStorage.setItem('userName', data.user.name); // Assuming 'user' object has a 'name' field
            
            // Get the first role name for routing
            const role = data.roles && data.roles.length > 0 ? data.roles[0] : 'user';
            localStorage.setItem('userRole', role);

            // 4. Determine destination based on role
            switch (role) {
                case 'Master Admin':
                    router.push('/master-admin/dashboard');
                    break;
                case 'admin':
                    router.push('/admin/dashboard');
                    break;
                case 'User':
                default:
                    router.push('/user/dashboard');
                    break;
            }
        } else if (data.message) {
             // Handle soft error messages that might be returned in a 200 response body (due to backend proxy logic)
             loginError.value = data.message;
        } else {
             loginError.value = 'Login failed. Invalid response structure.';
        }

    } catch (error) {
        localStorage.setItem('isAuthenticated', 'false');
        localStorage.removeItem('authToken');
        
        if (axios.isAxiosError(error) && error.response) {
            // If the backend returns a non-2xx status (which it should, but the current code proxies only JSON)
            const status = error.response.status;
            
            // Check if the response body contains a specific error message from the microservice
            const errorMessage = error.response.data?.message || 'Login failed.';

            if (status === 401) {
                loginError.value = errorMessage;
            } else if (status === 403) {
                 loginError.value = errorMessage;
            } else if (status === 503) {
                 loginError.value = 'Service Unavailable. Cannot reach authentication service.';
            } else {
                loginError.value = `Server Error (${status}): ${errorMessage}`;
            }
        } else {
            // Handle network errors (e.g., API Gateway is down)
            loginError.value = 'Network error. Could not reach the API Gateway.';
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<style scoped>
/* Scoped styles remain unchanged */
.auth-container {
min-height: 100vh;
display: flex;
align-items: center;
justify-content: center;
background: linear-gradient(135deg, #1e4449 0%, #4BB66D 100%);
padding: 20px;
}

.auth-card {
background: white;
border-radius: 12px;
box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
padding: 40px;
width: 100%;
max-width: 450px;
}

.auth-title {
color: #1e4449;
font-weight: 600;
margin-bottom: 8px;
}

.btn-primary {
background-color: #4BB66D;
border-color: #4BB66D;
font-weight: 500;
padding: 12px;
}

.btn-primary:hover {
background-color: #3f975b; 
border-color: #3f975b;
}

.form-control:focus {
border-color: #26d516;
box-shadow: 0 0 0 0.2rem rgba(38, 213, 22, 0.25);
}

a {
color: #4BB66D;
}

a:hover {
color: #26d516;
}


.auth-logo {
max-height: 120px; 
width: auto;
}
/* Style for the error message */
.alert-danger {
color: #842029;
background-color: #f8d7da;
border-color: #f5c2c7;
padding: 1rem 1rem;
margin-bottom: 1rem;
border: 1px solid transparent;
border-radius: 0.25rem;
}
</style>