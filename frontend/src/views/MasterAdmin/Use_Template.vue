<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Select a Template for <span class="text-dark-teal">{{ categoryTitle }}</span></h2>
      <button class="btn btn-outline-secondary btn-sm" @click="router.back()">
        <i class="bi bi-arrow-left me-1"></i>Back to Categories
      </button>
    </div>

    <div v-if="templates.length === 0" class="alert alert-warning text-center">
        No templates available for **{{ categoryTitle }}** yet.
    </div>

    <div class="row g-4">
      <div v-for="template in templates" :key="template.id" class="col-sm-6 col-md-4 col-lg-3">
        <div class="template-card" @click="selectTemplate(template)">
          <div class="card-body">
            <div class="template-icon mb-3">
              <i :class="template.icon"></i>
            </div>
            <h5 class="card-title">{{ template.name }}</h5>
            <p class="card-text small text-muted">{{ template.description }}</p>
            <span class="badge bg-dark-teal mt-2">Use Template</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// Assuming these paths are correct
import Navbar from '../../components/Navbar.vue'; 
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// Define the structure for a template
interface Template {
    id: number;
    name: string;
    categoryKey: string;
    description: string;
    icon: string;
    fields: Record<string, any>; 
}

const route = useRoute();
const router = useRouter();

const templateCategoryKey = computed<string>(() => route.query.category as string || '');
const categoryTitle = computed(() => {
    switch (templateCategoryKey.value) {
        case 'academic': return 'Academic Space';
        case 'it': return 'IT Space';
        case 'medical': return 'Medical & Health';
        case 'sports': return 'Sports & Recreational';
        case 'cultural': return 'Cultural Space';
        default: return 'Unknown Category';
    }
});

// --- Template Data based on your uploaded UI designs ---
const ALL_TEMPLATES: Template[] = [
    // --- Academic Templates ---
    { 
        id: 101, 
        name: 'Lecture Hall Template', 
        categoryKey: 'academic',
        description: 'Large space for lectures and seminars.', 
        icon: 'bi bi-easel',
        fields: {
            // Fields derived from 'academic.png'
            resourceName: '', locationName: '', capacity: null,
            equipment: ['Smart Board', 'Microphone', 'AC', 'Projector', 'Sound']
        }
    },
    { 
        id: 102, 
        name: 'Study Room Template', 
        categoryKey: 'academic',
        description: 'Small room suitable for group study sessions.', 
        icon: 'bi bi-person-workspace',
        fields: {
            resourceName: '', locationName: '', capacity: null,
            equipment: ['Smart Board', 'AC', 'Projector']
        }
    },
    { 
        id: 103, 
        name: 'Seminar Room Template', 
        categoryKey: 'academic',
        description: 'Mid-sized room for presentations.', 
        icon: 'bi bi-chat-dots',
        fields: {
            resourceName: '', locationName: '', capacity: null,
            equipment: ['Smart Board', 'Microphone', 'AC', 'Projector', 'Sound']
        }
    },
    { 
        id: 104, 
        name: 'Lab Space Template', 
        categoryKey: 'academic',
        description: 'Specialized room for practical work.', 
        icon: 'bi bi-flask',
        fields: {
            resourceName: '', locationName: '', capacity: null,
            equipment: ['AC']
        }
    },

    // --- IT Templates ---
    { 
        id: 201, 
        name: 'Computer Lab Template', 
        categoryKey: 'it',
        description: 'Room equipped with multiple workstations.', 
        icon: 'bi bi-pc-display',
        fields: {
            // Fields derived from 'it.png'
            resourceName: '', locationName: '', seatsCount: null,
            equipment: ['Smart Board', 'AC', 'Microphone', 'Sound', 'Projector']
        }
    },
    { 
        id: 202, 
        name: 'Data Center Template', 
        categoryKey: 'it',
        description: 'Climate-controlled room for hardware.', 
        icon: 'bi bi-server',
        fields: {
            resourceName: '', locationName: '', seatsCount: null,
            equipment: ['AC']
        }
    },
    { 
        id: 203, 
        name: 'IT Workshop Template', 
        categoryKey: 'it',
        description: 'Hands-on hardware and network training space.', 
        icon: 'bi bi-tools',
        fields: {
            resourceName: '', locationName: '', seatsCount: null,
            equipment: ['Smart Board', 'Projector']
        }
    },
    { 
        id: 204, 
        name: 'AV Editing Suite', 
        categoryKey: 'it',
        description: 'Specialized room for media editing.', 
        icon: 'bi bi-film',
        fields: {
            resourceName: '', locationName: '', seatsCount: null,
            equipment: ['Sound', 'AC']
        }
    },

    // --- Medical Templates ---
    { 
        id: 301, 
        name: 'Procedure Room', 
        categoryKey: 'medical',
        description: 'General medical examination and procedure room.', 
        icon: 'bi bi-clipboard-pulse',
        fields: {
            // Fields derived from 'medical.png'
            resourceName: '', locationName: '', capacity: null, medicalType: '',
            equipment: ['X Ray Machine', 'Scan', 'ECG Machine', 'Theater']
        }
    },
    { 
        id: 302, 
        name: 'Radiology Suite', 
        categoryKey: 'medical',
        description: 'Specialized area for imaging.', 
        icon: 'bi bi-x-diamond',
        fields: {
            resourceName: '', locationName: '', capacity: null, medicalType: '',
            equipment: ['X Ray Machine', 'Scan']
        }
    },
    { 
        id: 303, 
        name: 'Operating Theater', 
        categoryKey: 'medical',
        description: 'Room for surgical operations.', 
        icon: 'bi bi-heart-pulse',
        fields: {
            resourceName: '', locationName: '', capacity: null, medicalType: '',
            equipment: ['Theater']
        }
    },
    { 
        id: 304, 
        name: 'Consultation Room', 
        categoryKey: 'medical',
        description: 'Room for patient consultation.', 
        icon: 'bi bi-person-lines-fill',
        fields: {
            resourceName: '', locationName: '', capacity: null, medicalType: 'General',
            equipment: []
        }
    },

    // --- Sports Templates ---
    { 
        id: 401, 
        name: 'Indoor Court', 
        categoryKey: 'sports',
        description: 'Multipurpose sports court for indoor games.', 
        icon: 'bi bi-dribbble',
        fields: {
            // Fields derived from 'sports.png'
            resourceName: '', locationName: '', capacitySize: null, equipment: '',
            accessories: ['Flash Light', 'Microphone', 'Sound', 'Seats']
        }
    },
    { 
        id: 402, 
        name: 'Fitness Gym', 
        categoryKey: 'sports',
        description: 'Area dedicated to fitness equipment.', 
        icon: 'bi bi-heart-fill',
        fields: {
            resourceName: '', locationName: '', capacitySize: null, equipment: '',
            accessories: ['AC', 'Sound']
        }
    },
    { 
        id: 403, 
        name: 'Swimming Pool', 
        categoryKey: 'sports',
        description: 'Aquatic area for swimming and water sports.', 
        icon: 'bi bi-water',
        fields: {
            resourceName: '', locationName: '', capacitySize: null, equipment: 'Pool Equipment',
            accessories: ['Flash Light', 'Seats']
        }
    },
    { 
        id: 404, 
        name: 'Outdoor Field', 
        categoryKey: 'sports',
        description: 'Large area for outdoor team sports.', 
        icon: 'bi bi-flag',
        fields: {
            resourceName: '', locationName: '', capacitySize: null, equipment: 'Goal Posts, Nets',
            accessories: ['Flash Light', 'Seats']
        }
    },
];

// Computed property to filter templates based on the URL query parameter
const templates = computed<Template[]>(() => {
    return ALL_TEMPLATES.filter(t => t.categoryKey === templateCategoryKey.value);
});

// Function to handle the template selection
const selectTemplate = (template: Template) => {
    // Navigate to the final resource creation page, passing the template ID for pre-filling the form.
    router.push({ 
        path: '/add-resource', 
        query: { templateId: template.id, category: template.categoryKey } 
    });
};
</script>

<style scoped>
/* --- General Layout & Responsiveness --- */
.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}
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
.btn-outline-secondary {
    --bs-btn-color: #6c757d;
    --bs-btn-border-color: #6c757d;
    --bs-btn-hover-color: #ffffff;
    --bs-btn-hover-bg: #6c757d;
    --bs-btn-hover-border-color: #6c757d;
}

/* --- Template Card Styling --- */
.template-card {
    background: #f8f9fa; 
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.template-card:hover {
    background: #e9ecef; 
    border-color: #fcc300; 
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.template-icon {
    font-size: 2.5rem;
    color: #1e4449; 
    margin-bottom: 10px;
}

.template-card .card-title {
    font-weight: 600;
    color: #1e4449;
    font-size: 1.15rem;
}

.template-card .badge {
    background-color: #fcc300 !important;
    color: #1e4449;
    font-weight: 700;
    padding: 0.5em 1em;
}
</style>