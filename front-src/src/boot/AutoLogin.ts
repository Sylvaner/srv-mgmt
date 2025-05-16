import { boot } from 'quasar/wrappers';
import { refreshTokenFromServer } from 'src/libs/Fetcher';

// Login automatique à partir du refresh token
export default boot(async () => {
  const refreshToken = localStorage.getItem('refreshToken');
  if (refreshToken !== null) {
    await refreshTokenFromServer(refreshToken);
  }
});
