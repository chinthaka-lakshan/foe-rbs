<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="text-center mb-4">
        <img src="../assets/logo.png" alt="University Logo" class="auth-logo mb-3">
        <h2 class="auth-title">FOE</h2>
        <h4 class="auth-title">Resource Booking System</h4>
      </div>

      <form @submit.prevent="handleRegister">
        <div v-if="error" class="alert alert-danger" role="alert">
            {{ error }}
        </div>
        
        <div class="mb-3">
          <label for="fullName" class="form-label">Full Name</label>
          <input
            type="text"
            class="form-control"
            id="fullName"
            v-model="fullName"
            required
            placeholder="Enter your full name"
            :disabled="isLoading"
          >
        </div>

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
          <input
            type="password"
            class="form-control"
            id="password"
            v-model="password"
            required
            placeholder="Create a password"
            :disabled="isLoading"
          >
        </div>

        <div class="mb-3">
          <label for="confirmPassword" class="form-label">Confirm Password</label>
          <input
            type="password"
            class="form-control"
            id="confirmPassword"
            v-model="confirmPassword"
            required
            placeholder="Confirm your password"
            :disabled="isLoading"
          >
        </div>

        <div class="mb-3 form-check">
          <input class="form-check-input" type="checkbox" id="terms" v-model="acceptTerms" required :disabled="isLoading">
          <label class="form-check-label" for="terms">
            I agree to the terms and conditions
          </label>
        </div>

        <button 
            type="submit" 
            class="btn btn-primary w-100 mb-3"
            :disabled="isLoading || !acceptTerms" >
            <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Registering...' : 'Create Account' }}
        </button>

        <div class="text-center">
          <span class="text-muted">Already have an account? </span>
          <router-link to="/login" class="text-decoration-none">Sign In</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';

// !!! IMPORTANT: REPLACE THIS WITH YOUR ACTUAL LARAVEL API GATEWAY URL !!!
const API_URL = 'http://localhost:8000/api/users'; 

const router = useRouter();

// Form Data State
const fullName = ref('');
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const acceptTerms = ref(false);

// UI/Request State
const isLoading = ref(false);
const error = ref<string | null>(null);

const handleRegister = async () => {
  // 1. Reset state and basic client-side validation
  error.value = null;
  if (password.value !== confirmPassword.value) {
    error.value = 'Passwords do not match.';
    return;
  }
  if (!acceptTerms.value) {
    error.value = 'You must accept the terms and conditions.';
    return;
  }

  // 2. Prepare payload
  const payload = {
    // 'name' maps to the 'name' field required by your Laravel controller
    name: fullName.value,
    email: email.value,
    password: password.value,
  };

  isLoading.value = true;

  try {
    // 3. Send POST request to the Laravel API Gateway
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    // 4. Handle response statuses
    if (response.ok) { // Status codes 200-299 (including your 201 Created)
      console.log('Registration successful:', data);
      // Use a more subtle notification system in a real app (like a toast)
      alert('Registration successful! Please login.');
      router.push('/login');
    } else {
      // Handle Laravel Validation Errors (Status 422)
      if (response.status === 422 && data.errors) {
        // Example: If email validation fails, display the first error for that field
        const validationErrors = data.errors;
        if (validationErrors.email) {
            error.value = validationErrors.email[0];
        } else if (validationErrors.name) {
            error.value = validationErrors.name[0];
        } else if (validationErrors.password) {
            error.value = validationErrors.password[0];
        } else {
            error.value = 'Validation failed. Check your input.';
        }
      } 
      // Handle Gateway Timeout/Service Unavailable (503 from your gateway)
      else if (response.status === 503 && data.message) {
          error.value = data.message;
      }
      // General API error
      else {
        // Fallback for any other error status code
        error.value = data.message || `Registration failed with status: ${response.status}`;
      }
      console.error('Registration failed:', data);
    }

  } catch (e) {
    // 5. Handle Network or CORS errors
    console.error('Network or connection error:', e);
    error.value = 'Could not connect to the server. Please check your network and API URL.';
  } finally {
    isLoading.value = false;
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
  background-color:#4BB66D;
  border-color: #4BB66D;
  font-weight: 500;
  padding: 12px;
  
}

.btn-primary:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.form-control:focus {
  border-color: #4BB66D;
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
</style>