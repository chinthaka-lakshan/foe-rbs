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
            :disabled="isLoading"
          >
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input
              :type="isPasswordVisible ? 'text' : 'password'"
              class="form-control no-browser-icon" 
              id="password"
              v-model="password"
              required
              placeholder="Enter your password"
              :disabled="isLoading"
            >
            <button 
              class="btn btn-outline-secondary toggle-password" 
              type="button" 
              @click="togglePasswordVisibility"
              :disabled="isLoading"
            >
              <i :class="isPasswordVisible ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
            </button>
          </div>
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

        <button type="submit" class="btn btn-primary w-100 mb-2" :disabled="!email || !password || isLoading">
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Signing In...' : 'Sign In' }}
        </button>

        <button type="button" @click="handleGuestLogin" class="btn btn-outline-success w-100 mb-3" :disabled="isLoading">
            Sign In as Guest
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
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { reportStore } from '../store/reportStore';
import { resourceStore } from '../store/resourceStore';
import { userStore } from '../store/userStore';
import { systemStore } from '../store/systemSettings';

const router = useRouter();
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const loginError = ref(''); 
const isLoading = ref(false);

// Toggle Visibility logic
const isPasswordVisible = ref(false);
const togglePasswordVisibility = () => {
  isPasswordVisible.value = !isPasswordVisible.value;
};

onMounted(() => {
  document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
  document.body.style.overflow = 'auto';
});

const API_URL = 'http://localhost:8000/api/login';

const handleLogin = async () => {
    loginError.value = '';
    isLoading.value = true;
    try {
        const response = await axios.post(API_URL, {
            email: email.value,
            password: password.value,
            remember: rememberMe.value
        });
        const data = response.data;

        if (data.token) {
            processLoginSuccess(data);
        } else {
            loginError.value = data.message || 'Login failed.';
        }
    } catch (error) {
        loginError.value = 'Network error or invalid credentials.';
    } finally {
        isLoading.value = false;
    }
};

const handleGuestLogin = async () => {
    loginError.value = '';
    isLoading.value = true;
    try {
        const response = await axios.post('http://localhost:8000/api/guest-login');
        const data = response.data;

        if (data.token) {
            processLoginSuccess(data);
        } else {
            loginError.value = data.message || 'Guest login failed.';
        }
    } catch (error) {
        loginError.value = 'Network error during guest login.';
    } finally {
        isLoading.value = false;
    }
};

const processLoginSuccess = (data) => {
    // 1. Set authentication state
    localStorage.setItem('isAuthenticated', 'true');
    localStorage.setItem('authToken', data.token);
    localStorage.setItem('userName', data.user.name); 
    localStorage.setItem('userEmail', data.user.email);
    localStorage.setItem('userId', data.user.id.toString());
    const role = data.roles && data.roles.length > 0 ? data.roles[0] : 'user';
    localStorage.setItem('userRole', role);

    // 2. TRIGGER BACKGROUND DATA SYNC
    if (role === 'Master Admin') {
        systemStore.loadSettings(); 
        userStore.fetchUsers();
        resourceStore.fetchAll();
        reportStore.fetchAllReports();
        router.push('/master-admin/dashboard');
    } 
    else if (role === 'Admin') {
        systemStore.loadSettings();
        router.push('/admin/dashboard');
    } 
    else {
        router.push('/user/dashboard');
    }
};
</script>

<style scoped>
/* 1. HIDE THE BROWSER DEFAULT REVEAL ICON (Edge/Chrome) */
.no-browser-icon::-ms-reveal,
.no-browser-icon::-ms-clear {
  display: none;
}

/* Base layout styles - SCROLL REMOVED */
.auth-container {
  min-height: 100vh;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e4449 0%, #4BB66D 100%);
  padding: 20px;
  overflow: hidden;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.auth-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  padding: 40px;
  width: 100%;
  max-width: 450px;
  max-height: 90vh;
  overflow-y: auto;
}

/* Custom scrollbar for card (optional - looks better) */
.auth-card::-webkit-scrollbar {
  width: 6px;
}

.auth-card::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.auth-card::-webkit-scrollbar-thumb {
  background: #4BB66D;
  border-radius: 10px;
}

.auth-card::-webkit-scrollbar-thumb:hover {
  background: #1e4449;
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

.btn-primary:hover:not(:disabled) {
  background-color: #3f975b;
  border-color: #3f975b;
}

.btn-outline-success {
  color: #4BB66D;
  border-color: #4BB66D;
}

.btn-outline-success:hover:not(:disabled) {
  background-color: #4BB66D;
  color: white;
}

.toggle-password {
  border-color: #dee2e6;
  color: #6c757d;
  background-color: #fff;
}

.toggle-password:hover:not(:disabled) {
  background-color: #f8f9fa;
  color: #4BB66D;
}

.form-control:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.2rem rgba(75, 182, 109, 0.25);
}

.input-group:focus-within .form-control,
.input-group:focus-within .btn {
  border-color: #4BB66D;
}

.auth-logo {
  max-height: 120px; 
  width: auto;
}

.alert-danger {
  color: #842029;
  background-color: #f8d7da;
  border-color: #f5c2c7;
  padding: 1rem;
  margin-bottom: 1rem;
  border-radius: 0.25rem;
}

/* Responsive for mobile */
@media (max-width: 576px) {
  .auth-card {
    padding: 30px 20px;
    max-height: 95vh;
  }
}
</style>