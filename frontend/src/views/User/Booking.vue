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

    <!-- Calendar View -->
    <div class="calendar-card mb-4 shadow-sm border-0">
      <div class="d-flex justify-content-between align-items-center mb-4">
          <button class="btn btn-sm btn-outline-dark-teal" @click="changeMonth(-1)">
              <i class="bi bi-chevron-left"></i>
          </button>
          <h5 class="mb-0 calendar-title-header">{{ currentMonthName }} {{ currentYear }}</h5>
          <button class="btn btn-sm btn-outline-dark-teal" @click="changeMonth(1)">
              <i class="bi bi-chevron-right"></i>
          </button>
      </div>

      <div class="calendar-grid">
          <div v-for="day in weekdays" :key="day" class="calendar-header">{{ day }}</div>
          
          <div 
            v-for="day in daysInMonth" 
            :key="day.dateString" 
            class="calendar-day"
            :class="{ 
                'day-outside-month': day.isOutsideMonth,
                'day-has-booking': day.hasBooking,
                'day-is-selected': selectedDate === day.dateString
            }"
            @click="day.dateString && selectDate(day.dateString)"
            :title="day.hasBooking ? `${day.bookingCount} reservation(s) on ${day.dayNumber}` : ''"
          >
            <span class="day-number">{{ day.dayNumber }}</span>
            <span v-if="day.bookingCount" class="booking-badge">{{ day.bookingCount }}</span>
          </div>
      </div>
    </div>

    <!-- Selected Day Bookings Detail -->
    <div v-if="selectedDate" class="card mb-4 border-0 shadow-sm animate-fade-in">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-dark-teal"><i class="bi bi-calendar-event me-2"></i>Bookings for {{ formatDateLong(selectedDate) }}</h5>
        <button class="btn-close" @click="selectedDate = ''"></button>
      </div>
      <div class="card-body">
        <div v-if="selectedDateBookings.length > 0">
          <div v-for="b in selectedDateBookings" :key="b.id" class="booking-detail-item p-3 border rounded mb-2">
            <div class="d-flex justify-content-between">
              <span class="fw-bold">{{ b.booking_reference }}</span>
              <span class="badge" :class="statusBadgeClass(b.status)">{{ formatStatus(b.status) }}</span>
            </div>
            <div class="small text-muted mt-1">
              <i class="bi bi-clock me-1"></i>{{ formatTime(b.start_time) }} - {{ formatTime(b.end_time) }}
            </div>
            <div class="small mt-1">
              <strong>Resources: </strong>
              <span v-for="(detail, idx) in b.details" :key="detail.id">
                {{ detail.item_name }}{{ idx < b.details.length - 1 ? ', ' : '' }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-3 text-muted">
          No reservations found for this date.
        </div>
      </div>
    </div>

    <!-- Bookings Table -->
    <div v-if="!selectedDate" class="card shadow-sm border-0">
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
              <td>Rs. {{ Number(b.total_amount).toFixed(2) }}</td>
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
import { ref, computed, onMounted } from 'vue';
import Navbar from '../../components/Navbar.vue';
import UserSidebar from '../../components/Sidebar/UserSidebar.vue';
import { bookingStore } from '../../store/bookingStore';
import axios from 'axios';

const isCancelling = ref<number | null>(null);

// Calendar State
const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const currentDate = ref(new Date());
const selectedDate = ref('');

onMounted(() => {
  bookingStore.fetchMyBookings();
});

// Calendar computed
const currentMonthName = computed(() => 
  currentDate.value.toLocaleString('default', { month: 'long' })
);
const currentYear = computed(() => currentDate.value.getFullYear());

const changeMonth = (delta: number) => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + delta, 1);
};

const selectDate = (dateString: string) => {
  if (selectedDate.value === dateString) {
    selectedDate.value = '';
  } else {
    selectedDate.value = dateString;
  }
};

const daysInMonth = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();

  const firstDayOfMonth = new Date(year, month, 1);
  const jsDayOfWeek = firstDayOfMonth.getDay();
  const startingDayOfWeekIndex = (jsDayOfWeek + 6) % 7;
  
  const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
  const daysInPreviousMonth = new Date(year, month, 0).getDate();

  const allDays = [];
  const bookings = bookingStore.bookings || [];

  // Create booking count map
  const bookingCountMap = new Map();
  bookings.forEach(booking => {
    const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
    bookingCountMap.set(bookingDate, (bookingCountMap.get(bookingDate) || 0) + 1);
  });

  // Previous month padding
  for (let i = startingDayOfWeekIndex; i > 0; i--) {
    const dayNumber = daysInPreviousMonth - i + 1;
    allDays.push({ dayNumber, isOutsideMonth: true, dateString: '', hasBooking: false, bookingCount: 0 });
  }

  // Current month
  for (let day = 1; day <= daysInCurrentMonth; day++) {
    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const bookingCount = bookingCountMap.get(dateString) || 0;
    
    allDays.push({ 
      dayNumber: day, 
      isOutsideMonth: false, 
      dateString, 
      hasBooking: bookingCount > 0, 
      bookingCount 
    });
  }

  // Next month padding
  const totalCells = Math.ceil(allDays.length / 7) * 7;
  const remainingCells = totalCells - allDays.length;
  for (let i = 1; i <= remainingCells; i++) {
    allDays.push({ dayNumber: i, isOutsideMonth: true, dateString: '', hasBooking: false, bookingCount: 0 });
  }

  return allDays;
});

const selectedDateBookings = computed(() => {
  if (!selectedDate.value) return [];
  return bookingStore.bookings.filter(b => {
    const bDate = new Date(b.booking_date).toISOString().split('T')[0];
    return bDate === selectedDate.value;
  });
});

const formatDateLong = (dateStr: string) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
};

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

/* --- Calendar Specific Styles --- */
.calendar-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
}

.calendar-title-header {
  color: #1e4449;
  font-weight: 700;
  font-size: 1.25rem;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  text-align: center;
}

.calendar-header {
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  font-size: 0.75rem;
  padding: 10px 0;
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  background: #f8fafc;
  border: 1px solid transparent;
}

.calendar-day:hover:not(.day-outside-month) {
  background: #f1f5f9;
  transform: translateY(-2px);
}

.day-outside-month {
  opacity: 0.3;
  cursor: default;
  background: transparent;
}

.day-number {
  font-weight: 600;
  color: #1e293b;
}

.day-has-booking {
  background: #ecfdf5;
  border-color: #10b981;
}

.day-is-selected {
  background: #10b981 !important;
  color: white !important;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.day-is-selected .day-number,
.day-is-selected .booking-badge {
  color: white;
}

.booking-badge {
  position: absolute;
  top: 5px;
  right: 5px;
  background: #10b981;
  color: white;
  font-size: 0.65rem;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-weight: 700;
}

.booking-detail-item {
  transition: all 0.2s ease;
}

.booking-detail-item:hover {
  border-color: #10b981 !important;
  background-color: #f0fdf4;
}

.text-dark-teal {
  color: #1e4449;
}

.btn-outline-dark-teal {
  color: #1e4449;
  border-color: #1e4449;
}

.btn-outline-dark-teal:hover {
  background-color: #1e4449;
  color: white;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
