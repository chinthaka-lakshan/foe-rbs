<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Add New Resource</h2>
    </div>

    <div class="card p-4 shadow-sm">
      <form @submit.prevent="handleSave">
        <div class="row g-4">
          <div class="col-md-6">
            <label for="resourceName" class="form-label fw-bold">Resource Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="resourceName" 
              v-model="newResource.name" 
              required
              placeholder="e.g., Conference Room A 301"
            >
          </div>

          <div class="col-md-6">
            <label for="locationName" class="form-label fw-bold">Location Name</label>
            <input 
              type="text" 
              class="form-control" 
              id="locationName" 
              v-model="newResource.location"
              placeholder="e.g., Building C, Floor 2"
            >
          </div>

          <div class="col-md-6">
            <label for="resourceCategory" class="form-label fw-bold">Resource Category <span class="text-danger">*</span></label>
            <select class="form-select" id="resourceCategory" v-model="newResource.category" required>
              <option value="" disabled>Select a Category</option>
              <option value="Academic Space">Academic Space</option>
              <option value="Medical & Health">Medical & Health</option>
              <option value="Sports & Recreational">Sports & Recreational</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="assignee" class="form-label fw-bold">Assign Person Name</label>
            <select class="form-select" id="assignee" v-model="newResource.assignee">
              <option value="">No Assignee</option>
              <option v-for="person in assignees" :key="person.id" :value="person.name">{{ person.name }}</option>
            </select>
          </div>
          
          <div class="col-12">
            <label for="resourcePhotoFile" class="form-label fw-bold">Upload Photo</label>
            <input 
              type="file" 
              class="form-control" 
              id="resourcePhotoFile" 
              @change="handleFileUpload" 
              accept="image/*"
            >
            <small class="form-text text-muted">Select a file from your device to display below.</small>
          </div>

          <div class="col-12">
            <label for="resourcePhotoUrl" class="form-label fw-bold">Photo (Image URL)</label>
            <input 
              type="url" 
              class="form-control" 
              id="resourcePhotoUrl" 
              v-model="newResource.image" 
              placeholder="Paste image URL here (e.g., https://example.com/photo.jpg)"
            >
            <div v-if="newResource.image" class="mt-2">
              <img :src="newResource.image" alt="Resource Preview" class="img-thumbnail" style="max-height: 150px;">
            </div>
          </div>
          
          <div class="col-12">
            <label for="resourceDescription" class="form-label fw-bold">Description</label>
            <textarea 
              class="form-control" 
              id="resourceDescription" 
              rows="4" 
              v-model="newResource.description"
              placeholder="Provide a detailed description of the resource, its features, and capacity."
            ></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <button type="button" class="btn btn-outline-secondary" @click="handleCancel">
            <i class="bi bi-x-circle me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-save me-1"></i>Save Resource
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const router = useRouter();

interface NewResource {
    id: number;
    name: string;
    location: string;
    category: string;
    assignee: string;
    description: string;
    status: 'active' | 'inactive';
    image: string; // This stores the URL or the Base64 string
}

const initialResourceState: NewResource = {
    id: 0,
    name: '',
    location: '',
    category: '',
    assignee: '',
    description: '',
    status: 'active',
    image: '', // Start with an empty image field
};

const newResource = ref<NewResource>({ ...initialResourceState });

const assignees = ref([
    { id: 101, name: 'Alice Johnson' },
    { id: 102, name: 'Bob Smith' },
    { id: 103, name: 'Charlie Davis' },
]);

// Helper to get resources (relying on ResourcesPage to ensure defaults exist)
const getStoredResources = () => {
    const storedResourcesString = localStorage.getItem('resources');
    return storedResourcesString ? JSON.parse(storedResourcesString) : [];
};

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = (e) => {
      if (e.target && typeof e.target.result === 'string') {
        newResource.value.image = e.target.result; // Store Data URL
      }
    };
    reader.readAsDataURL(file); // Read file as Data URL (Base64)
  }
};

const handleSave = () => {
    // 1. Get the current list of resources
    let resources = getStoredResources();

    // 2. Generate a unique ID 
    const maxId = resources.reduce((max: number, r: any) => (r.id > max ? r.id : max), 0);
    newResource.value.id = maxId + 1;

    // 3. Ensure a default image if none is provided (URL or Base64)
    if (!newResource.value.image) {
      newResource.value.image = 'https://via.placeholder.com/300x180?text=New+Resource';
    }

    // 4. Add the new resource to the list
    resources.push({ ...newResource.value });

    // 5. Save the updated list back to Local Storage
    localStorage.setItem('resources', JSON.stringify(resources));

    // 6. Navigate back to the Resources list page
    router.push('/master-admin/resource'); // Update to your actual path if it's not '/'
};

const handleCancel = () => {
    // Clear form and navigate back
    Object.assign(newResource.value, initialResourceState);
    router.push('/master-admin/resource'); // Update to your actual path if it's not '/'
};
</script>

<style scoped>
/* Inherited section styles */
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

.section-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 24px;
}

.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.img-thumbnail {
    object-fit: cover;
    max-width: 100%;
}

.card{
    align-items: center;
}
</style>