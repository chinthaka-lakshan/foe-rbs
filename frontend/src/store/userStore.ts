// src/store/userStore.ts
import { reactive } from 'vue';
import axios from 'axios';

export const userStore = reactive({
  users: [] as any[],
  isLoading: false,
  isLoaded: false,

  async fetchUsers(force = false) {
    // Only fetch if not already loaded to prevent redundant API calls
    if (this.isLoaded && !force) return;
    
    this.isLoading = true;
    try {
      const token = localStorage.getItem('authToken');
      const res = await axios.get('http://localhost:8000/api/users', {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      // Standardize the user data mapping
      this.users = res.data.map((user: any) => ({
        ...user,
        primaryRole: user.roles?.[0]?.name || 'User'
      }));
      this.isLoaded = true;
    } catch (e) {
      console.error("Global User Store fetch failed", e);
    } finally {
      this.isLoading = false;
    }
  },

  // Helper to update a user's status or role instantly across the app
  updateUserLocally(userId: number | string, updatedFields: object) {
    const index = this.users.findIndex(u => u.id === userId);
    if (index !== -1) {
      this.users[index] = { ...this.users[index], ...updatedFields };
    }
  },

  // Helper to remove a user from memory after deletion
  removeUserLocally(userId: number | string) {
    this.users = this.users.filter(u => u.id !== userId);
  },

  // Helper to add a new user to memory after creation
  addUserLocally(newUser: any) {
    this.users.unshift({
      ...newUser,
      primaryRole: newUser.roles?.[0]?.name || 'User'
    });
  }
});