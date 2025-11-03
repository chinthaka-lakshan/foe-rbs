<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">Bookings</h2>

    <div class="mb-4">
      <div class="row g-3">
        <div class="col-md-4">
          <select class="form-select" v-model="selectedResource">
            <option value="">All Resources</option>
            <option value="1">Conference Room A</option>
            <option value="2">Sports Hall</option>
            <option value="3">Library Study Room</option>
            <option value="4">Medical Lab</option>
            <option value="5">Basketball Court</option>
          </select>
        </div>
        <div class="col-md-4">
          <select class="form-select" v-model="selectedStatus">
            <option value="">All Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <div class="col-md-4">
          <input type="date" class="form-control" v-model="selectedDate">
        </div>
      </div>
    </div>

    <div class="calendar-card mb-4">
      <h5 class="mb-3">Booking Calendar</h5>
      <div class="calendar-placeholder">
        <i class="bi bi-calendar3" style="font-size: 48px; color: #1e4449;"></i>
        <p class="text-center text-muted">Calendar view showing booked dates</p>
      </div>
    </div>

    <div class="table-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">All Bookings</h5>
        <button class="btn btn-success btn-sm">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>User ID</th>
              <th>Resource Name</th>
              <th>Booking Date</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in filteredBookings" :key="booking.id">
              <td>{{ booking.userId }}</td>
              <td>{{ booking.resourceName }}</td>
              <td>{{ booking.date }}</td>
              <td>{{ booking.startTime }}</td>
              <td>{{ booking.endTime }}</td>
              <td>
                <span class="badge" :class="getStatusClass(booking.status)">
                  {{ booking.status }}
                </span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-success" v-if="booking.status === 'pending'" @click="approveBooking(booking.id)">
                    <i class="bi bi-check-circle"></i>
                  </button>
                  <button class="btn btn-outline-danger" @click="deleteBooking(booking.id)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const selectedResource = ref('');
const selectedStatus = ref('');
const selectedDate = ref('');

const bookings = ref([
  { id: 1, userId: 'U001', resourceName: 'Conference Room A', date: '2025-10-25', startTime: '10:00 AM', endTime: '12:00 PM', status: 'approved' },
  { id: 2, userId: 'U002', resourceName: 'Sports Hall', date: '2025-10-26', startTime: '2:00 PM', endTime: '4:00 PM', status: 'pending' },
  { id: 3, userId: 'U003', resourceName: 'Library Study Room', date: '2025-10-27', startTime: '9:00 AM', endTime: '11:00 AM', status: 'rejected' },
  { id: 4, userId: 'U004', resourceName: 'Medical Lab', date: '2025-10-28', startTime: '1:00 PM', endTime: '3:00 PM', status: 'approved' },
  { id: 5, userId: 'U005', resourceName: 'Basketball Court', date: '2025-10-29', startTime: '4:00 PM', endTime: '6:00 PM', status: 'pending' }
]);

const filteredBookings = computed(() => {
  return bookings.value.filter(booking => {
    const matchesResource = !selectedResource.value || booking.resourceName.includes('Room') && selectedResource.value === '1';
    const matchesStatus = !selectedStatus.value || booking.status === selectedStatus.value;
    const matchesDate = !selectedDate.value || booking.date === selectedDate.value;
    return matchesResource && matchesStatus && matchesDate;
  });
});

const getStatusClass = (status: string) => {
  switch (status) {
    case 'approved': return 'bg-success';
    case 'pending': return 'bg-warning';
    case 'rejected': return 'bg-danger';
    default: return 'bg-secondary';
  }
};

const approveBooking = (id: number) => {
  const booking = bookings.value.find(b => b.id === id);
  if (booking) {
    booking.status = 'approved';
  }
};

const deleteBooking = (id: number) => {
  const index = bookings.value.findIndex(b => b.id === id);
  if (index !== -1) {
    bookings.value.splice(index, 1);
  }
};
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

.table-card,
.calendar-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.calendar-placeholder {
  height: 300px;
  background: #f8f9fa;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
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
