<template>
  <div class="section">
    <h2 class="section-title">Dashboard Overview</h2>

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
            <div class="booking-percentages">
              <div><span class="badge bg-success">65%</span> Approved</div>
              <div><span class="badge bg-warning">25%</span> Pending</div>
              <div><span class="badge bg-danger">10%</span> Rejected</div>
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
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.pie-chart-container {
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.total-bookings {
  text-align: center;
  padding: 20px;
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
}
</style>
