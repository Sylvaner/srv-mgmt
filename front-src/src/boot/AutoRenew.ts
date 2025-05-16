import { boot } from 'quasar/wrappers';
import { refreshTokenFromServer } from 'src/libs/Fetcher';
import { useUserStore } from 'src/stores/user-store';

// Renouvellement automatique du certificat
export default boot(({ store }) => {
  const userStore = useUserStore(store);

  setInterval(checkToken, 60000);

  function checkToken() {
    if (
      userStore.token.current !== '' &&
      Date.now() / 1000 > userStore.token.iat
    ) {
      refreshTokenFromServer();
    }
  }
});
