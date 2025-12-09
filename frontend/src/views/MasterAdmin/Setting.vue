<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">System Settings</h2>

    <div class="row g-4">
      <div class="col-md-12">
        <div class="settings-card">
          <h5 class="mb-4">General Settings</h5>

          <div v-if="loading" class="alert alert-info">Loading settings...</div>
          
          <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
          <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

          <form @submit.prevent="saveSettings" :class="{ 'disabled-form': loading || isSaving }">
            <fieldset :disabled="loading || isSaving">
                <div class="mb-3">
                  <label class="form-label">System Name</label>
                  <input type="text" class="form-control" v-model="settingsForm.systemName">
                </div>

                <div class="mb-3">
                  <label class="form-label">Organization Name</label>
                  <input type="text" class="form-control" v-model="settingsForm.organizationName">
                </div>

                <div class="mb-3">
                  <label class="form-label">Logo</label>
                  <input type="file" class="form-control" @change="handleFileUpload" accept="image/*" ref="logoFileInput">
                  <small class="text-muted">Recommended size: 200x60px</small>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Contact Email</label>
                  <input type="email" class="form-control" v-model="settingsForm.contactEmail">
                </div>

                <div class="mb-3">
                  <label class="form-label">Phone Number</label>
                  <input type="tel" class="form-control" v-model="settingsForm.phoneNumber">
                </div>

                <div class="mb-3">
                  <label class="form-label">Address</label>
                  <textarea class="form-control" rows="3" v-model="settingsForm.address"></textarea>
                </div>
            </fieldset>

           <div class="d-flex justify-content-end pt-3 mt-4 border-top">
                <button type="submit" class="btn btn-success" :disabled="loading || isSaving">
                    <i class="bi bi-save me-1"></i>
                    {{ isSaving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
// NOTE: Assuming the path is correct
import { useSettingsStore } from '../../stores/useSettingsStore'; 

import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- CONFIGURATION & API SETUP ---
const API_BASE_URL = 'http://localhost:8000/api';
const API_ENDPOINT = `${API_BASE_URL}/settings`;
const STORAGE_BASE_URL = 'http://localhost:8000/storage';

const apiClient = axios.create({
 baseURL: API_BASE_URL,
 headers: {
 'Authorization': `Bearer ${localStorage.getItem('authToken')}` 
 }
});

// --- INTERFACE (FIXED: Added logoPath) ---
// This interface defines the shape of the data in the local form state.
// We must include logoPath here to prevent the TypeScript error.
interface GeneralSettings {
 systemName: string;
 organizationName: string;
 contactEmail: string;
 phoneNumber: string;
 address: string;
logoPath: string; // FIX: Made logoPath mandatory in the local state for tracking
}

// --- STATE (FIXED: Initialized logoPath) ---
const settingsForm = ref<GeneralSettings>({
 systemName: '',
organizationName: '',
 contactEmail: '',
 phoneNumber: '',
 address: '',
 logoPath: '/logo.png' // FIX: Initialize it to prevent potential errors later
});

const { setSettings } = useSettingsStore();

const logoFile = ref<File | null>(null);
const logoFileInput = ref<HTMLInputElement | null>(null);
const isSaving = ref(false);
const loading = ref(true);
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

// --- LIFECYCLE ---
onMounted(() => {
 fetchSettings();
});

// --- METHODS ---
const fetchSettings = async () => {
 loading.value = true;
 errorMessage.value = null;
 try {
 const response = await apiClient.get(API_ENDPOINT); 
 
 const logoPath = response.data.logo 
 ? `${STORAGE_BASE_URL}/${response.data.logo}` 
: '/logo.png';

 const fetchedSettings: GeneralSettings = {
 systemName: response.data.systemName || '',
 organizationName: response.data.organizationName || '',
 contactEmail: response.data.contactEmail || '',
 phoneNumber: response.data.phoneNumber || '',
 address: response.data.address || '',
 logoPath: logoPath, 
};

 Object.assign(settingsForm.value, fetchedSettings); 
 setSettings(fetchedSettings); 
 
 } catch (error) {
 console.error('Error fetching settings:', error);
 errorMessage.value = 'Failed to load initial settings.';
 } finally {
 loading.value = false;
 }
};

const handleFileUpload = (event: Event) => {
 const target = event.target as HTMLInputElement;
 const file = target.files?.[0];
 if (file) {
 logoFile.value = file;
 }
};

const saveSettings = async () => {
 isSaving.value = true;
 successMessage.value = null;
errorMessage.value = null;
 
 try {
 const formData = new FormData();
 
 // FIX: Explicitly loop over GeneralSettings keys, excluding logoPath
 (Object.keys(settingsForm.value) as Array<keyof GeneralSettings>).forEach(key => {
 const val = settingsForm.value[key];
 if (key !== 'logoPath' && val !== undefined) { 
formData.append(key, val);
 }
 });

 if (logoFile.value) {
 formData.append('logo', logoFile.value);
 }
const response = await apiClient.post(API_ENDPOINT, formData, {});

 // 1. Prepare new data for the global store update
 const newGlobalSettings: Partial<GeneralSettings> = { 
 ...settingsForm.value, 
 };

 // 2. FIX: Update logoPath and local form state if a new file was uploaded
 if (logoFile.value) {
 const newPath = `${STORAGE_BASE_URL}/logos/${logoFile.value.name}`;
 newGlobalSettings.logoPath = newPath;
 // Crucial: Update the local form state's logoPath to reflect the new saved image
 settingsForm.value.logoPath = newPath;
 }
 
 // 3. Update global store, triggering immediate Navbar/Sidebar re-render
 setSettings(newGlobalSettings);

 successMessage.value = response.data.message || 'Settings saved successfully!';
 logoFile.value = null;
 if (logoFileInput.value) {
 logoFileInput.value.value = '';
 }
 
 } catch (error: any) {
 console.error('Error saving settings:', error);
 const msg = error.response?.data?.message || 'Failed to save settings. Check API connection.';
 errorMessage.value = msg;
 
  } finally {
 isSaving.value = false;
 setTimeout(() => {
   successMessage.value = null;
   errorMessage.value = null;
 }, 5000);
 }
};
</script>

<style scoped>
.disabled-form {
    opacity: 0.6;
    pointer-events: none;
}
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
.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}
.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}
</style>