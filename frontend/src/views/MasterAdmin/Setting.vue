<template>

  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">System Settings</h2>

    <div class="row g-4">
      <div class="col-md-8">
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

            <button type="submit" class="btn btn-success">
              <i class="bi bi-save me-1"></i>Save Changes
            </button>
          </form>
        </div>

        <div class="settings-card mt-4">
          <h5 class="mb-4">Booking Settings</h5>
          <form>
            <div class="mb-3">
              <label class="form-label">Maximum Booking Duration (hours)</label>
              <input type="number" class="form-control" v-model="bookingSettings.maxDuration" min="1" max="24">
            </div>

            <div class="mb-3">
              <label class="form-label">Booking Advance Notice (days)</label>
              <input type="number" class="form-control" v-model="bookingSettings.advanceNotice" min="1" max="90">
            </div>

            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" v-model="bookingSettings.autoApprove" id="autoApprove">
                <label class="form-check-label" for="autoApprove">
                  Auto-approve bookings
                </label>
              </div>
            </div>

            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" v-model="bookingSettings.emailNotifications" id="emailNotifications">
                <label class="form-check-label" for="emailNotifications">
                  Send email notifications
                </label>
              </div>
            </div>

            <button type="submit" class="btn btn-success">
              <i class="bi bi-save me-1"></i>Save Booking Settings
            </button>
          </form>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <h5 class="mb-4">System Status</h5>
          <div class="status-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>System Health</span>
              <span class="badge bg-success">Online</span>
            </div>
          </div>
          <div class="status-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>Database</span>
              <span class="badge bg-success">Connected</span>
            </div>
          </div>
          <div class="status-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>Email Service</span>
              <span class="badge bg-success">Active</span>
            </div>
          </div>
          <div class="status-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>Storage</span>
              <span class="badge bg-warning">75% Used</span>
            </div>
          </div>
        </div>

        <div class="settings-card mt-4">
          <h5 class="mb-4">Quick Actions</h5>
          <div class="d-grid gap-2">
            <button class="btn btn-outline-primary">
              <i class="bi bi-arrow-clockwise me-1"></i>Clear Cache
            </button>
            <button class="btn btn-outline-warning">
              <i class="bi bi-download me-1"></i>Backup Database
            </button>
            <button class="btn btn-outline-danger">
              <i class="bi bi-trash me-1"></i>Clear Logs
            </button>
          </div>
        </div>

        <div class="settings-card mt-4">
          <h5 class="mb-4">System Info</h5>
          <div class="info-item">
            <small class="text-muted">Version</small>
            <p class="mb-2">1.0.0</p>
          </div>
          <div class="info-item">
            <small class="text-muted">Last Updated</small>
            <p class="mb-2">October 28, 2025</p>
          </div>
          <div class="info-item">
            <small class="text-muted">Uptime</small>
            <p class="mb-0">30 days, 12 hours</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const settings = ref({
  systemName: 'University Resources Booking System',
  organizationName: 'State University',
  contactEmail: 'admin@university.edu',
  phoneNumber: '+1 (555) 123-4567',
  address: '123 University Ave, Campus City, ST 12345'
});

const bookingSettings = ref({
  maxDuration: 4,
  advanceNotice: 7,
  autoApprove: false,
  emailNotifications: true
});

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    console.log('File uploaded:', file.name);
  }
};

const saveSettings = () => {
  console.log('Settings saved:', settings.value);
};
</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
  margin-left: 260px;
}
@media (max-width: 768px) {
  /* When the sidebar collapses, reduce the margin to 70px (Collapsed Sidebar Width) */
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

.status-item,
.info-item {
  padding: 12px 0;
  border-bottom: 1px solid #e9ecef;
}

.status-item:last-child,
.info-item:last-child {
  border-bottom: none;
}

.btn-success {
  background-color: #26d516;
  border-color: #26d516;
}

.btn-success:hover {
  background-color: #22b913;
  border-color: #22b913;
}

.form-check-input:checked {
  background-color: #26d516;
  border-color: #26d516;
}
</style>
