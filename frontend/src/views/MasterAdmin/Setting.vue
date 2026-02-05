<template>
  <div class="layout-wrapper">
    <Navbar />
    <MasterAdminSidebar />
    
    <main class="content-body">
      <div class="container-fluid">
        <header class="page-header">
          <h2 class="section-title">System Configuration</h2>
          <p class="section-subtitle">Manage university branding and global operational parameters.</p>
        </header>

        <div class="settings-card">
          <div class="card-header-flex">
            <h5 class="m-0">General Settings</h5>
            <div v-if="logoPreview" class="logo-preview-box">
              <img :src="logoPreview" alt="System Logo" class="preview-img" />
              <button type="button" class="btn-remove-preview" @click="resetLogo">
                <i class="bi bi-x-circle-fill"></i>
              </button>
            </div>
          </div>

          <form @submit.prevent="saveSettings" class="settings-form mt-4">
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label">System Name</label>
                <input type="text" class="form-control shadow-none" v-model="settings.system_name" placeholder="e.g., University RBS">
              </div>
              <div class="col-md-6">
                <label class="form-label">Organization Name</label>
                <input type="text" class="form-control shadow-none" v-model="settings.organization_name" placeholder="e.g., KIU Sri Lanka">
              </div>
              
              <div class="col-md-12">
                <label class="form-label">Update Identity Logo</label>
                <div class="upload-wrapper">
                  <input type="file" class="form-control" @change="handleFileUpload" accept="image/*">
                  <div class="form-text text-muted">Recommended: Transparent PNG, 200x60px. New uploads replace the current logo.</div>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Contact Email</label>
                <input type="email" class="form-control shadow-none" v-model="settings.contact_email">
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control shadow-none" v-model="settings.phone_number">
              </div>
              <div class="col-md-12">
                <label class="form-label">Physical Address</label>
                <textarea class="form-control shadow-none" v-model="settings.address" rows="2"></textarea>
              </div>
            </div>

            <div class="form-footer mt-5">
              <button type="submit" class="btn btn-apply" :disabled="isLoading">
                <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-shield-check me-2"></i>
                {{ isLoading ? 'Finalizing...' : 'Save Configuration' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const settings = ref({
  system_name: '',
  organization_name: '',
  contact_email: '',
  phone_number: '',
  address: ''
});

const selectedFile = ref<File | null>(null);
const logoPreview = ref<string | null>(null);
const isLoading = ref(false);
const serverLogo = ref<string | null>(null); // Track original server image

// SystemSettings.vue

const fetchSettings = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/settings');
    settings.value = {
      system_name: res.data.site_name || '',
      organization_name: res.data.organization_name || '',
      contact_email: res.data.contact_email || '',
      phone_number: res.data.phone_number || '',
      address: res.data.address || ''
    };

    if (res.data.logo) {
      // Extract the filename from the path (e.g., "logos/abc.png" -> "abc.png")
      const filename = res.data.logo.split('/').pop();
      // Point to our new Gateway Proxy route
      logoPreview.value = `http://localhost:8000/api/settings/logo/${filename}`;
    }
  } catch (e) { 
    console.error("Fetch error:", e); 
  }
};
const handleFileUpload = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) {
    selectedFile.value = file;
    logoPreview.value = URL.createObjectURL(file); // Instant local blob preview
  }
};

const resetLogo = () => {
  selectedFile.value = null;
  logoPreview.value = serverLogo.value; // Revert to what is currently on the server
};

const saveSettings = async () => {
  isLoading.value = true;
  const formData = new FormData();
  
  // Flatten keys for standard backend processing
  Object.entries(settings.value).forEach(([k, v]) => formData.append(k, v));
  
  if (selectedFile.value) {
    formData.append('logo', selectedFile.value);
  }

  try {
    await axios.post('http://localhost:8000/api/settings', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    alert('System identity updated successfully.');
    await fetchSettings(); // Refresh preview with new server path
  } catch (e) {
    alert('Communication error. Verify API Gateway and Resource Service ports.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchSettings);
</script>

<style scoped>
.layout-wrapper { background: #f1f5f9; min-height: 100vh; overflow: hidden; display: flex; flex-direction: column; }

/* Structural separation to prevent overlap with the 260px sidebar */
.content-body { 
  margin-left: 260px; 
  padding: 50px 40px; 
  margin-top: 2px; 
  height: calc(100vh - 60px);
  overflow-y: auto;
  transition: all 0.3s ease;
}

.page-header { margin-bottom: 35px; }
.section-title { font-weight: 800; color: #1e293b; font-size: 1.75rem; }
.section-subtitle { color: #64748b; font-size: 0.95rem; }

/* Light card with crisp borders for identification */
.settings-card { 
  background: #fff; 
  border: 1px solid #e2e8f0; 
  border-radius: 16px; 
  padding: 40px; 
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); 
}

.card-header-flex { 
  display: flex; 
  justify-content: space-between; 
  align-items: center;
  border-bottom: 1px solid #f1f5f9; 
  padding-bottom: 20px; 
}

/* Image Preview Styles */
.logo-preview-box { position: relative; }
.preview-img { 
  height: 60px; 
  max-width: 220px; 
  object-fit: contain; 
  border: 1.5px solid #e2e8f0; 
  padding: 6px; 
  border-radius: 8px;
  background: #f8fafc;
}

.btn-remove-preview {
  position: absolute;
  top: -10px;
  right: -10px;
  background: white;
  border: none;
  color: #ef4444;
  font-size: 1.25rem;
  line-height: 1;
  padding: 0;
  border-radius: 50%;
  cursor: pointer;
}

.form-label { font-weight: 600; color: #475569; font-size: 0.88rem; margin-bottom: 8px; }

/* Professional Emerald Green Button */
.btn-apply { 
  background: #10b981; 
  color: #fff; 
  font-weight: 700; 
  padding: 12px 32px; 
  border: none; 
  border-radius: 10px;
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
  transition: all 0.2s ease;
}

.btn-apply:hover:not(:disabled) { 
  background: #059669; 
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
}

.form-footer { 
  display: flex; 
  justify-content: flex-end; 
  border-top: 1px solid #f1f5f9; 
  padding-top: 30px; 
}

@media (max-width: 768px) { 
  .content-body { margin-left: 80px; padding: 30px 20px; } 
}
</style>