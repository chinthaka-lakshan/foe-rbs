import { reactive } from 'vue';
import axios from 'axios';

export const systemStore = reactive({
  name: 'FOE RBS',
  logo: '',
  isLoaded: false,

  async loadSettings() {
    try {
      const res = await axios.get('http://localhost:8000/api/settings');
      this.updateState(res.data);
    } catch (e) {
      console.error("Store fetch failed", e);
    }
  },

  updateState(data: any) {
  this.name = data.site_name || data.value || this.name || 'FOE RBS';
  
  if (data.logo) {
    const filename = data.logo.split('/').pop();
    const timestamp = new Date().getTime();
    this.logo = `http://localhost:8000/api/settings/logo/${filename}?t=${timestamp}`;
  }
  this.isLoaded = true;
}
});