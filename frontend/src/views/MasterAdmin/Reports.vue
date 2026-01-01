<template>
  <navbar />
  <master-admin-sidebar />
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Reports Dashboard</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-dark-teal btn-sm" @click="exportAllToCSV">
          <i class="bi bi-file-earmark-excel me-1"></i>Export All CSV
        </button>
        <button class="btn btn-success btn-sm" @click="printReports">
          <i class="bi bi-printer me-1"></i>Print
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-4 col-lg-4 col-sm-6 col-12">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(30, 68, 73, 0.1);">
            <i class="bi bi-box-seam" style="color: #1e4449; font-size: 28px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalResources }}</h3>
            <p>Total Resources</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 col-lg-4 col-sm-6 col-12">
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
      <div class="col-md-4 col-lg-4 col-sm-6 col-12">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(38, 213, 22, 0.1);">
            <i class="bi bi-people" style="color: #26d516; font-size: 28px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalUsers }}</h3>
            <p>Total Users</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Global Date Range Filter -->
    <div class="filter-section mb-4">
      <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <h5 class="mb-0 text-dark-teal">Filter Reports by Date Range</h5>
        <div class="d-flex gap-2 flex-wrap">
          <button 
            class="btn btn-sm" 
            :class="dateRangeFilter === 'today' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
            @click="setDateRange('today')"
          >
            Today
          </button>
          <button 
            class="btn btn-sm" 
            :class="dateRangeFilter === 'week' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
            @click="setDateRange('week')"
          >
            This Week
          </button>
          <button 
            class="btn btn-sm" 
            :class="dateRangeFilter === 'month' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
            @click="setDateRange('month')"
          >
            This Month
          </button>
          <button 
            class="btn btn-sm" 
            :class="dateRangeFilter === 'year' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
            @click="setDateRange('year')"
          >
            This Year
          </button>
          <button 
            class="btn btn-sm" 
            :class="dateRangeFilter === 'custom' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
            @click="showCustomDatePicker()"
          >
            Custom
          </button>
        </div>
      </div>

      <div v-if="showCustomDateFields" class="row g-3">
        <div class="col-md-3 col-sm-6">
          <label class="form-label small text-muted">Start Date</label>
          <input 
            type="date" 
            class="form-control form-control-sm" 
            v-model="tempCustomStartDate"
          >
        </div>
        <div class="col-md-3 col-sm-6">
          <label class="form-label small text-muted">End Date</label>
          <input 
            type="date" 
            class="form-control form-control-sm" 
            v-model="tempCustomEndDate"
          >
        </div>
        <div class="col-md-4 col-sm-8 d-flex align-items-end gap-2">
          <button class="btn btn-sm btn-dark-teal" @click="applyCustomDateRange">
            Apply
          </button>
          <button class="btn btn-sm btn-outline-secondary" @click="cancelCustomDateRange">
            Cancel
          </button>
        </div>
      </div>
      
      <div class="text-muted small mt-2">
        Showing data from: <strong>{{ getDateRangeText() }}</strong>
      </div>
    </div>

    <!-- Rest of the template remains the same... -->
    <!-- Resources Report Section -->
    <div class="table-card mb-4" id="resources-report">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
          <h5 class="mb-0">Resources Report</h5>
          <span class="badge bg-dark-teal">{{ filteredResources.length }} resources</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <select class="form-select form-select-sm w-auto" v-model="resourceFilter.status">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Maintenance">Maintenance</option>
          </select>
          <select class="form-select form-select-sm w-auto" v-model="resourceFilter.category">
            <option value="">All Categories</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
          <input 
            type="text" 
            class="form-control form-control-sm" 
            style="min-width: 150px;"
            placeholder="Search resources..."
            v-model="resourceFilter.search"
          >
          <button class="btn btn-success btn-sm" @click="exportResourcesCSV">
            <i class="bi bi-download me-1"></i>CSV
          </button>
        </div>
      </div>
      
      <!-- Loading State for Resources -->
      <div v-if="isLoadingResources" class="text-center py-4">
        <div class="spinner-border spinner-border-sm text-dark-teal me-2"></div>
        <span class="text-muted">Loading resources...</span>
      </div>
      
      <!-- Error State for Resources -->
      <div v-if="resourceError" class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ resourceError }}
        <button type="button" class="btn-close" @click="resourceError = ''"></button>
      </div>
      
      <!-- Resources Table -->
      <div v-if="!isLoadingResources && !resourceError" class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Location</th>
              <th>Price</th>
              <th>Status</th>
              <th>Bookings</th>
              <th>Created Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="resource in filteredResources" :key="resource.id">
              <td>{{ resource.id }}</td>
              <td>
                <div>
                  <strong>{{ resource.name }}</strong>
                </div>
              </td>
              <td>{{ getCategoryName(resource.category_id) }}</td>
              <td>{{ resource.location_name || 'N/A' }}</td>
              <td>Rs. {{ formatPrice(resource.base_price) }}</td>
              <td>
                <span class="badge" :class="getResourceStatusClass(resource.status)">
                  {{ resource.status }}
                </span>
              </td>
              <td>
                <span class="badge bg-info">
                  {{ getResourceBookingCount(resource.id) }}
                </span>
              </td>
              <td>{{ formatDate(resource.created_at) }}</td>
            </tr>
          </tbody>
        </table>
        
        <!-- Empty State -->
        <div v-if="filteredResources.length === 0" class="text-center py-5">
          <i class="bi bi-inboxes" style="font-size: 3rem; color: #ccc;"></i>
          <p class="text-muted mt-3">No resources found matching your criteria</p>
        </div>
      </div>
    </div>

    <!-- Users Report Section -->
    <div class="table-card mb-4" id="users-report">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
          <h5 class="mb-0">Users Report</h5>
          <span class="badge bg-dark-teal">{{ filteredUsers.length }} users</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <input 
            type="text" 
            class="form-control form-control-sm" 
            style="min-width: 150px;"
            placeholder="Search users..."
            v-model="userFilter.search"
          >
          <select class="form-select form-select-sm w-auto" v-model="userFilter.role">
            <option value="">All Roles</option>
            <option value="Master Admin">Master Admin</option>
            <option value="Admin">Admin</option>
            <option value="User">User</option>
          </select>
          <select class="form-select form-select-sm w-auto" v-model="userFilter.status">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <button class="btn btn-success btn-sm" @click="exportUsersCSV">
            <i class="bi bi-download me-1"></i>CSV
          </button>
        </div>
      </div>
      
      <!-- Loading State for Users -->
      <div v-if="isLoadingUsers" class="text-center py-4">
        <div class="spinner-border spinner-border-sm text-dark-teal me-2"></div>
        <span class="text-muted">Loading users...</span>
      </div>
      
      <!-- Users Table -->
      <div v-if="!isLoadingUsers" class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Bookings</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id">
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span class="badge" :class="user.primaryRole.toLowerCase().includes('admin') ? 'bg-primary' : 'bg-info'">
                  {{ user.primaryRole }}
                </span>
              </td>
              <td>
                <span class="badge" :class="user.status === 'active' ? 'bg-success' : 'bg-secondary'">
                  {{ user.status }}
                </span>
              </td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td>
                <span class="badge bg-warning text-dark">
                  {{ getUserBookingCount(user.id) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Empty State -->
        <div v-if="filteredUsers.length === 0" class="text-center py-5">
          <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
          <p class="text-muted mt-3">No users found matching your criteria</p>
        </div>
      </div>
    </div>

    <!-- Bookings Report Section -->
    <div class="table-card" id="bookings-report">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
          <h5 class="mb-0">Bookings Report</h5>
          <span class="badge bg-dark-teal">{{ filteredBookings.length }} bookings</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <select class="form-select form-select-sm w-auto" v-model="bookingFilter.resource">
            <option value="">All Resources</option>
            <option v-for="res in resources" :key="res.id" :value="res.id">
              {{ res.name }}
            </option>
          </select>
          <select class="form-select form-select-sm w-auto" v-model="bookingFilter.status">
            <option value="">All Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <input 
            type="text" 
            class="form-control form-control-sm" 
            style="min-width: 150px;"
            placeholder="Search by user..."
            v-model="bookingFilter.search"
          >
          <button class="btn btn-success btn-sm" @click="exportBookingsCSV">
            <i class="bi bi-download me-1"></i>CSV
          </button>
        </div>
      </div>
      
      <!-- Loading State for Bookings -->
      <div v-if="isLoadingBookings" class="text-center py-4">
        <div class="spinner-border spinner-border-sm text-dark-teal me-2"></div>
        <span class="text-muted">Loading bookings...</span>
      </div>
      
      <!-- Bookings Table -->
      <div v-if="!isLoadingBookings" class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>User</th>
              <th>Resource</th>
              <th>Booking Date</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in filteredBookings" :key="booking.id">
              <td>{{ booking.id }}</td>
              <td>{{ booking.userName || `User ${booking.userId}` }}</td>
              <td>{{ getResourceName(booking.resourceId) }}</td>
              <td>{{ formatDate(booking.date) }}</td>
              <td>{{ formatTime(booking.startTime) }}</td>
              <td>{{ formatTime(booking.endTime) }}</td>
              <td>Rs. {{ formatPrice(booking.amount) }}</td>
              <td>
                <span class="badge" :class="getBookingStatusClass(booking.status)">
                  {{ booking.status }}
                </span>
              </td>
              <td>{{ formatDateTime(booking.created_at) }}</td>
            </tr>
          </tbody>
        </table>
        
        <!-- Empty State -->
        <div v-if="filteredBookings.length === 0" class="text-center py-5">
          <i class="bi bi-calendar" style="font-size: 3rem; color: #ccc;"></i>
          <p class="text-muted mt-3">No bookings found matching your criteria</p>
        </div>
      </div>
    </div>

    <!-- Loading Overlay for initial load -->
    <div v-if="isInitialLoading" class="loading-overlay">
      <div class="spinner-border text-dark-teal" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading reports...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';
import axios from 'axios';

// API Configuration - Based on your resource page
const API_BASE_URL = 'http://localhost:8000/api';

// Get auth token
const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('auth_token') || 
         localStorage.getItem('token');
};

// Interfaces
interface Resource {
  id: number;
  name: string;
  description?: string;
  location_name?: string;
  category_id: number;
  base_price: number | string;
  status: 'Active' | 'Inactive' | 'Maintenance';
  created_at: string;
  updated_at: string;
}

interface User {
  id: number | string;
  name: string;
  email: string;
  status: 'active' | 'inactive';
  primaryRole: string;
  created_at: string;
  last_login_at?: string;
}

interface Booking {
  id: number;
  userId: number | string;
  resourceId: number;
  date: string;
  startTime: string;
  endTime: string;
  status: 'approved' | 'pending' | 'rejected' | 'cancelled';
  amount?: number | string;
  created_at: string;
  userName?: string;
  userEmail?: string;
}

interface Category {
  id: number;
  name: string;
}

// State
const isInitialLoading = ref(false);
const isLoadingResources = ref(false);
const isLoadingUsers = ref(false);
const isLoadingBookings = ref(false);
const resourceError = ref('');

const resources = ref<Resource[]>([]);
const users = ref<User[]>([]);
const bookings = ref<Booking[]>([]);
const categories = ref<Category[]>([]);

// Filter states
const dateRangeFilter = ref('month'); // today, week, month, year, custom
const showCustomDateFields = ref(false);
const tempCustomStartDate = ref('');
const tempCustomEndDate = ref('');
const customStartDate = ref('');
const customEndDate = ref('');

const resourceFilter = ref({
  status: '',
  category: '',
  search: ''
});

const userFilter = ref({
  search: '',
  role: '',
  status: ''
});

const bookingFilter = ref({
  resource: '',
  status: '',
  search: ''
});

// Date range for filtering all sections
const currentDateRange = ref({ startDate: '', endDate: '' });

// Watch date range changes and apply to all sections
watch([dateRangeFilter], () => {
  if (dateRangeFilter.value !== 'custom') {
    updateCurrentDateRange();
    showCustomDateFields.value = false;
  }
});

// Function to update current date range
const updateCurrentDateRange = () => {
  const { startDate, endDate } = getDateRange();
  currentDateRange.value = { startDate, endDate };
};

// Computed properties
const stats = computed(() => ({
  totalResources: filteredResources.value.length,
  totalUsers: filteredUsers.value.length,
  totalBookings: filteredBookings.value.length
}));

const filteredResources = computed(() => {
  let filtered = resources.value;
  
  // Apply status filter
  if (resourceFilter.value.status) {
    filtered = filtered.filter(r => r.status === resourceFilter.value.status);
  }
  
  // Apply category filter - FIXED THIS PART
  if (resourceFilter.value.category) {
    // Convert both to string for comparison to handle number/string mismatch
    const selectedCategoryId = resourceFilter.value.category.toString();
    filtered = filtered.filter(r => {
      const resourceCategoryId = r.category_id?.toString() || '';
      return resourceCategoryId === selectedCategoryId;
    });
  }
  
  // Apply search filter
  if (resourceFilter.value.search) {
    const search = resourceFilter.value.search.toLowerCase();
    filtered = filtered.filter(r => 
      r.name.toLowerCase().includes(search) || 
      (r.description && r.description.toLowerCase().includes(search)) ||
      (r.location_name && r.location_name.toLowerCase().includes(search))
    );
  }
  
  // Apply date range filter to creation date
  const { startDate, endDate } = currentDateRange.value;
  if (startDate && endDate) {
    filtered = filtered.filter(r => {
      const createdDate = new Date(r.created_at).toISOString().split('T')[0];
      return createdDate >= startDate && createdDate <= endDate;
    });
  }
  
  return filtered;
});

const filteredUsers = computed(() => {
  let filtered = users.value;
  
  // Apply search filter
  if (userFilter.value.search) {
    const search = userFilter.value.search.toLowerCase();
    filtered = filtered.filter(u => 
      u.name.toLowerCase().includes(search) || 
      u.email.toLowerCase().includes(search)
    );
  }
  
  // Apply role filter
  if (userFilter.value.role) {
    filtered = filtered.filter(u => u.primaryRole === userFilter.value.role);
  }
  
  // Apply status filter
  if (userFilter.value.status) {
    filtered = filtered.filter(u => u.status === userFilter.value.status);
  }
  
  // Apply date range filter to creation date
  const { startDate, endDate } = currentDateRange.value;
  if (startDate && endDate) {
    filtered = filtered.filter(u => {
      const createdDate = new Date(u.created_at).toISOString().split('T')[0];
      return createdDate >= startDate && createdDate <= endDate;
    });
  }
  
  return filtered;
});

const filteredBookings = computed(() => {
  let filtered = bookings.value;
  
  // Apply resource filter
  if (bookingFilter.value.resource) {
    filtered = filtered.filter(b => b.resourceId.toString() === bookingFilter.value.resource);
  }
  
  // Apply status filter
  if (bookingFilter.value.status) {
    filtered = filtered.filter(b => b.status === bookingFilter.value.status);
  }
  
  // Apply search filter
  if (bookingFilter.value.search) {
    const search = bookingFilter.value.search.toLowerCase();
    filtered = filtered.filter(b => 
      (b.userName && b.userName.toLowerCase().includes(search)) ||
      (b.userEmail && b.userEmail.toLowerCase().includes(search))
    );
  }
  
  // Apply date range filter to booking date
  const { startDate, endDate } = currentDateRange.value;
  if (startDate && endDate) {
    filtered = filtered.filter(b => {
      const bookingDate = b.date.split(' ')[0]; // Get date part only
      return bookingDate >= startDate && bookingDate <= endDate;
    });
  }
  
  return filtered;
});

// Helper functions
const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'N/A';
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  } catch (error) {
    return 'N/A';
  }
};

const formatDateTime = (dateTimeString: string) => {
  if (!dateTimeString) return 'N/A';
  try {
    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) return 'N/A';
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (error) {
    return 'N/A';
  }
};

const formatTime = (timeString: string) => {
  if (!timeString) return 'N/A';
  return timeString;
};

const formatPrice = (price: number | string | undefined): string => {
  if (price === undefined || price === null) return '0.00';
  
  let numericPrice: number;
  if (typeof price === 'string') {
    const cleanPrice = price.replace(/[^\d.-]/g, '');
    numericPrice = parseFloat(cleanPrice);
  } else {
    numericPrice = price;
  }
  
  if (isNaN(numericPrice) || !isFinite(numericPrice)) {
    return '0.00';
  }
  
  return numericPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const getResourceStatusClass = (status: string) => {
  switch (status) {
    case 'Active': return 'bg-success';
    case 'Inactive': return 'bg-secondary';
    case 'Maintenance': return 'bg-warning text-dark';
    default: return 'bg-secondary';
  }
};

const getBookingStatusClass = (status: string) => {
  switch (status) {
    case 'approved': return 'bg-success';
    case 'pending': return 'bg-warning text-dark';
    case 'rejected': return 'bg-danger';
    case 'cancelled': return 'bg-secondary';
    default: return 'bg-secondary';
  }
};

const getCategoryName = (categoryId: number) => {
  const category = categories.value.find(c => c.id === categoryId);
  return category ? category.name : 'Unknown Category';
};

const getResourceName = (resourceId: number) => {
  const resource = resources.value.find(r => r.id === resourceId);
  return resource ? resource.name : 'Unknown Resource';
};

const getResourceBookingCount = (resourceId: number) => {
  const { startDate, endDate } = currentDateRange.value;
  let count = bookings.value.filter(b => b.resourceId === resourceId).length;
  
  // If date range is set, filter bookings by date
  if (startDate && endDate) {
    count = bookings.value.filter(b => {
      if (b.resourceId !== resourceId) return false;
      const bookingDate = b.date.split(' ')[0];
      return bookingDate >= startDate && bookingDate <= endDate;
    }).length;
  }
  
  return count;
};

const getUserBookingCount = (userId: number | string) => {
  const { startDate, endDate } = currentDateRange.value;
  let count = bookings.value.filter(b => b.userId === userId).length;
  
  // If date range is set, filter bookings by date
  if (startDate && endDate) {
    count = bookings.value.filter(b => {
      if (b.userId !== userId) return false;
      const bookingDate = b.date.split(' ')[0];
      return bookingDate >= startDate && bookingDate <= endDate;
    }).length;
  }
  
  return count;
};

const getDateRangeText = () => {
  const { startDate, endDate } = currentDateRange.value;
  if (!startDate || !endDate) return 'All Time';
  
  const start = new Date(startDate);
  const end = new Date(endDate);
  
  if (isNaN(start.getTime()) || isNaN(end.getTime())) return 'All Time';
  
  if (dateRangeFilter.value === 'today') {
    return start.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  } else if (dateRangeFilter.value === 'week') {
    return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
  } else if (dateRangeFilter.value === 'month') {
    return start.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  } else if (dateRangeFilter.value === 'year') {
    return start.getFullYear().toString();
  } else if (dateRangeFilter.value === 'custom') {
    return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
  }
  return 'All Time';
};

const getDateRange = () => {
  const today = new Date();
  let startDate = '';
  let endDate = '';
  
  try {
    switch (dateRangeFilter.value) {
      case 'today':
        startDate = today.toISOString().split('T')[0];
        endDate = startDate;
        break;
      case 'week':
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - today.getDay()); // Start from Sunday
        startDate = weekStart.toISOString().split('T')[0];
        endDate = today.toISOString().split('T')[0];
        break;
      case 'month':
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        startDate = monthStart.toISOString().split('T')[0];
        endDate = today.toISOString().split('T')[0];
        break;
      case 'year':
        const yearStart = new Date(today.getFullYear(), 0, 1);
        startDate = yearStart.toISOString().split('T')[0];
        endDate = today.toISOString().split('T')[0];
        break;
      case 'custom':
        startDate = customStartDate.value || today.toISOString().split('T')[0];
        endDate = customEndDate.value || today.toISOString().split('T')[0];
        break;
    }
  } catch (error) {
    console.error('Error calculating date range:', error);
    startDate = today.toISOString().split('T')[0];
    endDate = startDate;
  }
  
  return { startDate, endDate };
};

// Date range functions
const setDateRange = (range: string) => {
  dateRangeFilter.value = range;
  showCustomDateFields.value = false;
  
  if (range === 'custom') {
    showCustomDateFields.value = true;
    // Set default values for custom date picker (last 30 days)
    const today = new Date();
    const lastMonth = new Date(today);
    lastMonth.setDate(today.getDate() - 30);
    
    tempCustomStartDate.value = lastMonth.toISOString().split('T')[0];
    tempCustomEndDate.value = today.toISOString().split('T')[0];
    
    // Set initial values but don't apply yet
    customStartDate.value = tempCustomStartDate.value;
    customEndDate.value = tempCustomEndDate.value;
    updateCurrentDateRange();
  } else {
    customStartDate.value = '';
    customEndDate.value = '';
    updateCurrentDateRange();
  }
};

const showCustomDatePicker = () => {
  if (dateRangeFilter.value === 'custom') {
    // Toggle visibility
    showCustomDateFields.value = !showCustomDateFields.value;
  } else {
    setDateRange('custom');
  }
};

const applyCustomDateRange = () => {
  if (!tempCustomStartDate.value || !tempCustomEndDate.value) {
    alert('Please select both start and end dates');
    return;
  }
  
  try {
    const start = new Date(tempCustomStartDate.value);
    const end = new Date(tempCustomEndDate.value);
    
    if (isNaN(start.getTime()) || isNaN(end.getTime())) {
      alert('Invalid date format');
      return;
    }
    
    if (start > end) {
      alert('Start date cannot be after end date');
      return;
    }
    
    // Apply the custom dates
    customStartDate.value = tempCustomStartDate.value;
    customEndDate.value = tempCustomEndDate.value;
    dateRangeFilter.value = 'custom';
    updateCurrentDateRange();
    showCustomDateFields.value = false;
  } catch (error) {
    alert('Error processing dates');
  }
};

const cancelCustomDateRange = () => {
  // Reset to previous values and hide the custom date fields
  showCustomDateFields.value = false;
  
  // If we had a custom range applied before, keep it
  // Otherwise, reset to the default "This Month"
  if (dateRangeFilter.value !== 'custom') {
    tempCustomStartDate.value = '';
    tempCustomEndDate.value = '';
  } else {
    // Reset temp values to current custom values
    tempCustomStartDate.value = customStartDate.value;
    tempCustomEndDate.value = customEndDate.value;
  }
};

// Export functions (CSV)
const exportToCSV = (data: any[], headers: string[], filename: string) => {
  try {
    // Convert data to CSV format
    const csvContent = [
      headers.join(','),
      ...data.map(row => Object.values(row).map(value => 
        `"${String(value).replace(/"/g, '""')}"`
      ).join(','))
    ].join('\n');
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error('Error exporting CSV:', error);
    alert('Error exporting CSV file');
  }
};

const exportResourcesCSV = () => {
  const data = filteredResources.value.map(resource => ({
    ID: resource.id,
    Name: resource.name,
    Description: resource.description || '',
    Category: getCategoryName(resource.category_id),
    Location: resource.location_name || 'N/A',
    Price: `Rs. ${formatPrice(resource.base_price)}`,
    Status: resource.status,
    Bookings: getResourceBookingCount(resource.id),
    'Created Date': formatDate(resource.created_at)
  }));
  
  exportToCSV(data, 
    ['ID', 'Name', 'Description', 'Category', 'Location', 'Price', 'Status', 'Bookings', 'Created Date'], 
    `resources-report-${new Date().toISOString().split('T')[0]}.csv`
  );
};

const exportUsersCSV = () => {
  const data = filteredUsers.value.map(user => ({
    ID: user.id,
    Name: user.name,
    Email: user.email,
    Role: user.primaryRole,
    Status: user.status,
    'Joined Date': formatDate(user.created_at),
    Bookings: getUserBookingCount(user.id)
  }));
  
  exportToCSV(data, 
    ['ID', 'Name', 'Email', 'Role', 'Status', 'Joined Date', 'Bookings'], 
    `users-report-${new Date().toISOString().split('T')[0]}.csv`
  );
};

const exportBookingsCSV = () => {
  const data = filteredBookings.value.map(booking => ({
    ID: booking.id,
    User: booking.userName || `User ${booking.userId}`,
    Resource: getResourceName(booking.resourceId),
    Date: formatDate(booking.date),
    'Start Time': formatTime(booking.startTime),
    'End Time': formatTime(booking.endTime),
    Amount: `Rs. ${formatPrice(booking.amount)}`,
    Status: booking.status,
    'Created At': formatDateTime(booking.created_at)
  }));
  
  exportToCSV(data, 
    ['ID', 'User', 'Resource', 'Date', 'Start Time', 'End Time', 'Amount', 'Status', 'Created At'], 
    `bookings-report-${new Date().toISOString().split('T')[0]}.csv`
  );
};

const exportAllToCSV = () => {
  // Export each report separately with delay
  exportResourcesCSV();
  setTimeout(() => exportUsersCSV(), 500);
  setTimeout(() => exportBookingsCSV(), 1000);
};

const printReports = () => {
  try {
    const printWindow = window.open('', '_blank');
    
    if (!printWindow) {
      alert('Please allow popups to print the report');
      return;
    }
    
    // Get report content
    const resourcesContent = document.getElementById('resources-report')?.innerHTML || '';
    const usersContent = document.getElementById('users-report')?.innerHTML || '';
    const bookingsContent = document.getElementById('bookings-report')?.innerHTML || '';
    
    const printContent = `
      <!DOCTYPE html>
      <html>
      <head>
        <title>University Resource Booking System - Reports</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          @media print {
            body { padding: 20px; font-size: 12px; }
            h1 { color: #1e4449; margin-bottom: 20px; }
            h3 { color: #1e4449; margin-top: 30px; margin-bottom: 15px; }
            .table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            .table th, .table td { border: 1px solid #dee2e6; padding: 6px; }
            .badge { border: 1px solid #000; font-size: 10px; padding: 3px 6px; }
            .btn, .form-control, .form-select, .filter-section { display: none !important; }
            .report-section { margin-bottom: 30px; page-break-inside: avoid; }
          }
          .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1e4449; padding-bottom: 15px; }
          .date-range { text-align: center; color: #666; margin-bottom: 20px; }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>University Resource Booking System</h1>
          <h2>Comprehensive Reports</h2>
          <div class="date-range">
            <p>Generated on: ${new Date().toLocaleString()}</p>
            <p>Date Range: ${getDateRangeText()}</p>
          </div>
        </div>
        
        <div class="report-section">
          <h3>Resources Report (${filteredResources.value.length} resources)</h3>
          ${resourcesContent.replace(/<div class="d-flex justify-content-between align-items-center mb-3">.*?<\/div>/gs, '')}
        </div>
        
        <div class="report-section">
          <h3>Users Report (${filteredUsers.value.length} users)</h3>
          ${usersContent.replace(/<div class="d-flex justify-content-between align-items-center mb-3">.*?<\/div>/gs, '')}
        </div>
        
        <div class="report-section">
          <h3>Bookings Report (${filteredBookings.value.length} bookings)</h3>
          ${bookingsContent.replace(/<div class="d-flex justify-content-between align-items-center mb-3">.*?<\/div>/gs, '')}
        </div>
      </body>
      </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
    // Wait for content to load then print
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 500);
  } catch (error) {
    console.error('Error printing reports:', error);
    alert('Error printing reports');
  }
};

// API calls with error handling
const fetchResources = async () => {
  isLoadingResources.value = true;
  resourceError.value = '';
  
  try {
    const token = getAuthToken();
    if (!token) {
      throw new Error('No authentication token found');
    }
    
    const response = await axios.get(`${API_BASE_URL}/resources`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Handle different API response structures
    if (response.data && Array.isArray(response.data)) {
      resources.value = response.data;
    } else if (response.data && response.data.resources) {
      resources.value = response.data.resources;
    } else {
      resources.value = [];
    }
    
    console.log('Resources loaded:', resources.value.length);
    // Debug: Log resources with their categories
    resources.value.forEach(resource => {
      console.log(`Resource ID: ${resource.id}, Category ID: ${resource.category_id}, Name: ${resource.name}`);
    });
  } catch (error: any) {
    console.error('Error fetching resources:', error);
    if (error.response?.status === 401) {
      resourceError.value = 'Authentication required. Please login again.';
    } else if (error.message?.includes('token')) {
      resourceError.value = 'Authentication token missing. Please login.';
    } else {
      resourceError.value = 'Failed to load resources. Please try again.';
    }
    resources.value = [];
  } finally {
    isLoadingResources.value = false;
  }
};

const fetchUsers = async () => {
  isLoadingUsers.value = true;
  
  try {
    const token = getAuthToken();
    if (!token) {
      throw new Error('No authentication token found');
    }
    
    const response = await axios.get(`${API_BASE_URL}/users`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    let usersData = [];
    if (response.data && Array.isArray(response.data)) {
      usersData = response.data;
    } else {
      usersData = [];
    }
    
    users.value = usersData.map((user: any) => ({
      ...user,
      primaryRole: user.roles?.[0]?.name || 'User',
      id: user.id || user._id || 'N/A'
    }));
    
    console.log('Users loaded:', users.value.length);
  } catch (error: any) {
    console.error('Error fetching users:', error);
    users.value = [];
  } finally {
    isLoadingUsers.value = false;
  }
};

const fetchBookings = async () => {
  isLoadingBookings.value = true;
  
  try {
    const token = getAuthToken();
    if (!token) {
      throw new Error('No authentication token found');
    }
    
    const response = await axios.get(`${API_BASE_URL}/bookings`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    let bookingsData = [];
    if (response.data && Array.isArray(response.data)) {
      bookingsData = response.data;
    } else if (response.data && response.data.bookings) {
      bookingsData = response.data.bookings;
    } else {
      bookingsData = [];
    }
    
    bookings.value = bookingsData.map((booking: any) => ({
      ...booking,
      id: booking.id || booking._id || Math.random(),
      userId: booking.userId || booking.user_id || 'N/A',
      resourceId: booking.resourceId || booking.resource_id || 0,
      date: booking.date || booking.booking_date || '',
      startTime: booking.startTime || booking.start_time || '',
      endTime: booking.endTime || booking.end_time || '',
      status: booking.status || 'pending',
      amount: booking.amount || booking.total_amount || 0,
      userName: booking.userName || booking.user_name || '',
      userEmail: booking.userEmail || booking.user_email || '',
      created_at: booking.created_at || booking.createdAt || new Date().toISOString()
    }));
    
    console.log('Bookings loaded:', bookings.value.length);
  } catch (error: any) {
    console.error('Error fetching bookings:', error);
    bookings.value = [];
  } finally {
    isLoadingBookings.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const token = getAuthToken();
    if (!token) {
      return;
    }
    
    const response = await axios.get(`${API_BASE_URL}/categories`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    if (response.data && Array.isArray(response.data)) {
      categories.value = response.data;
    } else if (response.data && response.data.categories) {
      categories.value = response.data.categories;
    } else {
      categories.value = [];
    }
    
    console.log('Categories loaded:', categories.value.length);
    // Debug: Log categories
    categories.value.forEach(category => {
      console.log(`Category ID: ${category.id}, Name: ${category.name}`);
    });
  } catch (error: any) {
    console.error('Error fetching categories:', error);
    categories.value = [];
  }
};

const fetchAllData = async () => {
  isInitialLoading.value = true;
  
  try {
    // Fetch all data in parallel
    await Promise.all([
      fetchResources(),
      fetchUsers(),
      fetchBookings(),
      fetchCategories()
    ]);
  } catch (error) {
    console.error('Error fetching all data:', error);
  } finally {
    isInitialLoading.value = false;
  }
};

// Initialize
onMounted(() => {
  setDateRange('month');
  fetchAllData();
});
</script>

<style scoped>
.section {
  animation: fadeIn 0.3s ease;
  margin-left: 260px;
  padding: 20px;
  min-height: 100vh;
  background-color: #f8f9fa;
}

@media (max-width: 768px) {
  .section {
    margin-left: 0;
    padding: 15px;
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
  font-size: 1.8rem;
}

/* Statistics Cards */
.stat-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 15px;
  transition: transform 0.3s ease;
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-content h3 {
  font-size: 28px;
  font-weight: 700;
  color: #1e4449;
  margin: 0;
  line-height: 1;
}

.stat-content p {
  margin: 5px 0 0 0;
  color: #6c757d;
  font-size: 14px;
}

/* Filter Section */
.filter-section {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
  margin-bottom: 20px;
}

.text-dark-teal {
  color: #1e4449;
  font-weight: 600;
}

.btn-dark-teal {
  background-color: #1e4449;
  border-color: #1e4449;
  color: white;
}

.btn-dark-teal:hover {
  background-color: #163136;
  border-color: #163136;
  color: white;
}

.btn-outline-dark-teal {
  --bs-btn-color: #1e4449;
  --bs-btn-border-color: #1e4449;
  --bs-btn-hover-bg: #1e4449;
  --bs-btn-hover-color: white;
}

/* Table Cards */
.table-card {
  background: white;
  border-radius: 10px;
  padding: 25px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  margin-bottom: 20px;
}

.table-card h5 {
  color: #1e4449;
  font-weight: 600;
}

/* Table Styles */
.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table {
  margin-bottom: 0;
  font-size: 14px;
}

.table thead {
  background-color: #1e4449;
  color: white;
  border-bottom: 2px solid #163136;
}

.table th {
  font-weight: 600;
  padding: 12px 8px;
  white-space: nowrap;
  border: none;
}

.table td {
  padding: 10px 8px;
  vertical-align: middle;
  border-top: 1px solid #dee2e6;
}

.table tbody tr {
  transition: background-color 0.2s;
}

.table tbody tr:hover {
  background-color: rgba(30, 68, 73, 0.05);
}

/* Badge Styles */
.badge {
  font-size: 12px;
  font-weight: 500;
  padding: 5px 10px;
  border-radius: 20px;
}

.bg-dark-teal {
  background-color: #1e4449 !important;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.95);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

/* Form Controls */
.form-control,
.form-select {
  border: 1px solid #dee2e6;
  border-radius: 6px;
  font-size: 14px;
}

.form-control:focus,
.form-select:focus {
  border-color: #1e4449;
  box-shadow: 0 0 0 0.25rem rgba(30, 68, 73, 0.25);
}

/* Success Button */
.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* Alert Styles */
.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .section-title {
    font-size: 1.5rem;
  }
  
  .stat-card {
    padding: 15px;
  }
  
  .stat-icon {
    width: 50px;
    height: 50px;
  }
  
  .stat-content h3 {
    font-size: 24px;
  }
  
  .table-card {
    padding: 15px;
  }
  
  .table th,
  .table td {
    font-size: 13px;
    padding: 8px 6px;
  }
  
  .badge {
    font-size: 11px;
    padding: 4px 8px;
  }
  
  .d-flex.gap-2 {
    gap: 8px !important;
  }
  
  .form-control,
  .form-select {
    font-size: 13px;
  }
}

@media (max-width: 576px) {
  .section {
    padding: 10px;
  }
  
  .stat-card {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
  
  .filter-section .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 10px !important;
  }
  
  .filter-section .d-flex.gap-2 {
    width: 100%;
  }
  
  .table-responsive {
    font-size: 12px;
  }
  
  .table th,
  .table td {
    padding: 6px 4px;
  }
}

/* Print Styles */
@media print {
  .section {
    margin-left: 0 !important;
    background: white !important;
    padding: 0 !important;
  }
  
  .stat-card,
  .filter-section,
  .table-card {
    box-shadow: none !important;
    border: none !important;
    padding: 0 !important;
    margin-bottom: 20px !important;
  }
  
  .btn,
  .form-control,
  .form-select {
    display: none !important;
  }
  
  .table {
    font-size: 10px !important;
  }
}
</style>