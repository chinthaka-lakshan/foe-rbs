import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import ForgotPassword from '../views/ForgotPassword.vue';
import Master_Admin_Dashboard from '../views/MasterAdmin/Master_Admin_Dashboard.vue';
import Resources from '../views/MasterAdmin/Resources.vue';
import Booking from '../views/MasterAdmin/Booking.vue';
import Users from '../views/MasterAdmin/Users.vue';
import Reports from '../views/MasterAdmin/Reports.vue';
import Setting from '../views/MasterAdmin/Setting.vue';
import Categories from '../../../frontend/src/views/MasterAdmin/Categories.vue';
import Templates from '../views/MasterAdmin/Templates.vue';
import Add_Resource from '../views/MasterAdmin/Add_Resource.vue';
import Use_Template from '../views/MasterAdmin/Use_Template.vue';
import Single_Resource from '../views/MasterAdmin/Single_Resource.vue';
import Single_Resource_Booking from '../views/MasterAdmin/Single_Resource_Booking.vue';
import Booking_Item from '../views/MasterAdmin/Booking_Item.vue';
import User_Dashboard from '../views/User/User_Dashboard.vue';
import Admin_Dashboard from '../views/Admin/Admin_Dashboard.vue';


const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },

  {
    path:'/',
    redirect: '/register',
    
  },
  {
    path:'/register',
    name: 'Register',
    component: Register
  },
  
  {
    path:'/',
    redirect: '/forgot-password',
    
  },
  {
    path:'/forgot-password',
    name: 'forgot-password',
    component: ForgotPassword
  },
  {
    path:'/',
    redirect:'/categories'
  },
  {
    path:'/categories',
    name:'categories',
    component:Categories
  },

  {
    path: '/',
    redirect: 'booking_item'
  },
  {
   path: '/booking_item',
   name: 'booking-item',
   component:Booking_Item
  },
  {
    path:'/',
    redirect:'/templates'
  },
  {
    path:'/templates',
    name:'templates',
    component:Templates
  },

  {
    path:'/',
    redirect: '/master-admin/resource'
  },
  {
    path: '/master-admin/resource',
    name: 'master-admin-resource',
    component:Resources
  },

  {
    path:'/',
    redirect:'/add-resource'
  },

  {
    path:'/add-resource',
    name:'add-resource',
    component:Add_Resource
  },

  {
    path:'/resource/:id',
    name:'Single-Resource',
    component:Single_Resource
  },

  {
    path:'/single-resource-booking',
    name:'single-resource-booking',
    component:Single_Resource_Booking
  },

  {
    path:'/',
    redirect:'/use-template'
  },
  {
    path:'/use-template',
    name:'use-template',
    component:Use_Template
  },

  {
    path:'/',
    redirect: '/master-admin/booking'
  },
  {
    path: '/master-admin/booking',
    name: 'master-admin-booking',
    component:Booking
  },

  {
    path:'/',
    redirect: '/master-admin/users'
  },
  {
    path: '/master-admin/users',
    name: 'master-admin-users',
    component:Users
  },

  {
    path:'/',
    redirect: '/master-admin/reports'
  },
  {
    path: '/master-admin/reports',
    name: 'master-admin-reports',
    component:Reports
  },
  {
    path:'/',
    redirect:'/master-admin/setting'
  },
  {
    path:'/master-admin/setting',
    name:'master-admin-setting',
    component:Setting
  },
  {
    path: '/master-admin/dashboard',
    name: 'master-admin-dashboard',
    component: Master_Admin_Dashboard,
    meta: { requiresAuth: true, role: 'Master Admin' }
  },

  {
    path: '/user/dashboard',
    name: 'user-dashboard',
    component: User_Dashboard,
    meta: { requiresAuth: true, role: 'User' }
  },

   {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: Admin_Dashboard,
    meta: { requiresAuth: true, role: 'admin' }
  },

 
  

];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, _from, next) => {
  const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
  const userRole = localStorage.getItem('userRole');

  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login');
  } else if (to.meta.role && userRole !== to.meta.role) {
    next('/login');
  } else {
    next();
  }
});

export default router;
