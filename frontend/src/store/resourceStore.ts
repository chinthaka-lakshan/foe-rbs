// src/store/resourceStore.ts
import { reactive } from 'vue';
import axios from 'axios';

export const resourceStore = reactive({
  resources: [] as any[],
  categories: [] as any[],
  isLoading: false,
  isLoaded: false,

  async fetchAll() {
    // Only fetch once per session unless explicitly refreshed
    if (this.isLoaded) return;
    
    this.isLoading = true;
    try {
      const token = localStorage.getItem('authToken');
      const headers = { Authorization: `Bearer ${token}`, Accept: 'application/json' };
      
      const [resResources, resCategories] = await Promise.all([
        axios.get('http://localhost:8000/api/resources', { headers }),
        axios.get('http://localhost:8000/api/categories', { headers })
      ]);

      this.resources = resResources.data.resources || resResources.data;
      this.categories = resCategories.data.categories || resCategories.data;
      this.isLoaded = true;
    } catch (e) {
      console.error("Resource Store failed to load", e);
    } finally {
      this.isLoading = false;
    }
  },

  // Helper to remove resource from memory after deletion
  removeResource(id: number) {
    this.resources = this.resources.filter(r => r.id !== id);
  },

  // Helper to update status instantly in the UI
  updateStatus(id: number, status: string) {
    const resource = this.resources.find(r => r.id === id);
    if (resource) resource.status = status;
  }
});