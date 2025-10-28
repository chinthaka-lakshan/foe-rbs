import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import ForgotPassword from '../views/ForgotPassword.vue';
import Dashboard from '../views/MasterAdmin/Dashboard.vue';
import MasterAdminDashboard from '../views/MasterAdmin/MasterAdminDashboard.vue';


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
    path: '/master-admin',
    name: 'MasterAdminDashboard',
    component: MasterAdminDashboard,
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
