<template>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Resources</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm">
          <i class="bi bi-list-ul me-1"></i>Categories
        </button>
        <button class="btn btn-outline-primary btn-sm">
          <i class="bi bi-file-text me-1"></i>Templates
        </button>
        <button class="btn btn-success btn-sm">
          <i class="bi bi-plus-circle me-1"></i>Add New
        </button>
      </div>
    </div>

    <div class="mb-4">
      <div class="row g-3">
        <div class="col-md-8">
          <input
            type="text"
            class="form-control"
            placeholder="Search resources by name..."
            v-model="searchQuery"
          >
        </div>
        <div class="col-md-4">
          <select class="form-select" v-model="selectedCategory">
            <option value="">All Categories</option>
            <option value="academic">Academic Space</option>
            <option value="medical">Medical & Health</option>
            <option value="sports">Sports & Recreational</option>
          </select>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div v-for="resource in filteredResources" :key="resource.id" class="col-md-4">
        <div class="resource-card">
          <div class="resource-image">
            <img :src="resource.image" :alt="resource.name">
          </div>
          <div class="resource-body">
            <h5>{{ resource.name }}</h5>
            <p class="text-muted mb-2">{{ resource.category }}</p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge" :class="resource.status === 'active' ? 'bg-success' : 'bg-secondary'">
                {{ resource.status }}
              </span>
              <div class="form-check form-switch">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :checked="resource.status === 'active'"
                  @change="toggleResourceStatus(resource.id)"
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const searchQuery = ref('');
const selectedCategory = ref('');

const resources = ref([
  { id: 1, name: 'Conference Room A', category: 'Academic Space', status: 'active', image: 'https://images.pexels.com/photos/1181406/pexels-photo-1181406.jpeg?auto=compress&cs=tinysrgb&w=300' },
  { id: 2, name: 'Sports Hall', category: 'Sports & Recreational', status: 'active', image: 'https://images.pexels.com/photos/260024/pexels-photo-260024.jpeg?auto=compress&cs=tinysrgb&w=300' },
  { id: 3, name: 'Library Study Room', category: 'Academic Space', status: 'inactive', image: 'https://images.pexels.com/photos/2041540/pexels-photo-2041540.jpeg?auto=compress&cs=tinysrgb&w=300' },
  { id: 4, name: 'Medical Lab', category: 'Medical & Health', status: 'active', image: 'https://images.pexels.com/photos/356040/pexels-photo-356040.jpeg?auto=compress&cs=tinysrgb&w=300' },
  { id: 5, name: 'Basketball Court', category: 'Sports & Recreational', status: 'active', image: 'https://images.pexels.com/photos/1752757/pexels-photo-1752757.jpeg?auto=compress&cs=tinysrgb&w=300' },
  { id: 6, name: 'Lecture Hall B', category: 'Academic Space', status: 'active', image: 'https://images.pexels.com/photos/267885/pexels-photo-267885.jpeg?auto=compress&cs=tinysrgb&w=300' },
  
]);

const filteredResources = computed(() => {
  return resources.value.filter(resource => {
    const matchesSearch = resource.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesCategory = !selectedCategory.value || resource.category.toLowerCase().includes(selectedCategory.value);
    return matchesSearch && matchesCategory;
  });
});

const toggleResourceStatus = (id: number) => {
  const resource = resources.value.find(r => r.id === id);
  if (resource) {
    resource.status = resource.status === 'active' ? 'inactive' : 'active';
  }
};
</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
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

.resource-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.resource-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.resource-image {
  height: 180px;
  overflow: hidden;
}

.resource-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.resource-body {
  padding: 16px;
}

.resource-body h5 {
  margin-bottom: 8px;
  color: #1e4449;
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
