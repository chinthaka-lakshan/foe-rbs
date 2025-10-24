import { defineStore } from 'pinia'
import { ref } from 'vue'
// Supabase imports have been removed.

export const useAuthStore = defineStore('auth', () => {
  // State
  // user type is simplified to 'any' for local storage compatibility
  const user = ref<any | null>(null)
  const userRole = ref<string>('')
  const loading = ref(false)
  const error = ref<string>('')

  // Actions
  const signIn = async (email: string, password: string): Promise<boolean> => {
    // Mock user database (hardcoded credentials)
    const mockUsers = [
        { email: 'masteradmin@university.edu', password: 'password', role: 'master_admin', name: 'Master Admin' },
        { email: 'admin@university.edu', password: 'password', role: 'admin', name: 'Admin User' },
        { email: 'user@university.edu', password: 'password', role: 'user', name: 'Regular User' },
    ];

    try {
      loading.value = true
      error.value = ''
      
      const foundUser = mockUsers.find(u => u.email === email && u.password === password);

      if (!foundUser) {
          throw new Error('Invalid email or password.');
      }
      
      // Simulate successful login
      user.value = { 
          id: 'mock-' + foundUser.role,
          email: foundUser.email,
          full_name: foundUser.name
      };
      userRole.value = foundUser.role;

      // Save state to localStorage for persistence
      localStorage.setItem('user', JSON.stringify(user.value));
      localStorage.setItem('userRole', foundUser.role);

      return true
    } catch (err: any) {
      error.value = err.message
      user.value = null;
      userRole.value = '';
      return false
    } finally {
      loading.value = false
    }
  }

  const signUp = async (email: string, password: string, fullName: string): Promise<boolean> => {
    try {
      loading.value = true
      error.value = ''
      
      // TEMPORARY: Simulate successful sign-up and auto-login as a 'user'
      // In a real local system, you would save this user to a local array/store.

      user.value = { id: 'mock-new-' + Date.now(), email: email, full_name: fullName };
      userRole.value = 'user'; // Assign default role
      
      localStorage.setItem('user', JSON.stringify(user.value));
      localStorage.setItem('userRole', 'user');

      return true
    } catch (err: any) {
      // Since there's no actual API call, this catch block is only for local errors
      error.value = err.message || 'Signup failed.';
      return false
    } finally {
      loading.value = false
    }
  }

  const signOut = async () => {
    try {
      loading.value = true
      
      // Clear local storage state
      localStorage.removeItem('user');
      localStorage.removeItem('userRole');

      user.value = null
      userRole.value = ''
    } catch (err: any) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  const resetPassword = async (email: string): Promise<boolean> => {
    try {
      loading.value = true
      error.value = ''
      // TEMPORARY: Simulate password reset process
      await new Promise(resolve => setTimeout(resolve, 500)); 
      error.value = `Password reset simulated: Check your email ${email}.`;
      
      return true
    } catch (err: any) {
      error.value = err.message
      return false
    } finally {
      loading.value = false
    }
  }

  // Load user state from localStorage on app start
  const initAuth = async () => {
    const storedUser = localStorage.getItem('user');
    const storedRole = localStorage.getItem('userRole');
    
    if (storedUser && storedRole) {
      user.value = JSON.parse(storedUser);
      userRole.value = storedRole;
    } else {
      user.value = null;
      userRole.value = '';
    }
    
    // Auth listener is removed since local storage doesn't have native listeners
  }

  // Return public API
  return {
    user,
    userRole,
    loading,
    error,
    signIn,
    signUp,
    signOut,
    resetPassword,
    initAuth
  }
})
