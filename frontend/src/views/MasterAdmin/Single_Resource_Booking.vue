<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Book Resource: <span class="text-dark-teal">{{ resource?.name || 'Loading...' }}</span></h2>
      <button class="btn btn-outline-dark-teal" @click="router.back()">
        <i class="bi bi-arrow-left me-1"></i> Back
      </button>
    </div>

    <div v-if="loading" class="alert alert-info text-center">
        Loading resource details...
    </div>
    
    <div v-else-if="!resource" class="alert alert-danger text-center">
        Resource not found. Cannot proceed with booking.
    </div>

    <div v-else class="card p-4 shadow-sm">
      <form @submit.prevent="submitBooking">
        <div class="row g-4">
          <div class="col-md-12 mb-3 border-bottom pb-3">
            <p class="fw-bold mb-1 fs-5">
                Location: {{ resource.location }} 
                <span class="badge" :class="resource.status === 'active' ? 'bg-success' : 'bg-secondary'">
                    {{ resource.status.toUpperCase() }}
                </span>
            </p>
            <p class="text-muted small mb-0">Category: {{ resource.category }} | Base Price: Rs. {{ resource.price?.toFixed(2) || '0.00' }}</p>
          </div>

          <div class="col-lg-6">
              <h5 class="fw-bold mb-3 section-subtitle">Booking History Calendar</h5>
              
              <div class="calendar-card border p-3 rounded bg-light mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                      <button class="btn btn-sm btn-outline-dark-teal" type="button" @click="changeMonth(-1)">
                          <i class="bi bi-chevron-left"></i>
                      </button>
                      <h6 class="mb-0 calendar-title-header">{{ currentMonthName }} {{ currentYear }}</h6>
                      <button class="btn btn-sm btn-outline-dark-teal" type="button" @click="changeMonth(1)">
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
                            'day-is-booked': day.isBooked,
                            'day-is-selected': day.dateString && day.dateString === dateFilter,
                        }"
                        @click="day.dateString && selectDate(day.dateString)"
                        :title="day.isBooked ? 'Already Booked' : 'Available'"
                      >
                        <span class="day-number">{{ day.dayNumber }}</span>
                      </div>
                  </div>
              </div>

              <h5 class="fw-bold mb-3 section-subtitle">Upcoming Bookings (Filtered by Calendar)</h5>
              <div class="booking-table-placeholder border p-3 rounded bg-white">
                  <div v-if="filteredUpcomingBookings.length === 0" class="text-muted text-center py-4 small">
                      No upcoming bookings found.
                  </div>
                  <div v-else class="table-responsive history-list">
                      <table class="table table-sm table-striped mb-0 small">
                          <thead>
                              <tr>
                                  <th>Date</th>
                                  <th>Time</th>
                                  <th>User</th>
                                  <th>Status</th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr v-for="booking in filteredUpcomingBookings" :key="booking.id">
                                  <td>{{ booking.date }}</td>
                                  <td>{{ booking.startTime }} - {{ booking.endTime }}</td>
                                  <td>{{ booking.userId }}</td>
                                  <td>
                                      <span class="badge" :class="getBookingStatusClass(booking.status)">
                                          {{ booking.status }}
                                      </span>
                                  </td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>


          <div class="col-lg-6">
            <h5 class="fw-bold mb-3 section-subtitle">1. Reservation Details</h5>

            <div class="date-time-box border p-3 rounded">
                <label for="bookingDate" class="form-label fw-bold">Select Date <span class="text-danger">*</span></label>
                <input 
                  type="date" 
                  class="form-control mb-3" 
                  id="bookingDate" 
                  v-model="bookingData.date" 
                  required
                  :min="todayDateString"
                >

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="startTime" class="form-label small-label">Start Time <span class="text-danger">*</span></label>
                        <input 
                          type="time" 
                          class="form-control" 
                          id="startTime" 
                          v-model="bookingData.startTime" 
                          required
                        >
                    </div>
                    <div class="col-6">
                        <label for="endTime" class="form-label small-label">End Time <span class="text-danger">*</span></label>
                        <input 
                          type="time" 
                          class="form-control" 
                          id="endTime" 
                          v-model="bookingData.endTime" 
                          required
                        >
                    </div>
                </div>

                <small v-if="isCurrentDayAvailable" class="text-success fw-bold">
                    <i class="bi bi-check-circle-fill me-1"></i> Resource is AVAILABLE for reservation.
                </small>
                <small v-else class="text-danger fw-bold">
                    <i class="bi bi-x-circle-fill me-1"></i> Resource is UNAVAILABLE on this day. (Check weekly schedule)
                </small>
            </div>
            
            <div class="schedule-reference mt-4 border p-3 rounded bg-light">
                <h6 class="fw-bold mb-2 section-subtitle">Weekly Schedule Summary</h6>
                <ul class="list-unstyled mb-0 small">
                    <li v-for="day in resource.schedule" :key="day.dayName" class="d-flex justify-content-between py-1">
                        <span class="fw-medium">{{ day.dayName }}</span>
                        <span :class="day.available ? 'text-success' : 'text-danger'">
                            <span v-if="day.available">
                                {{ day.startTime }} - {{ day.endTime }}
                            </span>
                            <span v-else>
                                Unavailable
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
            
            <h5 class="fw-bold mb-3 mt-4 section-subtitle">2. Equipment & Purpose</h5>

            <div class="equipment-selection-box border p-3 rounded bg-light mb-3">
                <div v-if="!resource.equipment || resource.equipment.length === 0" class="text-muted text-center py-2 small">
                    No optional equipment available.
                </div>
                
                <div 
                    v-for="item in resource.equipment" 
                    :key="item.name" 
                    class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-2"
                >
                    <div class="form-check me-3 flex-grow-1">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            v-model="item.checked"
                            :id="'eq-' + item.name"
                        >
                        <label class="form-check-label" :for="'eq-' + item.name">
                            {{ item.name }} 
                            <small class="text-muted ms-2">(Rs. {{ item.price?.toFixed(2) || '0.00' }})</small>
                        </label>
                    </div>

                    <div class="w-25 input-group input-group-sm">
                        <input 
                            type="number" 
                            class="form-control text-center" 
                            v-model.number="item.quantity"
                            min="1"
                            :disabled="!item.checked"
                        >
                    </div>
                </div>
            </div>

            <div class="col-12">
                <label for="purpose" class="form-label fw-bold small-label">Purpose/Notes</label>
                <textarea 
                  class="form-control" 
                  id="purpose" 
                  v-model="bookingData.purpose"
                  rows="2"
                  placeholder="e.g., Team meeting, presentation practice, etc."
                ></textarea>
            </div>


            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
              <button 
                type="submit" 
                class="btn btn-success" 
                :disabled="!resource.status || resource.status !== 'active' || !isCurrentDayAvailable || !bookingData.date"
              >
                <i class="bi bi-send me-1"></i> Send Booking Request
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

// NOTE: Adjust these paths if necessary
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const router = useRouter();
const route = useRoute();

// --- DATA INTERFACES ---

interface ScheduleDay { dayName: string; available: boolean; startTime: string; endTime: string; }
interface EquipmentItem { name: string; price: number | null; checked: boolean; quantity?: number; } 

interface Resource {
    id: number;
    name: string;
    location?: string;
    category: string;
    price: number | null; 
    status: 'active' | 'inactive';
    equipment?: EquipmentItem[]; 
    schedule?: ScheduleDay[]; 
}
interface GlobalBooking {
    id: number;
    resourceId: number;
    date: string;
    startTime: string;
    endTime: string;
    status: 'approved' | 'pending' | 'rejected';
    userId: string;
}

interface BookingForm {
    date: string;
    startTime: string;
    endTime: string;
    purpose: string;
}

// --- STATE ---

const resource = ref<Resource | null>(null);
const loading = ref(true);
const dateFilter = ref(''); // Used to filter the table list by clicking the calendar
const todayDateString = new Date().toISOString().split('T')[0]; 

const bookingData = ref<BookingForm>({
    date: todayDateString, 
    startTime: '09:00',
    endTime: '10:00',
    purpose: '',
});

// --- CALENDAR STATE ---
const calendarDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1)); // First of current month
const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// --- MOCK BOOKING HISTORY (for calendar/display) ---
const MOCK_BOOKING_HISTORY: GlobalBooking[] = [
    { id: 1, resourceId: 1, date: '2025-11-15', startTime: '10:00', endTime: '12:00', status: 'approved', userId: 'U001' },
    { id: 2, resourceId: 1, date: '2025-11-20', startTime: '14:00', endTime: '16:00', status: 'pending', userId: 'U002' },
    { id: 3, resourceId: 1, date: '2025-12-05', startTime: '11:00', endTime: '13:00', status: 'approved', userId: 'U003' },
    { id: 4, resourceId: 2, date: '2025-12-10', startTime: '15:00', endTime: '16:00', status: 'approved', userId: 'U004' },
    { id: 5, resourceId: 3, date: '2025-11-10', startTime: '15:00', endTime: '16:00', status: 'approved', userId: 'U005' },
];

// --- UTILITIES & FETCHING ---

const getStoredResources = (): Resource[] => {
    const storedResourcesString = localStorage.getItem('resources');
    return storedResourcesString ? JSON.parse(storedResourcesString) : [];
};

const parseDate = (dateString: string) => {
    // Converts YYYY-MM-DD string to a number (YYYYMMDD) for numerical comparison
    return Number(dateString.replace(/-/g, ''));
};

const fetchResourceDetails = (id: number) => {
    const latestResources = getStoredResources();
    const fetchedResource = latestResources.find(r => r.id === id);
    
    loading.value = false;

    if (fetchedResource) {
        if (fetchedResource.price === undefined) fetchedResource.price = null;
        if (fetchedResource.equipment === undefined || !Array.isArray(fetchedResource.equipment)) {
             fetchedResource.equipment = [];
        } else {
             fetchedResource.equipment = fetchedResource.equipment.map(item => ({
                 ...item,
                 quantity: item.quantity || 1 
             }));
        }
        if (fetchedResource.schedule === undefined) fetchedResource.schedule = [];

        resource.value = fetchedResource;
    } else {
        resource.value = null;
    }
};

// --- CALENDAR ACTIONS ---

const selectDate = (dateString: string) => {
    // 1. Update the Reservation Form date input
    bookingData.value.date = dateString; 
    
    // 2. Update the table filter
    dateFilter.value = dateString; 
};

const changeMonth = (delta: number) => {
    calendarDate.value = new Date(calendarDate.value.getFullYear(), calendarDate.value.getMonth() + delta, 1);
};


// --- COMPUTED PROPERTIES ---

const isCurrentDayAvailable = computed(() => {
    if (!bookingData.value.date || !resource.value || !resource.value.schedule) {
        return false;
    }
    
    const jsDayOfWeek = new Date(bookingData.value.date).getDay();
    const scheduleIndex = (jsDayOfWeek + 6) % 7; 
    
    const daySchedule = resource.value.schedule[scheduleIndex];
    return daySchedule?.available === true;
});

const currentMonthName = computed(() => 
    calendarDate.value.toLocaleString('default', { month: 'long' })
);
const currentYear = computed(() => calendarDate.value.getFullYear());

const daysInMonth = computed(() => {
    const year = calendarDate.value.getFullYear();
    const month = calendarDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const jsDayOfWeek = firstDayOfMonth.getDay();
    const startingDayOfWeekIndex = (jsDayOfWeek + 6) % 7; 
    
    const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
    const daysInPreviousMonth = new Date(year, month, 0).getDate();
    const allDays = [];

    const bookedDates = new Set(
        MOCK_BOOKING_HISTORY
            .filter(b => b.resourceId === resource.value?.id)
            .map(b => b.date)
    );

    // Add padding days
    for (let i = startingDayOfWeekIndex; i > 0; i--) {
        allDays.push({ dayNumber: daysInPreviousMonth - i + 1, isOutsideMonth: true, dateString: '' });
    }

    // Add current month days
    for (let day = 1; day <= daysInCurrentMonth; day++) {
        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isBooked = bookedDates.has(dateString);
        
        allDays.push({ dayNumber: day, isOutsideMonth: false, dateString, isBooked });
    }

    // Add padding days (from next month)
    const remainingCells = 42 - allDays.length; 
    for (let i = 1; i <= remainingCells; i++) {
        allDays.push({ dayNumber: i, isOutsideMonth: true, dateString: '' });
    }

    return allDays.slice(0, Math.ceil(allDays.length / 7) * 7);
});


const filteredUpcomingBookings = computed<GlobalBooking[]>(() => {
    if (!resource.value) return [];
    
    const resourceId = resource.value.id;
    const todayNum = parseDate(todayDateString);
    
    let filtered = MOCK_BOOKING_HISTORY.filter(b => b.resourceId === resourceId);

    // Filter 1: Only TODAY and FUTURE dates
    filtered = filtered.filter(b => parseDate(b.date) >= todayNum);

    // Filter 2: Filter by the date selected on the calendar/form
    if (dateFilter.value) {
        filtered = filtered.filter(b => b.date === dateFilter.value);
    }
    
    // Sort by date
    return filtered.sort((a, b) => parseDate(a.date) - parseDate(b.date));
});

const getBookingStatusClass = (status: string) => {
    switch (status) {
        case 'approved': return 'bg-success';
        case 'pending': return 'bg-warning text-dark';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
};

// --- SUBMISSION LOGIC ---

const submitBooking = () => {
    if (resource.value?.status !== 'active') { 
    alert("Cannot book: Resource is inactive or not loaded."); 
    return;}
    if (!bookingData.value.date || !bookingData.value.startTime || !bookingData.value.endTime) { alert("Please select a valid date, start time, and end time."); return; }
    if (!isCurrentDayAvailable.value) { alert("Booking failed: Resource is not available on the selected day according to its weekly schedule."); return; }

    const selectedEquipment = resource.value.equipment
        ?.filter(item => item.checked)
        .map(item => ({
            name: item.name,
            price: item.price,
            quantity: item.quantity,
        }));
    
    const bookingPayload = {
        resourceId: resource.value.id,
        date: bookingData.value.date,
        startTime: bookingData.value.startTime,
        endTime: bookingData.value.endTime,
        purpose: bookingData.value.purpose,
        equipment: selectedEquipment,
        status: 'pending', 
        createdAt: new Date().toISOString(),
    };
    
    console.log("Submitting Booking Payload:", bookingPayload);
    
    alert(`Booking Request Sent for ${resource.value.name}! Status: Pending Review.`);

    router.push('/master-admin/booking'); 
};

// --- LIFECYCLE ---

onMounted(() => {
    const resourceId = parseInt(route.query.resourceId as string);
    if (resourceId) {
        fetchResourceDetails(resourceId);
    } else {
        loading.value = false;
        resource.value = null;
    }
});
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
.section-title {
    color: #1e4449;
    font-weight: 600;
    margin-bottom: 24px;
}
.section-subtitle {
    color: #1e4449;
    font-size: 1.1rem;
}
.small-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}
.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}
.card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* --- Calendar Styles --- */
.calendar-card {
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.calendar-title-header {
    color: #1e4449;
    font-weight: 600;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 3px;
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
    padding: 8px 0;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
    font-weight: 500;
    font-size: 0.9em;
}
.day-outside-month {
    color: #ccc;
    cursor: default;
}
.calendar-day:not(.day-outside-month):hover {
    background-color: #fcc30040;
}

/* Calendar Booking Statuses */
.day-is-booked {
    background-color: #dc354540; /* Light Red for existing booking */
    color: #dc3545;
    font-weight: 700;
}
.day-is-booked:hover {
    background-color: #dc354560;
}
.day-is-selected {
    background-color: #fcc300; /* Yellow for selection */
    color: #1e4449;
    border: 1px solid #1e4449;
}
.day-is-selected:hover {
    background-color: #e6b200;
}

/* --- Layouts & Components --- */
.date-time-box, .equipment-selection-box, .schedule-reference {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
.schedule-reference li {
    padding: 0.25rem 0.5rem;
    border-bottom: 1px solid #eee;
}
.equipment-selection-box .form-check-label {
    font-size: 0.95rem;
}

/* Booking List Table Styling */
.booking-table-placeholder {
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
.history-list {
    max-height: 250px;
    overflow-y: auto;
}
.booking-table-placeholder th {
    font-size: 0.8rem;
    color: #1e4449;
    font-weight: 600;
}
.booking-table-placeholder td {
    font-size: 0.85rem;
}


/* --- Button & Color Classes --- */
.btn-outline-dark-teal {
  --bs-btn-color: #1e4449;
  --bs-btn-border-color: #1e4449;
  --bs-btn-hover-bg: #fcc300;
  --bs-btn-hover-color: #ffffff;
  --bs-btn-hover-border-color: #fcc300;
}
.btn-success {
    background-color: #4BB66D;
    border-color: #4BB66D;
}
.bg-success {
    background-color: #4BB66D !important;
}
.bg-warning {
    background-color: #ffc107 !important;
}
.bg-danger {
    background-color: #dc3545 !important;
}
.text-success {
    color: #4BB66D !important;
}
.text-danger {
    color: #dc3545 !important;
}
.text-dark {
    color: #212529 !important;
}
</style>