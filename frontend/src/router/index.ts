import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import ForgotPassword from '../views/ForgotPassword.vue';
import MasterAdminDashboard from '../views/MasterAdmin/MasterAdminDashboard.vue';
import Categories from '../views/MasterAdmin/Categories.vue';
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
    redirect: '/categories'
  },
  {
    path:'/categories',
    name: 'categories',
    component:Categories
  },
  {
    path:'/',
    redirect: '/templates'
  },
  {
    path: '/templates',
    name: 'tepmlates',
    component:Templates
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
