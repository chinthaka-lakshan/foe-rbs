<template>
  <Navbar />
  <MasterAdminSidebar />
  <div class="section">
    
    <div class="dashboard-header mb-4">
      <h2 class="section-title">Welcome Master Admin Dashboard</h2>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-people-fill"
          :value="stats.totalUsers"
          label="Total Users"
          color="#4BB66D"
        />
      </div>
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-box-fill"
          :value="stats.totalResources"
          label="Total Resources"
          color="#26d516"
        />
      </div>
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-clock-fill"
          :value="stats.pendingBookings"
          label="Pending Bookings"
          color="#fcc300"
        />
      </div>
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-check-circle-fill"
          :value="stats.approvedBookings"
          label="Approved Bookings"
          color="#1e4449"
        />
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="mb-3">Bookings Status</h5>
          <div v-if="isLoading" class="text-center py-5">
             <div class="spinner-border text-success" role="status"></div>
          </div>
          <div v-else class="pie-chart-container">
            <PieChart
              :approved="stats.approvedBookings"
              :pending="stats.pendingBookings"
              :rejected="stats.rejectedBookings"
            />
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="mb-3">Total Bookings Summary</h5>
          <div class="total-bookings">
            <h2 v-if="!isLoading">{{ stats.totalBookings }}</h2>
            <h2 v-else>...</h2>
            <div class="booking-boxes">
              <div class="booking-box approved"> 
                <span class="badge bg-success">{{ calculatePercent(stats.approvedBookings) }}%</span> 
                <p>Approved</p>
              </div>
              <div class="booking-box pending">
                <span class="badge bg-warning text-dark">{{ calculatePercent(stats.pendingBookings) }}%</span> 
                <p>Pending</p>
              </div>
              <div class="booking-box rejected">
                <span class="badge bg-danger">{{ calculatePercent(stats.rejectedBookings) }}%</span> 
                <p>Rejected</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import StatCard from '../../components/StatCard.vue';
import PieChart from '../../components/PieChart.vue';
import Navbar from '../../components/Navbar.vue';
import { userStore } from '../../store/userStore'
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- API CONFIG ---
const API_BASE_URL = 'http://localhost:8000/api';
const getAuthToken = () => localStorage.getItem('authToken');

// --- STATE ---
const isLoading = ref(true);
const stats = ref({
  totalUsers: computed(() => userStore.users.length),
  totalResources: 0,
  pendingBookings: 0,
  approvedBookings: 0,
  rejectedBookings: 0,
  totalBookings: 0
});

// --- LOGIC ---

// Helper to calculate percentages for the UI
const calculatePercent = (value: number) => {
  if (stats.value.totalBookings === 0) return 0;
  return Math.round((value / stats.value.totalBookings) * 100);
};

const fetchDashboardData = async () => {
  isLoading.value = true;
  const token = getAuthToken();

  if (!token) {
    console.error("Auth token missing");
    isLoading.value = false;
    return;
  }

  try {
    // 1. Fetch Users Count
    if (!userStore.isLoaded) {
      await userStore.fetchUsers();
    }

    // 2. Fetch Bookings and filter by exact Backend Status strings
    const bookingRes = await fetch(`${API_BASE_URL}/bookings`, {
       headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (bookingRes.ok) {
        const bookings = await bookingRes.json();
        stats.value.totalBookings = bookings.length;

        // UPDATED FILTER LOGIC TO MATCH POSTMAN DATA
        stats.value.approvedBookings = bookings.filter((b: any) => b.status === 'Confirmed' || b.status === 'Completed').length;
        stats.value.pendingBookings = bookings.filter((b: any) => b.status === 'Pending').length;
        stats.value.rejectedBookings = bookings.filter((b: any) => b.status === 'Cancelled').length;
    }

    // 3. Fetch Resources Count
    const resourceRes = await fetch(`${API_BASE_URL}/resources`, {
       headers: { 'Authorization': `Bearer ${token}` }
    });
    if (resourceRes.ok) {
        const resources = await resourceRes.json();
        stats.value.totalResources = Array.isArray(resources) ? resources.length : 0;
    }

  } catch (error) {
    console.error("Error fetching dashboard stats:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
.section {
  margin-left: 250px; 
  padding: 20px; 
  animation: fadeIn 0.3s ease;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section { margin-left: 70px; }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.dashboard-header {
  background-color: #e5f4de; 
  color: #1e4449; 
  text-align: center;
  padding: 30px 15px; 
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.dashboard-header .section-title {
  margin: 0;
  font-weight: 600;
  font-size: 24px; 
}

.chart-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  height: 100%; 
  margin-top: 20px;
}

.total-bookings h2 {
  font-size: 40px; 
  color: #1e4449;
  text-align: center;
  margin-bottom: 20px;
}

.booking-boxes {
  display: flex;
  flex-wrap: wrap; 
  justify-content: space-between;
  gap: 16px;
}

.booking-box {
  flex: 1 1 30%; 
  min-width: 90px; 
  background: white;
  border-radius: 8px;
  padding: 15px; 
  box-shadow: 0 2px 8px rgba(30, 68, 73, 0.15);
  text-align: center;
}

.booking-box p {
  margin: 10px 0 0;
  font-weight: 500;
  color: #1e4449;
}
</style>