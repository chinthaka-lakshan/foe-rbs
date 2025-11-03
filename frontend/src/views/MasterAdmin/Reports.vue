<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">Reports</h2>

    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(38, 213, 22, 0.1);">
            <i class="bi bi-file-text" style="color: #26d516; font-size: 28px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalResources }}</h3>
            <p>Total Resources</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(75, 182, 109, 0.1);">
            <i class="bi bi-calendar-check" style="color: #4BB66D; font-size: 28px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalBookings }}</h3>
            <p>Total Bookings</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(30, 68, 73, 0.1);">
            <i class="bi bi-people" style="color: #1e4449; font-size: 28px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalUsers }}</h3>
            <p>Total Users</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(252, 195, 0, 0.1);">
            <i class="bi bi-percent" style="color: #fcc300; font-size: 28px;"></i>
          </div>
          <div class="stat-content">
            <h3>65%</h3>
            <p>Success Rate</p>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex gap-2">
        <select class="form-select w-auto" v-model="selectedPeriod">
          <option value="week">This Week</option>
          <option value="month">This Month</option>
          <option value="year">This Year</option>
        </select>
        <select class="form-select w-auto" v-model="selectedReportType">
          <option value="bookings">Bookings Report</option>
          <option value="resources">Resources Report</option>
          <option value="users">Users Report</option>
        </select>
      </div>
      <button class="btn btn-success">
        <i class="bi bi-printer me-1"></i>Export PDF
      </button>
    </div>

    <div class="chart-card mb-4">
      <h5 class="mb-3">Booking Statistics</h5>
      <div class="bar-chart-placeholder">
        <i class="bi bi-bar-chart-fill" style="font-size: 48px; color: #1e4449;"></i>
        <p class="text-center text-muted">Bar chart showing booking trends over time</p>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="mb-3">Resource Utilization</h5>
          <div class="mini-chart-placeholder">
            <i class="bi bi-pie-chart-fill" style="font-size: 36px; color: #26d516;"></i>
            <p class="text-center text-muted">Resource usage breakdown</p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="mb-3">User Activity</h5>
          <div class="mini-chart-placeholder">
            <i class="bi bi-graph-up" style="font-size: 36px; color: #4BB66D;"></i>
            <p class="text-center text-muted">Active users over time</p>
          </div>
        </div>
      </div>
    </div>

    <div class="table-card">
      <h5 class="mb-3">Recent Activity Log</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Date</th>
              <th>User</th>
              <th>Action</th>
              <th>Resource</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="activity in activityLog" :key="activity.id">
              <td>{{ activity.date }}</td>
              <td>{{ activity.user }}</td>
              <td>{{ activity.action }}</td>
              <td>{{ activity.resource }}</td>
              <td>
                <span class="badge" :class="activity.status === 'success' ? 'bg-success' : 'bg-warning'">
                  {{ activity.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const selectedPeriod = ref('month');
const selectedReportType = ref('bookings');

const stats = ref({
  totalResources: 42,
  totalBookings: 240,
  totalUsers: 265
});

const activityLog = ref([
  { id: 1, date: '2025-10-28 10:30', user: 'John Doe', action: 'Booking Created', resource: 'Conference Room A', status: 'success' },
  { id: 2, date: '2025-10-28 09:15', user: 'Jane Smith', action: 'Resource Updated', resource: 'Sports Hall', status: 'success' },
  { id: 3, date: '2025-10-27 16:45', user: 'Bob Johnson', action: 'Booking Cancelled', resource: 'Library Study Room', status: 'success' },
  { id: 4, date: '2025-10-27 14:20', user: 'Alice Williams', action: 'User Created', resource: 'N/A', status: 'success' },
  { id: 5, date: '2025-10-27 11:00', user: 'Charlie Brown', action: 'Booking Requested', resource: 'Medical Lab', status: 'pending' }
]);
</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
  margin-left: 260px;
}
@media (max-width: 768px) {
  /* When the sidebar collapses, reduce the margin to 70px (Collapsed Sidebar Width) */
  .section {
    margin-left: 80px;
  }
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

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-content h3 {
  font-size: 28px;
  font-weight: 700;
  color: #1e4449;
  margin: 0;
}

.stat-content p {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
}

.chart-card,
.table-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.bar-chart-placeholder,
.mini-chart-placeholder {
  height: 300px;
  background: #f8f9fa;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.mini-chart-placeholder {
  height: 200px;
}

.table thead {
  background: #f8f9fa;
}

.btn-success {
  background-color: #26d516;
  border-color: #26d516;
}

.btn-success:hover {
  background-color: #22b913;
  border-color: #22b913;
}
</style>
