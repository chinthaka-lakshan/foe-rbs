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

        <button type="submit" class="btn btn-primary w-100 mb-3" :disabled="!email || !password">Sign In</button>

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

const router = useRouter();
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const loginError = ref(''); // State to hold login error messages

// Define a common placeholder password for demonstration
const TEST_PASSWORD = 'password123';

const handleLogin = () => {
    loginError.value = ''; // Clear previous errors

    // 1. Master Admin Login
    if (email.value === 'masteradmin@university.edu' && password.value === TEST_PASSWORD) {
        localStorage.setItem('isAuthenticated', 'true');
        localStorage.setItem('userRole', 'master-admin');
        localStorage.setItem('userName', 'Master Admin');
        // Navigate to the Master Admin dashboard route
        router.push('/master-admin/dashboard'); 
        
    // 2. Regular Admin Login
    } else if (email.value === 'admin@university.edu' && password.value === TEST_PASSWORD) {
        localStorage.setItem('isAuthenticated', 'true');
        localStorage.setItem('userRole', 'admin');
        localStorage.setItem('userName', 'Admin User');
        // Navigate to the Regular Admin dashboard route
        router.push('/admin/dashboard'); 
        
    // 3. Regular User Login
    } else if (email.value === 'user@university.edu' && password.value === TEST_PASSWORD) {
        localStorage.setItem('isAuthenticated', 'true');
        localStorage.setItem('userRole', 'user');
        localStorage.setItem('userName', 'Regular User');
        // Navigate to the Regular User dashboard route
        router.push('/user/dashboard'); 

    // 4. Failed Login
    } else {
        localStorage.setItem('isAuthenticated', 'false');
        localStorage.removeItem('userRole');
        loginError.value = 'Invalid email or password.';
    }
};
</script>

<style scoped>
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