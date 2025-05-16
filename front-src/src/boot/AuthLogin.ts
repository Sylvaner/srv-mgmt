import { boot } from 'quasar/wrappers';
import { useUserStore } from 'src/stores/user-store';

// Redirection à la page de connexion au niveau des routes
export default boot(({ router, store }) => {
  const userStore = useUserStore(store);

  // Connexion pour accéder au site
  router.beforeEach((to, from, next) => {
    if (to.path === '/login') {
      next();
    } else if (userStore.user.connected) {
      next();
    } else {
      next('/login');
    }
  });
});
