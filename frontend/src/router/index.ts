import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import ForgotPassword from '../views/ForgotPassword.vue';
import Dashboard from '../views/MasterAdmin/Dashboard.vue';
import Resources from '../views/MasterAdmin/Resources.vue';
import Booking from '../views/MasterAdmin/Booking.vue';
import Users from '../views/MasterAdmin/Users.vue';
import Reports from '../views/MasterAdmin/Reports.vue';
import Setting from '../views/MasterAdmin/Setting.vue';
import Categories from '../../../frontend/src/views/MasterAdmin/Categories.vue';
import Templates from '../views/MasterAdmin/Templates.vue';


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
    component: Dashboard,
    meta: { requiresAuth: true, role: 'master-admin' }
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
