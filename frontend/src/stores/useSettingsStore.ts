// src/stores/useSettingsStore.ts

import { reactive, readonly } from 'vue';

/**
 * Defines the strict type/shape of the data held in the global settings state.
 */
interface SettingsState {
    systemName: string; // Used in Navbar title
    organizationName: string; // Used in Sidebar text
    logoPath: string; // Used in Sidebar image source
    contactEmail: string;
    phoneNumber: string;
    address: string;
}

// 1. Define the core state object using 'reactive'.
// This is the single source of truth. Any component consuming this will update automatically.
const state = reactive<SettingsState>({
    systemName: 'University Resources', // Default value
    organizationName: 'FOE RBS', // Default value
    logoPath: '/logo.png', // Default logo path (points to public folder)
    contactEmail: '',
    phoneNumber: '',
    address: '',
});

/**
 * Function to update the settings state.
 * This is the only way components should modify the central state.
 * @param newSettings A partial object containing the key-value pairs to update.
 */
const setSettings = (newSettings: Partial<SettingsState>) => {
    // Merge the new properties into the existing state object
    Object.assign(state, newSettings);
};

/**
 * The main composable function that components call to access the store.
 */
export function useSettingsStore() {
    return {
        // Expose the state wrapped in 'readonly'. 
        // This allows components to read the data, but prevents them from directly
        // modifying it, forcing them to use the 'setSettings' action instead.
        settings: readonly(state), 
        
        // Expose the update function
        setSettings,
    };
}