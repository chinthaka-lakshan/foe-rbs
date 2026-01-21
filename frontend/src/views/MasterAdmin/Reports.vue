<template>
  <navbar />
  <master-admin-sidebar />
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Reports Dashboard</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-dark-teal btn-sm" @click="printAllReports">
          <i class="bi bi-printer me-1"></i>Print All
        </button>
      </div>
    </div>

    <!-- Global Date Range Filter - REDESIGNED -->
    <div class="global-filter-card mb-4">
      <div class="filter-header">
        <i class="bi bi-funnel me-2"></i>
        <h6 class="mb-0">Filter Reports by Date</h6>
      </div>
      
      <div class="filter-body">
        <div class="row g-3 align-items-end">
          <!-- Quick Date Buttons -->
          <div class="col-md-8">
            <div class="quick-date-buttons">
              <div class="d-flex flex-wrap gap-2">
                <button 
                  class="btn btn-sm" 
                  :class="dateRangeType === 'today' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
                  @click="setDateRange('today')"
                >
                  <i class="bi bi-calendar-day me-1"></i>Today
                </button>
                <button 
                  class="btn btn-sm" 
                  :class="dateRangeType === 'week' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
                  @click="setDateRange('week')"
                >
                  <i class="bi bi-calendar-week me-1"></i>This Week
                </button>
                <button 
                  class="btn btn-sm" 
                  :class="dateRangeType === 'month' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
                  @click="setDateRange('month')"
                >
                  <i class="bi bi-calendar-month me-1"></i>This Month
                </button>
                <button 
                  class="btn btn-sm" 
                  :class="dateRangeType === 'year' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
                  @click="setDateRange('year')"
                >
                  <i class="bi bi-calendar-year me-1"></i>This Year
                </button>
                <button 
                  class="btn btn-sm" 
                  :class="dateRangeType === 'all' ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
                  @click="setDateRange('all')"
                >
                  <i class="bi bi-calendar-check me-1"></i>All Time
                </button>
              </div>
            </div>
          </div>
          
          <!-- Custom Date Range -->
          <div class="col-md-4">
            <div class="custom-date-range">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="bi bi-calendar-range"></i>
                </span>
                <input 
                  type="date" 
                  class="form-control form-control-sm" 
                  v-model="startDate"
                  :max="endDate"
                >
                <span class="input-group-text">to</span>
                <input 
                  type="date" 
                  class="form-control form-control-sm" 
                  v-model="endDate"
                  :min="startDate"
                >
              </div>
            </div>
          </div>
          
          <!-- Apply/Reset Buttons -->
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div class="filter-status">
                <span class="badge bg-light text-dark">
                  <i class="bi bi-info-circle me-1"></i>
                  <span v-if="dateRangeType === 'all'">Showing all records</span>
                  <span v-else>
                    Showing data from <strong>{{ formatDate(startDate) }}</strong> 
                    to <strong>{{ formatDate(endDate) }}</strong>
                  </span>
                </span>
              </div>
              <div class="filter-actions">
                <button 
                  class="btn btn-sm btn-success me-2" 
                  @click="applyDateRange"
                  :disabled="!startDate || !endDate"
                >
                  <i class="bi bi-check-circle me-1"></i>Apply Filter
                </button>
                <button 
                  class="btn btn-sm btn-outline-secondary" 
                  @click="resetDateRange"
                >
                  <i class="bi bi-x-circle me-1"></i>Reset
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-3 col-lg-3 col-sm-6 col-12">
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
      <div class="col-md-3 col-lg-3 col-sm-6 col-12">
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
      <div class="col-md-3 col-lg-3 col-sm-6 col-12">
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
      <div class="col-md-3 col-lg-3 col-sm-6 col-12">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(255, 193, 7, 0.1);">
            <i class="bi bi-cash-coin" style="color: #ffc107; font-size: 28px;"></i>
          </div>
          <div class="stat-content">
            <h3>Rs. {{ formatPrice(stats.totalRevenue) }}</h3>
            <p>Total Revenue</p>
          </div>
        </div>
      </div>
    </div>

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
          <button class="btn btn-sm btn-outline-success" @click="printSection('resources')" :disabled="filteredResources.length === 0">
            <i class="bi bi-file-pdf me-1"></i> PDF
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
              <th>Revenue</th>
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
              <td>
                <span class="badge bg-success">
                  Rs. {{ formatPrice(getResourceRevenue(resource.id)) }}
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
          <input 
            type="text" 
            class="form-control form-control-sm" 
            style="min-width: 150px;"
            placeholder="Search users..."
            v-model="userFilter.search"
          >
          <button class="btn btn-sm btn-outline-success" @click="printSection('users')" :disabled="filteredUsers.length === 0">
            <i class="bi bi-file-pdf me-1"></i> PDF
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
              <th>Spent</th>
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
              <td>
                <span class="badge bg-success">
                  Rs. {{ formatPrice(getUserSpending(user.id)) }}
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
            <option v-for="resource in uniqueResources" :key="resource" :value="resource">
              {{ resource }}
            </option>
          </select>
          <select class="form-select form-select-sm w-auto" v-model="bookingFilter.status">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Completed">Completed</option>
          </select>
          <input 
            type="text" 
            class="form-control form-control-sm" 
            style="min-width: 150px;"
            placeholder="Search by user email..."
            v-model="bookingFilter.search"
          >
          <button class="btn btn-sm btn-outline-success" @click="printSection('bookings')" :disabled="filteredBookings.length === 0">
            <i class="bi bi-file-pdf me-1"></i> PDF
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
              <th>Booking Ref</th>
              <th>User Email</th>
              <th>Resource</th>
              <th>Booking Date</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Total Amount</th>
              <th>Status</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in filteredBookings" :key="booking.id">
              <td>
                <span class="badge bg-light text-dark">{{ booking.booking_reference }}</span>
              </td>
              <td>{{ booking.user_email }}</td>
              
              <td>
                <template v-if="booking.details && booking.details.length > 0">
                  {{ booking.details[0].item_name }}
                </template>
                <template v-else-if="booking.resource_details && booking.resource_details.length > 0">
                  {{ booking.resource_details[0].name }}
                </template>
                <template v-else>
                  <span class="text-muted">N/A</span>
                </template>
              </td>

              <td>{{ formatDate(booking.booking_date) }}</td>
              <td>{{ booking.start_time }}</td>
              <td>{{ booking.end_time }}</td>
              <td>Rs. {{ formatPrice(booking.total_amount) }}</td>
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
          <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
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

// API Configuration
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

interface BookingDetail {
  item_name: string;
  [key: string]: any;
}

interface ResourceDetail {
  name: string;
  [key: string]: any;
}

interface Booking {
  id: number;
  booking_reference: string;
  user_email: string;
  details: BookingDetail[];
  resource_details: ResourceDetail[];
  booking_date: string;
  start_time: string;
  end_time: string;
  total_amount: number | string;
  status: 'Pending' | 'Confirmed' | 'Cancelled' | 'Completed';
  created_at: string;
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

// Date Range State
const dateRangeType = ref<'today' | 'week' | 'month' | 'year' | 'all'>('month');
const startDate = ref('');
const endDate = ref('');

// Filter states for each section
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

// Initialize dates on component mount
const initializeDates = () => {
  const today = new Date();
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  
  // Set default dates to current month
  startDate.value = startOfMonth.toISOString().split('T')[0];
  endDate.value = today.toISOString().split('T')[0];
};

// Set date range based on type
const setDateRange = (type: 'today' | 'week' | 'month' | 'year' | 'all') => {
  dateRangeType.value = type;
  
  const today = new Date();
  let start = new Date();
  let end = new Date();
  
  switch (type) {
    case 'today':
      startDate.value = today.toISOString().split('T')[0];
      endDate.value = today.toISOString().split('T')[0];
      break;
    case 'week':
      start.setDate(today.getDate() - today.getDay());
      startDate.value = start.toISOString().split('T')[0];
      endDate.value = today.toISOString().split('T')[0];
      break;
    case 'month':
      start = new Date(today.getFullYear(), today.getMonth(), 1);
      startDate.value = start.toISOString().split('T')[0];
      endDate.value = today.toISOString().split('T')[0];
      break;
    case 'year':
      start = new Date(today.getFullYear(), 0, 1);
      startDate.value = start.toISOString().split('T')[0];
      endDate.value = today.toISOString().split('T')[0];
      break;
    case 'all':
      // Clear dates for "All Time"
      startDate.value = '';
      endDate.value = '';
      break;
  }
};

// Apply date range manually
const applyDateRange = () => {
  // If custom dates are selected, switch to manual mode
  if (startDate.value && endDate.value) {
    dateRangeType.value = 'month'; // Reset to month type but use custom dates
  }
};

// Reset date range to default
const resetDateRange = () => {
  dateRangeType.value = 'month';
  const today = new Date();
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  
  startDate.value = startOfMonth.toISOString().split('T')[0];
  endDate.value = today.toISOString().split('T')[0];
};

// Computed properties
const stats = computed(() => ({
  totalResources: filteredResources.value.length,
  totalUsers: filteredUsers.value.length,
  totalBookings: filteredBookings.value.length,
  totalRevenue: filteredBookings.value.reduce((total, booking) => {
    const amount = parseFloat(booking.total_amount.toString()) || 0;
    return total + amount;
  }, 0)
}));

// Get unique resources for filter dropdown
const uniqueResources = computed(() => {
  const resources = new Set<string>();
  
  bookings.value.forEach(booking => {
    if (booking.details && booking.details.length > 0) {
      booking.details.forEach((detail: BookingDetail) => {
        if (detail.item_name) {
          resources.add(detail.item_name);
        }
      });
    }
    if (booking.resource_details && booking.resource_details.length > 0) {
      booking.resource_details.forEach((resource: ResourceDetail) => {
        if (resource.name) {
          resources.add(resource.name);
        }
      });
    }
  });
  
  return Array.from(resources).sort();
});

// Filter resources by date range and other filters
const filteredResources = computed(() => {
  let filtered = resources.value;
  
  // Apply status filter
  if (resourceFilter.value.status) {
    filtered = filtered.filter(r => r.status === resourceFilter.value.status);
  }
  
  // Apply category filter
  if (resourceFilter.value.category) {
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
  if (startDate.value && endDate.value && dateRangeType.value !== 'all') {
    filtered = filtered.filter(r => {
      const createdDate = new Date(r.created_at).toISOString().split('T')[0];
      return createdDate >= startDate.value && createdDate <= endDate.value;
    });
  }
  
  return filtered;
});

// Filter users by date range and other filters
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
  if (startDate.value && endDate.value && dateRangeType.value !== 'all') {
    filtered = filtered.filter(u => {
      const createdDate = new Date(u.created_at).toISOString().split('T')[0];
      return createdDate >= startDate.value && createdDate <= endDate.value;
    });
  }
  
  return filtered;
});

// Filter bookings by date range and other filters
const filteredBookings = computed(() => {
  let filtered = bookings.value;
  
  // Apply resource filter
  if (bookingFilter.value.resource) {
    filtered = filtered.filter(b => {
      // Check in details array
      const hasDetail = b.details?.some((detail: BookingDetail) => 
        detail.item_name === bookingFilter.value.resource
      );
      
      // Check in resource_details array
      const hasResourceDetail = b.resource_details?.some((resource: ResourceDetail) => 
        resource.name === bookingFilter.value.resource
      );
      
      return hasDetail || hasResourceDetail;
    });
  }
  
  // Apply status filter
  if (bookingFilter.value.status) {
    filtered = filtered.filter(b => b.status === bookingFilter.value.status);
  }
  
  // Apply search filter (by user email)
  if (bookingFilter.value.search) {
    const search = bookingFilter.value.search.toLowerCase();
    filtered = filtered.filter(b => 
      b.user_email && b.user_email.toLowerCase().includes(search)
    );
  }
  
  // Apply date range filter to booking date
  if (startDate.value && endDate.value && dateRangeType.value !== 'all') {
    filtered = filtered.filter(b => {
      const bookingDate = new Date(b.booking_date).toISOString().split('T')[0];
      return bookingDate >= startDate.value && bookingDate <= endDate.value;
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
    case 'Confirmed': return 'bg-success';
    case 'Pending': return 'bg-warning text-dark';
    case 'Cancelled': return 'bg-danger';
    case 'Completed': return 'bg-info';
    default: return 'bg-secondary';
  }
};

const getCategoryName = (categoryId: number) => {
  const category = categories.value.find(c => c.id === categoryId);
  return category ? category.name : 'Unknown Category';
};

const getResourceBookingCount = (resourceId: number) => {
  // Get all bookings for this resource
  let bookingsForResource = bookings.value.filter(b => {
    // Check if booking has this resource
    const hasResourceInDetails = b.details?.some((detail: BookingDetail) => {
      return detail.item_name && detail.item_name.includes(resourceId.toString());
    });
    
    const hasResourceInResourceDetails = b.resource_details?.some((resource: ResourceDetail) => {
      return resource.name && resource.name.includes(resourceId.toString());
    });
    
    return hasResourceInDetails || hasResourceInResourceDetails;
  });
  
  // Filter by date range if applicable
  if (startDate.value && endDate.value && dateRangeType.value !== 'all') {
    bookingsForResource = bookingsForResource.filter(b => {
      const bookingDate = new Date(b.booking_date).toISOString().split('T')[0];
      return bookingDate >= startDate.value && bookingDate <= endDate.value;
    });
  }
  
  return bookingsForResource.length;
};

const getResourceRevenue = (resourceId: number) => {
  // Get all bookings for this resource
  let bookingsForResource = bookings.value.filter(b => {
    // Check if booking has this resource
    const hasResourceInDetails = b.details?.some((detail: BookingDetail) => {
      return detail.item_name && detail.item_name.includes(resourceId.toString());
    });
    
    const hasResourceInResourceDetails = b.resource_details?.some((resource: ResourceDetail) => {
      return resource.name && resource.name.includes(resourceId.toString());
    });
    
    return hasResourceInDetails || hasResourceInResourceDetails;
  });
  
  // Filter by date range if applicable
  if (startDate.value && endDate.value && dateRangeType.value !== 'all') {
    bookingsForResource = bookingsForResource.filter(b => {
      const bookingDate = new Date(b.booking_date).toISOString().split('T')[0];
      return bookingDate >= startDate.value && bookingDate <= endDate.value;
    });
  }
  
  // Calculate total revenue
  const total = bookingsForResource.reduce((sum, booking) => {
    const amount = parseFloat(booking.total_amount.toString()) || 0;
    return sum + amount;
  }, 0);
  
  return total;
};

const getUserBookingCount = (userId: number | string) => {
  const user = users.value.find(u => u.id === userId);
  if (!user) return 0;
  
  let userBookings = bookings.value.filter(b => b.user_email === user.email);
  
  // Filter by date range if applicable
  if (startDate.value && endDate.value && dateRangeType.value !== 'all') {
    userBookings = userBookings.filter(b => {
      const bookingDate = new Date(b.booking_date).toISOString().split('T')[0];
      return bookingDate >= startDate.value && bookingDate <= endDate.value;
    });
  }
  
  return userBookings.length;
};

const getUserSpending = (userId: number | string) => {
  const user = users.value.find(u => u.id === userId);
  if (!user) return 0;
  
  let userBookings = bookings.value.filter(b => b.user_email === user.email);
  
  // Filter by date range if applicable
  if (startDate.value && endDate.value && dateRangeType.value !== 'all') {
    userBookings = userBookings.filter(b => {
      const bookingDate = new Date(b.booking_date).toISOString().split('T')[0];
      return bookingDate >= startDate.value && bookingDate <= endDate.value;
    });
  }
  
  // Calculate total spending
  const total = userBookings.reduce((sum, booking) => {
    const amount = parseFloat(booking.total_amount.toString()) || 0;
    return sum + amount;
  }, 0);
  
  return total;
};

// Print section separately
const printSection = (section: string) => {
  try {
    let content = '';
    let title = '';
    let count = 0;
    
    switch (section) {
      case 'resources':
        content = document.getElementById('resources-report')?.innerHTML || '';
        title = 'Resources Report';
        count = filteredResources.value.length;
        break;
      case 'users':
        content = document.getElementById('users-report')?.innerHTML || '';
        title = 'Users Report';
        count = filteredUsers.value.length;
        break;
      case 'bookings':
        content = document.getElementById('bookings-report')?.innerHTML || '';
        title = 'Bookings Report';
        count = filteredBookings.value.length;
        break;
    }
    
    if (!content) {
      alert('No content available to print');
      return;
    }
    
    const printWindow = window.open('', '_blank');
    
    if (!printWindow) {
      alert('Please allow popups to print the report');
      return;
    }
    
    const dateRangeText = dateRangeType.value === 'all' 
      ? 'All Time' 
      : `${formatDate(startDate.value)} to ${formatDate(endDate.value)}`;
    
    const printContent = `
      <!DOCTYPE html>
      <html>
      <head>
        <title>University Resource Booking System - ${title}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <style>
          @media print {
            body { padding: 15px; font-size: 11px; }
            h1 { color: #1e4449; margin-bottom: 15px; font-size: 20px; }
            h3 { color: #1e4449; margin-top: 20px; margin-bottom: 10px; font-size: 16px; }
            .table { border-collapse: collapse; width: 100%; margin-bottom: 15px; font-size: 9px; }
            .table th, .table td { border: 1px solid #dee2e6; padding: 4px; }
            .badge { border: 1px solid #000; font-size: 8px; padding: 2px 4px; }
            .btn, .form-control, .form-select, .filter-section { display: none !important; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e4449; padding-bottom: 10px; }
            .date-range { text-align: center; color: #666; margin-bottom: 15px; font-size: 10px; }
            .page-break { page-break-before: always; }
          }
          @media screen {
            body { padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1e4449; padding-bottom: 15px; }
            .date-range { text-align: center; color: #666; margin-bottom: 20px; }
          }
          body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>University Resource Booking System</h1>
          <h2>${title}</h2>
          <div class="date-range">
            <p>Generated on: ${new Date().toLocaleString()}</p>
            <p>Date Range: ${dateRangeText}</p>
            <p>Total Records: ${count}</p>
          </div>
        </div>
        
        ${content.replace(/<div class="d-flex justify-content-between align-items-center mb-3">.*?<\/div>/gs, '')}
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 10px;">
          <p>Report generated by University Resource Booking System</p>
          <p>© ${new Date().getFullYear()} - All rights reserved</p>
        </div>
        
        <script>
          // Auto-print when page loads
          window.onload = function() {
            setTimeout(function() {
              window.print();
            }, 500);
          }
          
          // Close window after print
          window.onafterprint = function() {
            setTimeout(function() {
              window.close();
            }, 1000);
          }
        <\/script>
      </body>
      </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
  } catch (error) {
    console.error('Error printing section:', error);
    alert('Error printing report');
  }
};

const printAllReports = () => {
  try {
    const printWindow = window.open('', '_blank');
    
    if (!printWindow) {
      alert('Please allow popups to print the report');
      return;
    }
    
    const resourcesContent = document.getElementById('resources-report')?.innerHTML || '';
    const usersContent = document.getElementById('users-report')?.innerHTML || '';
    const bookingsContent = document.getElementById('bookings-report')?.innerHTML || '';
    
    const dateRangeText = dateRangeType.value === 'all' 
      ? 'All Time' 
      : `${formatDate(startDate.value)} to ${formatDate(endDate.value)}`;
    
    const printContent = `
      <!DOCTYPE html>
      <html>
      <head>
        <title>University Resource Booking System - Comprehensive Reports</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          @media print {
            body { padding: 15px; font-size: 11px; }
            h1 { color: #1e4449; margin-bottom: 15px; font-size: 20px; }
            h3 { color: #1e4449; margin-top: 20px; margin-bottom: 10px; font-size: 16px; }
            .table { border-collapse: collapse; width: 100%; margin-bottom: 15px; font-size: 9px; }
            .table th, .table td { border: 1px solid #dee2e6; padding: 4px; }
            .badge { border: 1px solid #000; font-size: 8px; padding: 2px 4px; }
            .btn, .form-control, .form-select, .filter-section { display: none !important; }
            .report-section { margin-bottom: 30px; page-break-inside: avoid; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e4449; padding-bottom: 10px; }
            .date-range { text-align: center; color: #666; margin-bottom: 15px; font-size: 10px; }
            .page-break { page-break-before: always; }
          }
          @media screen {
            body { padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1e4449; padding-bottom: 15px; }
            .date-range { text-align: center; color: #666; margin-bottom: 20px; }
          }
          body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>University Resource Booking System</h1>
          <h2>Comprehensive Reports</h2>
          <div class="date-range">
            <p>Generated on: ${new Date().toLocaleString()}</p>
            <p>Date Range: ${dateRangeText}</p>
          </div>
        </div>
        
        <div class="report-section">
          <h3>Resources Report (${filteredResources.value.length} resources)</h3>
          ${resourcesContent.replace(/<div class="d-flex justify-content-between align-items-center mb-3">.*?<\/div>/gs, '')}
        </div>
        
        <div class="page-break"></div>
        
        <div class="report-section">
          <h3>Users Report (${filteredUsers.value.length} users)</h3>
          ${usersContent.replace(/<div class="d-flex justify-content-between align-items-center mb-3">.*?<\/div>/gs, '')}
        </div>
        
        <div class="page-break"></div>
        
        <div class="report-section">
          <h3>Bookings Report (${filteredBookings.value.length} bookings)</h3>
          ${bookingsContent.replace(/<div class="d-flex justify-content-between align-items-center mb-3">.*?<\/div>/gs, '')}
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 10px;">
          <p>Report generated by University Resource Booking System</p>
          <p>© ${new Date().getFullYear()} - All rights reserved</p>
        </div>
        
        <script>
          // Auto-print when page loads
          window.onload = function() {
            setTimeout(function() {
              window.print();
            }, 500);
          }
          
          // Close window after print
          window.onafterprint = function() {
            setTimeout(function() {
              window.close();
            }, 1000);
          }
        <\/script>
      </body>
      </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
  } catch (error) {
    console.error('Error printing reports:', error);
    alert('Error printing reports');
  }
};

// API calls
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
    
    if (response.data && Array.isArray(response.data)) {
      resources.value = response.data;
    } else if (response.data && response.data.resources) {
      resources.value = response.data.resources;
    } else {
      resources.value = [];
    }
    
    console.log('Resources loaded:', resources.value.length);
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
    } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
      bookingsData = response.data.data;
    } else if (response.data && response.data.bookings && Array.isArray(response.data.bookings)) {
      bookingsData = response.data.bookings;
    } else {
      bookingsData = [];
    }
    
    bookings.value = bookingsData.map((booking: any) => ({
      id: booking.id || booking._id || Math.random(),
      booking_reference: booking.booking_reference || `REF-${booking.id || 'N/A'}`,
      user_email: booking.user_email || booking.userEmail || 'N/A',
      details: booking.details || [],
      resource_details: booking.resource_details || [],
      booking_date: booking.booking_date || booking.date || '',
      start_time: booking.start_time || booking.startTime || '',
      end_time: booking.end_time || booking.endTime || '',
      total_amount: booking.total_amount || booking.amount || 0,
      status: booking.status || 'Pending',
      created_at: booking.created_at || booking.createdAt || new Date().toISOString()
    }));
    
    console.log('Bookings loaded:', bookings.value.length);
  } catch (error: any) {
    console.error('Error fetching bookings:', error);
    
    if (error.response) {
      if (error.response.status === 401) {
        alert('Authentication required. Please login again.');
      } else if (error.response.status === 404) {
        alert('Bookings endpoint not found.');
      } else if (error.response.status === 500) {
        alert('Server error. Please try again later.');
      } else {
        alert(`Failed to load bookings: ${error.response.data?.message || 'Unknown error'}`);
      }
    } else if (error.request) {
      alert('No response from server. Please check your connection.');
    } else {
      alert(`Request error: ${error.message}`);
    }
    
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
  initializeDates();
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

/* Global Filter Card - REDESIGNED */
.global-filter-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  border: 1px solid #e9ecef;
  overflow: hidden;
  margin-bottom: 24px;
}

.filter-header {
  background: linear-gradient(135deg, #1e4449 0%, #2c5f66 100%);
  color: white;
  padding: 16px 24px;
  display: flex;
  align-items: center;
}

.filter-header h6 {
  font-weight: 600;
  margin: 0;
}

.filter-body {
  padding: 24px;
}

.quick-date-buttons .btn {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
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

.btn-outline-dark-teal:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(30, 68, 73, 0.2);
}

.custom-date-range .input-group {
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #dee2e6;
}

.custom-date-range .input-group-text {
  background-color: #f8f9fa;
  border: none;
  color: #1e4449;
  font-weight: 500;
}

.custom-date-range .form-control {
  border: none;
  padding: 8px 12px;
  font-size: 0.9rem;
}

.custom-date-range .form-control:focus {
  box-shadow: none;
  background-color: #fff;
}

.filter-actions .btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.filter-actions .btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.filter-actions .btn-success:disabled {
  background-color: #6c757d;
  border-color: #6c757d;
}

.filter-status .badge {
  font-size: 0.85rem;
  padding: 8px 12px;
  background-color: #f8f9fa !important;
  border: 1px solid #dee2e6;
}

/* Statistics Cards */
.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: all 0.3s ease;
  height: 100%;
  border: 1px solid #e9ecef;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
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
  font-weight: 500;
}

/* Table Cards */
.table-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  margin-bottom: 24px;
  border: 1px solid #e9ecef;
}

.table-card h5 {
  color: #1e4449;
  font-weight: 600;
  font-size: 1.2rem;
}

/* Table Styles */
.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table {
  margin-bottom: 0;
  font-size: 14px;
  border-radius: 8px;
  overflow: hidden;
}

.table thead {
  background: linear-gradient(135deg, #1e4449 0%, #2c5f66 100%);
  color: white;
  border-bottom: none;
}

.table th {
  font-weight: 600;
  padding: 14px 12px;
  white-space: nowrap;
  border: none;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

.table td {
  padding: 12px;
  vertical-align: middle;
  border-top: 1px solid #e9ecef;
}

.table tbody tr {
  transition: all 0.2s ease;
}

.table tbody tr:hover {
  background-color: rgba(30, 68, 73, 0.05);
  transform: scale(1.002);
}

/* Badge Styles */
.badge {
  font-size: 12px;
  font-weight: 500;
  padding: 6px 12px;
  border-radius: 20px;
  letter-spacing: 0.3px;
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
  border-radius: 8px;
  font-size: 14px;
  padding: 8px 12px;
  transition: all 0.2s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #1e4449;
  box-shadow: 0 0 0 3px rgba(30, 68, 73, 0.15);
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

.btn-outline-success {
  --bs-btn-color: #198754;
  --bs-btn-border-color: #198754;
  --bs-btn-hover-bg: #198754;
  --bs-btn-hover-color: white;
}

/* Alert Styles */
.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
  border-radius: 8px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .section-title {
    font-size: 1.5rem;
  }
  
  .filter-body {
    padding: 16px;
  }
  
  .stat-card {
    padding: 16px;
  }
  
  .stat-icon {
    width: 50px;
    height: 50px;
  }
  
  .stat-content h3 {
    font-size: 24px;
  }
  
  .table-card {
    padding: 16px;
  }
  
  .table th,
  .table td {
    font-size: 13px;
    padding: 10px 8px;
  }
  
  .badge {
    font-size: 11px;
    padding: 4px 10px;
  }
  
  .d-flex.gap-2 {
    gap: 8px !important;
  }
  
  .form-control,
  .form-select {
    font-size: 13px;
  }
  
  .filter-actions {
    flex-direction: column;
    gap: 8px;
  }
  
  .filter-actions .btn {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .section {
    padding: 12px;
  }
  
  .stat-card {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .table-responsive {
    font-size: 12px;
  }
  
  .table th,
  .table td {
    padding: 8px 6px;
  }
  
  .quick-date-buttons .d-flex {
    flex-direction: column;
  }
  
  .quick-date-buttons .btn {
    width: 100%;
    margin-bottom: 8px;
  }
}

/* Print Styles */
@media print {
  .section {
    margin-left: 0 !important;
    background: white !important;
    padding: 0 !important;
  }
  
  .global-filter-card,
  .stat-card,
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

/* Export Button Styles */
.btn-outline-success:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-sm {
  padding: 0.35rem 0.75rem;
  font-size: 0.875rem;
  border-radius: 6px;
}
</style>