<template>
    <Navbar/>
    <MasterAdminSidebar/>
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
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const stats = ref({
  totalUsers: 265,
  totalResources: 42,
  pendingBookings: 18,
  approvedBookings: 156,
  totalBookings: 240
});
</script>

<style scoped>
/* ================================================= */
/* FIX: ADJUSTED .section FOR FIXED SIDEBAR          */
/* ================================================= */

.section {
  /* Pushes the entire dashboard content to the right by 250px (Sidebar Width) */
  margin-left: 250px; 
  padding: 20px; /* Add overall padding */
  animation: fadeIn 0.3s ease;
  margin-top: 20px;
}

@media (max-width: 768px) {
  /* When the sidebar collapses, reduce the margin to 70px (Collapsed Sidebar Width) */
  .section {
    margin-left: 70px;
  }
}

/* ================================================= */
/* RESPONSIVE CSS STYLES START                       */
/* ================================================= */

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

/* --- Dashboard Header --- */
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
  color: #1e4449;
  font-size: 24px; 
}

@media (min-width: 768px) {
  .dashboard-header .section-title {
    font-size: 32px; 
  }
}

/* --- General Card & Title Styles --- */
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
  height: 100%; 
  margin-top: 20px;
}

/* --- Total Bookings Section --- */
.total-bookings {
  text-align: center;
  padding-top: 20px; 
}

@media (min-width: 768px) {
  .total-bookings {
    padding-top: 42px; 
  }
}

.total-bookings h2 {
  font-size: 40px; 
  color: #1e4449;
  margin-bottom: 20px;
}

@media (min-width: 768px) {
  .total-bookings h2 {
    font-size: 48px; 
  }
}

/* --- Booking Percentage Boxes --- */
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
  transition: transform 0.2s ease;
  text-align: center;
}

@media (min-width: 576px) {
  .booking-box {
    padding: 20px; 
  }
}

.booking-box:hover {
  transform: translateY(-4px);
}

.booking-box p {
  margin: 10px 0 0;
  font-weight: 500;
  color: #1e4449;
  font-size: 16px; 
}

@media (min-width: 768px) {
  .booking-box p {
    font-size: 20px; 
  }
}

.booking-box .badge {
  font-size: 14px; 
  padding: 10px 15px; 
  display: inline-block; 
}

@media (min-width: 768px) {
  .booking-box .badge {
    font-size: 16px; 
    padding: 18px 24px; 
  }
}

.booking-box.approved { border-top: 4px solid white; }
.booking-box.pending { border-top: 4px solid white; }
.booking-box.rejected { border-top: 4px solid white; }

/* --- Pie Chart Container --- */
/* .pie-chart-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 0;
} */


</style>