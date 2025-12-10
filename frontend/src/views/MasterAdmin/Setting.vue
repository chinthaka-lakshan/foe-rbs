<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">System Settings</h2>

    <div class="row g-4">
      <div class="col-md-12">
        <div class="settings-card">
          <h5 class="mb-4">General Settings</h5>
          <form @submit.prevent="saveSettings">
            <div class="mb-3">
              <label class="form-label">System Name</label>
              <input type="text" class="form-control" v-model="settings.systemName">
            </div>

            <div class="mb-3">
              <label class="form-label">Organization Name</label>
              <input type="text" class="form-control" v-model="settings.organizationName">
            </div>

            <div class="mb-3">
              <label class="form-label">Logo</label>
              <input type="file" class="form-control" @change="handleFileUpload">
              <small class="text-muted">Recommended size: 200x60px</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Contact Email</label>
              <input type="email" class="form-control" v-model="settings.contactEmail">
            </div>

            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input type="tel" class="form-control" v-model="settings.phoneNumber">
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea class="form-control" rows="3" v-model="settings.address"></textarea>
            </div>

           <div class="d-flex justify-content-end pt-3 mt-4 border-top">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
            </div>
          </form>
        </div>
        </div>
      </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- INTERFACE ---
interface GeneralSettings {
  systemName: string;
  organizationName: string;
  contactEmail: string;
  phoneNumber: string;
  address: string;
}

// --- STATE (INITIAL DATA MATCHING THE IMAGE) ---
const settings = ref<GeneralSettings>({
  systemName: 'University Resources Booking System',
  organizationName: 'State University',
  contactEmail: 'admin@university.edu',
  phoneNumber: '+1 (555) 123-4567',
  address: '123 University Ave, Campus City, ST 12345'
});

// --- METHODS ---
const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    console.log('File uploaded:', file.name);
  }
};

const saveSettings = () => {
  console.log('General Settings saved:', settings.value);
  alert('General Settings Saved!');
};

// Removed saveBookingSettings and bookingSettings state
</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
  margin-left: 260px;
  padding: 20px;
}
@media (max-width: 768px) {
  .section {
    margin-left: 80px;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.section-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 24px;
}

.settings-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.settings-card h5 {
  color: #1e4449;
  font-weight: 600;
}

.form-label {
    font-weight: 500;
}

/* --- Color Overrides (Matching the bright green in the image) --- */
.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}
/* Removed unused status/info item styles */
</style>