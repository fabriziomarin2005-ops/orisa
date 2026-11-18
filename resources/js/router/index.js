import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from '../views/DashboardView.vue';
import ZonasView from '../views/ZonasView.vue';
import LoginView from '../views/LoginView.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { public: true },
    },
    {
        path: '/',
        name: 'dashboard',
        component: DashboardView,
        meta: { requiresAuth: true },
    },
    {
        path: '/zonas',
        name: 'zonas',
        component: ZonasView,
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('orisa_auth_token');

    if (to.meta.requiresAuth && !isAuthenticated) {
        return next({ name: 'login' });
    }

    if (to.name === 'login' && isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
