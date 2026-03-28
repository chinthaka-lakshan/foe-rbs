<template>
  <Navbar />
  <UserSidebar />
  <div class="section">
    <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center">
      <div>
        <h2 class="section-title mb-1">My Bookings</h2>
        <p class="text-muted mb-0">Track the status of your resource reservations.</p>
      </div>
      <button class="btn btn-outline-primary" @click="bookingStore.fetchMyBookings(true)">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="bookingStore.isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading your bookings...</span>
      </div>
    </div>

    <!-- Bookings Table -->
    <div v-else class="card shadow-sm border-0">
      <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col" class="ps-4">Reference</th>
              <th scope="col">Date & Time</th>
              <th scope="col">Items / Resources</th>
              <th scope="col">Amount</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in bookingStore.bookings" :key="b.id">
              <td class="ps-4 fw-bold text-primary">{{ b.booking_reference }}</td>
              <td>
                <div class="fw-bold">{{ formatDate(b.booking_date) }}</div>
                <small class="text-muted">{{ formatTime(b.start_time) }} - {{ formatTime(b.end_time) }}</small>
              </td>
              <td>
                <ul class="list-unstyled mb-0 small">
                  <li v-for="detail in b.details" :key="detail.id">
                    <i class="bi bi-dot"></i> {{ detail.item_name }}
                    <span class="text-muted" v-if="detail.item_type === 'booking_item'">(x{{ detail.quantity }})</span>
                  </li>
                  <li v-if="!b.details || b.details.length === 0" class="text-muted fst-italic">No details</li>
                </ul>
              </td>
              <td>Rs. {{ parseFloat(b.total_amount).toFixed(2) }}</td>
              <td>
                <span class="badge" :class="statusBadgeClass(b.status)">
                  {{ formatStatus(b.status) }}
                </span>
              </td>
              <td class="text-end pe-4">
                <button 
                  class="btn btn-sm btn-outline-danger" 
                  v-if="['Pending', 'Pending_for_Verification', 'Confirmed'].includes(b.status)"
                  @click="cancelBooking(b)"
                  :disabled="isCancelling === b.id"
                >
                  <span v-if="isCancelling === b.id" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <span v-else class="text-muted small">-</span>
              </td>
            </tr>
            <tr v-if="bookingStore.bookings.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                <h5>No Bookings Found</h5>
                <p>You haven't made any resource reservations yet.</p>
                <router-link to="/user/resource" class="btn btn-primary mt-2">Browse Resources</router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Navbar from '../../components/Navbar.vue';
import UserSidebar from '../../components/Sidebar/UserSidebar.vue';
import { bookingStore } from '../../store/bookingStore';
import axios from 'axios';

const isCancelling = ref<number | null>(null);

onMounted(() => {
  bookingStore.fetchMyBookings();
});

const formatDate = (dateStr: string) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatTime = (timeStr: string) => {
  if (!timeStr) return '';
  const [h, m] = timeStr.split(':');
  const date = new Date();
  date.setHours(parseInt(h), parseInt(m), 0);
  return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

const formatStatus = (status: string) => {
  if (status === 'Pending_for_Verification') return 'Awaiting OTP';
  return status;
};

const statusBadgeClass = (status: string) => {
  switch (status) {
    case 'Confirmed':
    case 'Approved':
      return 'bg-success';
    case 'Pending':
      return 'bg-warning text-dark';
    case 'Pending_for_Verification':
      return 'bg-info text-dark';
    case 'Cancelled':
    case 'Rejected':
      return 'bg-danger';
    case 'Completed':
      return 'bg-secondary';
    default:
      return 'bg-light text-dark';
  }
};

const cancelBooking = async (b: any) => {
  if (!confirm(`Are you sure you want to cancel booking ${b.booking_reference}?`)) return;
  
  isCancelling.value = b.id;
  try {
    const token = localStorage.getItem('authToken');
    const response = await axios.post(`http://localhost:8000/api/bookings/${b.id}/cancel`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.data) {
      bookingStore.updateBookingLocally(response.data.booking);
      alert("Booking successfully cancelled.");
    }
  } catch (error: any) {
    console.error("Cancellation failed", error);
    alert(error.response?.data?.message || "Cancellation failed due to server error.");
  } finally {
    isCancelling.value = null;
  }
};
</script>

<style scoped>
.section {
  margin-left: 250px;
  padding: 20px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section {
    margin-left: 70px;
  }
}

.dashboard-header {
  background-color: #e5f4de; 
  color: #1e4449; 
  padding: 20px 25px; 
  border-radius: 10px;
}

.section-title {
  margin: 0;
  font-weight: 600;
}
</style>
