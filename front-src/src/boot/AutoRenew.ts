import { boot } from 'quasar/wrappers';
import { refreshTokenFromServer } from 'src/libs/Fetcher';
import { useUserStore } from 'src/stores/user-store';

// Renouvellement automatique du token
export default boot(({ store }) => {
  const userStore = useUserStore(store);
  let refreshing = false;

  setInterval(checkToken, 60000);

  /**
   * Le token n'est renouvelé qu'une fois la moitié de sa durée de vie écoulée.
   * Auparavant, la comparaison se faisait avec la date d'émission (iat) : elle
   * était toujours vraie et déclenchait un renouvellement (donc des requêtes
   * LDAP côté serveur) toutes les minutes pour chaque onglet ouvert.
   */
  async function checkToken() {
    if (refreshing || userStore.token.current === '') {
      return;
    }
    const { iat, exp } = userStore.token;
    const renewAt = iat + (exp - iat) / 2;
    if (Date.now() / 1000 > renewAt) {
      refreshing = true;
      try {
        await refreshTokenFromServer();
      } finally {
        refreshing = false;
      }
    }
  }
});
