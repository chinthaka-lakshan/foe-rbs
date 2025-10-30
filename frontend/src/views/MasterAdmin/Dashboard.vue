<template>
  <div class="section">
    <div class="dashboard-header">
       <h2 class="section-title">Welcome Master Admin Dashboard</h2>
    </div>
   

    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <StatCard
          icon="bi bi-people-fill"
          :value="stats.totalUsers"
          label="Total Users"
          color="#4BB66D"
        />
      </div>
      <div class="col-md-3">
        <StatCard
          icon="bi bi-box-fill"
          :value="stats.totalResources"
          label="Total Resources"
          color="#26d516"
        />
      </div>
      <div class="col-md-3">
        <StatCard
          icon="bi bi-clock-fill"
          :value="stats.pendingBookings"
          label="Pending Bookings"
          color="#fcc300"
        />
      </div>
      <div class="col-md-3">
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
          <div class="pie-chart-container">
            <PieChart
              :approved="156"
              :pending="60"
              :rejected="35"
            />
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="mb-3">Total Bookings</h5>
          <div class="total-bookings">
            <h2>{{ stats.totalBookings }}</h2>
              <div class="booking-boxes">
                <div class="booking-box approved"> 
                  <span class="badge bg-success">65%</span> <p>Approved</p>
                </div>
                <div class="booking-box pending">
                  <span class="badge bg-warning text-dark">25%</span> <p>Pending</p>
                </div>
                <div class="booking-box rejected">
                  <span class="badge bg-danger">10%</span> <p>Rejected</p>
                </div>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import StatCard from '../../components/StatCard.vue';
import PieChart from '../../components/PieChart.vue';

const stats = ref({
  totalUsers: 265,
  totalResources: 42,
  pendingBookings: 18,
  approvedBookings: 156,
  totalBookings: 240
});
</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
}

.dashboard-header {
  background-color:#e5f4de; /* green box */
  color: white;
  text-align: center;
  padding: 30px 0;
  border-radius: 10px;
  margin-bottom: 40px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.dashboard-header .section-title {
  margin: 0;
  font-size: 32px; /* size of Dashboard Overview text */
  font-weight: 600;
  color:#1e4449; /* ensure text is visible on green */
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

.chart-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(114, 38, 38, 0.08);
  min-height: 440px;

}

/* .pie-chart-container {
  height: 350px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  
} */

.total-bookings {
  text-align: center;
  padding-top: 42px;
   /* height: 350px; */
  
}

.total-bookings h2 {
  font-size: 48px;
  color: #1e4449;
  margin-bottom: 20px;
}

.booking-percentages {
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  gap: 15px;
  font-size: 24px;
  /* background: rgb(249, 252, 246);
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(114, 38, 38, 0.08); */
}

.booking-boxes {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.booking-box {
  flex: 1;
  min-width: 100px;
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(30, 68, 73, 0.15);
  transition: transform 0.2s ease;
}

.booking-box:hover {
  transform: translateY(-4px);
}

.booking-box p {
  margin: 10px 0 0;
  font-weight: 500;
  color: #1e4449;
  font-size: 20px;
}

.booking-box.approved {
  border-top: 4px solid white;
}

.booking-box.pending {
  border-top: 4px solid white;
}

.booking-box.rejected {
  border-top: 4px solid white;
}

.booking-box .badge {
  font-size: 16px; /* increase or decrease this value */
  padding: 18px 24px; /* optional: adjust padding for better look */
}


</style>
