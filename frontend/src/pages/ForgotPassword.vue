<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="text-center mb-4">
        <h2 class="auth-title">Reset Password</h2>
        <p class="text-muted">{{ getStepDescription() }}</p>
      </div>

      <div v-if="showSuccessMessage" class="success-popup">
        Password reset successfully!
      </div>

      <form @submit.prevent="handleSubmit">
        <div v-if="step === 1" class="mb-3">
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

        <div v-if="step === 2" class="mb-3">
          <label for="otp" class="form-label">Enter OTP</label>
          <input
            type="text"
            class="form-control"
            id="otp"
            v-model="otp"
            required
            placeholder="Enter 6-digit OTP"
            maxlength="6"
          >
          <small class="text-muted">Check your email for the OTP code</small>
        </div>

        <div v-if="step === 3">
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <input
              type="password"
              class="form-control"
              id="newPassword"
              v-model="newPassword"
              required
              placeholder="Enter new password"
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
              placeholder="Confirm new password"
            >
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">
          {{ getButtonText() }}
        </button>

        <div class="text-center">
          <router-link to="/login" class="text-decoration-none">
            <i class="bi bi-arrow-left me-2"></i>Back to Login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const step = ref(1);
const email = ref('');
const otp = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showSuccessMessage = ref(false);

const getStepDescription = () => {
  if (step.value === 1) return 'Enter your email to receive reset instructions';
  if (step.value === 2) return 'Enter the OTP sent to your email';
  return 'Enter your new password';
};

const getButtonText = () => {
  if (step.value === 1) return 'Send OTP';
  if (step.value === 2) return 'Verify OTP';
  return 'Save Password';
};

const handleSubmit = () => {
  if (step.value === 1) {
    alert(`OTP has been sent to ${email.value}`);
    step.value = 2;
  } else if (step.value === 2) {
    if (otp.value.length === 6) {
      step.value = 3;
    } else {
      alert('Please enter a valid 6-digit OTP');
    }
  } else if (step.value === 3) {
    if (newPassword.value !== confirmPassword.value) {
      alert('Passwords do not match');
      return;
    }
    if (newPassword.value.length < 6) {
      alert('Password must be at least 6 characters');
      return;
    }

    showSuccessMessage.value = true;

    setTimeout(() => {
      showSuccessMessage.value = false;
      router.push('/login');
    }, 2000);
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
  position: relative;
}

.auth-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 8px;
}

.text-muted {
  color: #6c757d;
  margin-bottom: 0;
}

.form-label {
  font-weight: 500;
  color: #1e4449;
  margin-bottom: 8px;
}

.form-control {
  padding: 12px;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s ease;
}

.form-control:focus {
  border-color:  #4BB66D;
  box-shadow: 0 0 0 0.2rem rgba(38, 213, 22, 0.25);
  outline: none;
}

.btn-primary {
  background-color:  #4BB66D;
  border-color:  #4BB66D;
  font-weight: 500;
  padding: 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
}

.btn-primary:hover {
  background-color:  #3f975b;
  border-color:  #3f975b;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(38, 213, 22, 0.3);
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.w-100 {
  width: 100%;
}

.text-center {
  text-align: center;
}

.text-decoration-none {
  text-decoration: none;
  color:  #4BB66D;
  font-weight: 500;
  transition: color 0.3s ease;
}

.text-decoration-none:hover {
  color:   #3f975b;
}

.success-popup {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  background-color:  #4BB66D;
  color: white;
  padding: 16px 32px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(38, 213, 22, 0.4);
  font-weight: 500;
  z-index: 1000;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

small {
  display: block;
  margin-top: 4px;
  font-size: 14px;
}
</style>
