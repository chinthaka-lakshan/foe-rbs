<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <h2 class="section-title">Bookings</h2>

    <div class="mb-4 filter-row">
      <div class="row g-3">
        
        <div class="col-sm-6 col-md-3">
          <select class="form-select" v-model="selectedResource">
            <option value="">All Resources</option>
            <option value="1">Conference Room A</option>
            <option value="2">Sports Hall</option>
            <option value="3">Library Study Room</option>
            <option value="4">Medical Lab</option>
            <option value="5">Basketball Court</option>
          </select>
        </div>
        
        <div class="col-sm-6 col-md-3">
          <select class="form-select" v-model="selectedStatus">
            <option value="">All Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        
        <div class="col-sm-6 col-md-3">
            <div class="input-group">
                <input 
                    type="date" 
                    class="form-control" 
                    v-model="startDate" 
                    placeholder="Start Date"
                >
                <span class="input-group-text calendar-icon-fix">
                    <i class="bi bi-calendar-range"></i>
                </span>
            </div>
        </div>
        
        <div class="col-sm-6 col-md-3">
            <div class="input-group">
                <input 
                    type="date" 
                    class="form-control" 
                    v-model="endDate" 
                    placeholder="End Date"
                >
                <span class="input-group-text calendar-icon-fix">
                    <i class="bi bi-calendar-range"></i>
                </span>
            </div>
        </div>
        
      </div>
    </div>

    <div class="calendar-card mb-4">
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
                'day-has-booking': day.hasApprovedBooking,
                'day-is-start': day.dateString && day.dateString === startDate,
                'day-is-end': day.dateString && day.dateString === endDate,
                'day-in-range': day.dateString && isDateInRange(day.dateString)
            }"
            @click="day.dateString && updateDateRange(day.dateString)"
            :title="day.hasApprovedBooking ? `Bookings on ${day.dayNumber}` : ''"
          >
            <span class="day-label" v-if="day.dateString === startDate">Start</span>
            <span class="day-label" v-else-if="day.dateString === endDate">End</span>
            <span class="day-number">{{ day.dayNumber }}</span>
          </div>
      </div>
    </div>

    <div class="table-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">All Bookings (Filtered: {{ filteredBookings.length }})</h5>
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

interface Booking {
    id: number;
    userId: string;
    resourceName: string;
    date: string; // YYYY-MM-DD format
    startTime: string;
    endTime: string;
    status: 'approved' | 'pending' | 'rejected';
}

const selectedResource = ref('');
const selectedStatus = ref('');
const startDate = ref(''); 
const endDate = ref('');   

// --- Mock Booking Data ---
const bookings = ref<Booking[]>([
    { id: 1, userId: 'U001', resourceName: 'Conference Room A', date: '2025-10-25', startTime: '10:00 AM', endTime: '12:00 PM', status: 'approved' },
    { id: 2, userId: 'U002', resourceName: 'Sports Hall', date: '2025-10-26', startTime: '2:00 PM', endTime: '4:00 PM', status: 'pending' },
    { id: 3, userId: 'U003', resourceName: 'Library Study Room', date: '2025-10-27', startTime: '9:00 AM', endTime: '11:00 AM', status: 'rejected' },
    { id: 4, userId: 'U004', resourceName: 'Medical Lab', date: '2025-10-28', startTime: '1:00 PM', endTime: '3:00 PM', status: 'approved' },
    { id: 5, userId: 'U005', resourceName: 'Basketball Court', date: '2025-10-29', startTime: '4:00 PM', endTime: '6:00 PM', status: 'pending' },
    
    { id: 6, userId: 'U006', resourceName: 'Conference Room A', date: '2025-11-15', startTime: '1:00 PM', endTime: '3:00 PM', status: 'approved' },
    { id: 7, userId: 'U007', resourceName: 'Sports Hall', date: '2025-11-20', startTime: '5:00 PM', endTime: '7:00 PM', status: 'approved' },
    { id: 8, userId: 'U008', resourceName: 'Medical Lab', date: '2025-11-22', startTime: '8:00 AM', endTime: '10:00 AM', status: 'approved' },
    { id: 9, userId: 'U009', resourceName: 'Library Study Room', date: '2025-11-28', startTime: '10:00 AM', endTime: '12:00 PM', status: 'pending' },
    
    { id: 10, userId: 'U010', resourceName: 'Conference Room A', date: '2025-12-10', startTime: '1:00 PM', endTime: '3:00 PM', status: 'approved' },
    
]);

// --- Utility Functions ---

const parseDate = (dateString: string) => {
    return dateString.replace(/-/g, '');
};

const updateDateRange = (dateString: string) => {
    const clickedDateNum = parseDate(dateString);
    const startNum = startDate.value ? parseDate(startDate.value) : 0;
    const endNum = endDate.value ? parseDate(endDate.value) : Infinity;

    if (!startDate.value || clickedDateNum < startNum || (startDate.value && endDate.value)) {
        startDate.value = dateString;
        endDate.value = '';
    } else if (clickedDateNum > startNum) {
        endDate.value = dateString;
    } else {
        startDate.value = '';
        endDate.value = '';
    }
};

const isDateInRange = (dateString: string) => {
    const checkNum = parseDate(dateString);
    const startNum = startDate.value ? parseDate(startDate.value) : 0;
    const endNum = endDate.value ? parseDate(endDate.value) : 0;

    if (startNum && endNum && startNum < endNum) {
        return checkNum > startNum && checkNum < endNum;
    }
    return false;
};

// --- Calendar Logic ---
const initialDate = new Date(2025, 10, 1); 
const currentDate = ref(initialDate); 

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const currentMonthName = computed(() => 
    currentDate.value.toLocaleString('default', { month: 'long' })
);
const currentYear = computed(() => currentDate.value.getFullYear());

const changeMonth = (delta: number) => {
    const newMonthDate = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + delta, 1);
    currentDate.value = newMonthDate;
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

    // Add padding days (from previous month)
    for (let i = startingDayOfWeekIndex; i > 0; i--) {
        const dayNumber = daysInPreviousMonth - i + 1;
        allDays.push({ dayNumber, isOutsideMonth: true, dateString: '' });
    }

    // Prepare approved booking dates for quick lookup
    const approvedBookingDates = new Set(
        bookings.value
            .filter(b => b.status === 'approved')
            .map(b => b.date)
    );

    // Add days of the current month
    for (let day = 1; day <= daysInCurrentMonth; day++) {
        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const hasApprovedBooking = approvedBookingDates.has(dateString);
        
        allDays.push({ dayNumber: day, isOutsideMonth: false, dateString, hasApprovedBooking });
    }

    // Add padding days (from next month) to fill the grid
    const remainingCells = 42 - allDays.length; 
    for (let i = 1; i <= remainingCells; i++) {
        allDays.push({ dayNumber: i, isOutsideMonth: true, dateString: '' });
    }

    return allDays.slice(0, Math.ceil(allDays.length / 7) * 7);
});


// --- Table and CRUD Logic ---

const filteredBookings = computed(() => {
    const startNum = startDate.value ? parseDate(startDate.value) : 0;
    const endNum = endDate.value ? parseDate(endDate.value) : Infinity;

    return bookings.value.filter(booking => {
        const matchesResource = !selectedResource.value || booking.id.toString() === selectedResource.value;
        const matchesStatus = !selectedStatus.value || booking.status === selectedStatus.value;
        
        // Date Range Filtering
        const bookingDateNum = parseDate(booking.date);
        const matchesDateRange = bookingDateNum >= startNum && bookingDateNum <= endNum;

        return matchesResource && matchesStatus && matchesDateRange;
    });
});

const getStatusClass = (status: string) => {
    switch (status) {
        case 'approved': return 'bg-success';
        case 'pending': return 'bg-warning text-dark';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
};

const approveBooking = (id: number) => {
    const booking = bookings.value.find(b => b.id === id);
    if (booking) {
        booking.status = 'approved';
        bookings.value = [...bookings.value];
    }
};

const deleteBooking = (id: number) => {
    const index = bookings.value.findIndex(b => b.id === id);
    if (index !== -1) {
        bookings.value.splice(index, 1);
        bookings.value = [...bookings.value];
    }
};

</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
  margin-left: 260px;
  padding: 20px; 
}

@media (max-width: 768px) {
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

.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}

/* Fix for filter row layout */
.small-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}

.calendar-title-header {
    color: #1e4449;
    font-weight: 600;
}

/* Custom styling for the input group icon */
.input-group .form-control {
    border-right: none; /* Hide separation line between input and text */
}

.calendar-icon-fix {
    background-color: #f8f9fa; /* Light grey background for icon */
    color: #495057;
    border-left: none;
}

/* --- Calendar Specific Styles --- */
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    text-align: center;
    user-select: none;
}

.calendar-header {
    font-weight: 600;
    color: #1e4449;
    padding: 8px 0;
    font-size: 0.9em;
}

.calendar-day {
    position: relative; 
    padding: 8px 0; 
    min-height: 45px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.2s;
    font-weight: 500;
    font-size: 0.9em;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Inner elements for stacking */
.day-label {
    font-size: 0.65rem;
    font-weight: 700;
    line-height: 1;
    text-transform: uppercase;
    color: #1e4449;
    margin-bottom: 2px;
}
.day-number {
    line-height: 1;
}

.calendar-day:not(.day-outside-month):hover {
    background-color: #fcc30040;
    transform: scale(1.05);
}

.day-outside-month {
    color: #ccc;
    cursor: default;
}

/* Range Styling */
.day-in-range {
    background-color: #e6f7ff; /* Light blue background */
    border-radius: 0;
}
.day-is-start, .day-is-end {
    background-color: #fcc300 !important; /* Yellow highlight for boundaries */
    color: #1e4449 !important;
    font-weight: 700;
    border: 1px solid #1e4449;
}
.day-is-start .day-label, .day-is-end .day-label {
    color: #1e4449 !important;
}

/* Existing Approved Booking Style */
.day-has-booking {
    background-color: #4BB66D; /* Success background */
    color: white;
    font-weight: 700;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.day-has-booking .day-label {
    color: white;
}

/* --- Table and Button Styles --- */

.table thead {
  background: #f8f9fa;
}

.btn-outline-dark-teal {
    --bs-btn-color: #1e4449;
    --bs-btn-border-color: #1e4449;
    --bs-btn-hover-bg: #fcc300;
    --bs-btn-hover-color: #ffffff;
    --bs-btn-hover-border-color: #fcc300;
}

.btn-success {
  background-color: #4BB66D; /* Custom green */
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* Badge classes used in the table */
.bg-warning {
    background-color: #ffc107 !important;
}
.bg-danger {
    background-color: #dc3545 !important;
}
.bg-secondary {
    background-color: #6c757d !important;
}
.text-dark { 
    color: #212529 !important;
}

/* Action button styles */
.btn-group-sm .btn {
    padding: 0.25rem 0.4rem; /* Reduced padding for compact action buttons */
    font-size: 0.8rem;
}
.btn-outline-success {
    --bs-btn-color: #4BB66D;
    --bs-btn-border-color: #4BB66D;
    --bs-btn-hover-bg: #4BB66D;
    --bs-btn-hover-color: white;
}
.btn-outline-danger {
    --bs-btn-color: #dc3545;
    --bs-btn-border-color: #dc3545;
    --bs-btn-hover-bg: #dc3545;
    --bs-btn-hover-color: white;
}
</style>