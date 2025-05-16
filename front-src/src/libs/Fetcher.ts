import { useUserStore } from 'src/stores/user-store';
import { useRouter } from 'vue-router';
import { useSettingsStore } from 'src/stores/settings-store';

const userStore = useUserStore();

export function connect(login: string, password: string): Promise<boolean> {
  return new Promise<boolean>((resolve) => {
    fetcher('/api/login', 'POST', { username: login, password: password }, true)
      .then((response: Response) => {
        if (response.status !== 200) {
          resolve(false);
        } else {
          response
            .json()
            .then((tokenData) => {
              userStore.update(tokenData.token, tokenData.refresh_token);
              const settingsStore = useSettingsStore();
              settingsStore.update();
              resolve(true);
            })
            .catch(() => {
              resolve(false);
            });
        }
      })
      .catch(() => {
        resolve(false);
      });
  });
}

export function refreshTokenFromServer(
  refreshToken?: string,
): Promise<boolean> {
  if (refreshToken === undefined) {
    refreshToken = userStore.token.refreshToken;
  }
  return new Promise<boolean>((resolve) => {
    fetcher('/api/token/refresh', 'POST', { refresh_token: refreshToken }, true)
      .then((response: Response) => {
        if (response.status !== 200) {
          resolve(false);
        } else {
          response
            .json()
            .then((tokenData) => {
              userStore.update(tokenData.token, tokenData.refresh_token);
              resolve(true);
            })
            .catch(() => {
              userStore.$reset();
              const router = useRouter();
              router.push('/login');
              resolve(false);
            });
        }
      })
      .catch(() => {
        resolve(false);
      });
  });
}

export function fetcher(
  url: string,
  method: string,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  data?: any,
  force?: boolean,
): Promise<Response> {
  if (
    Date.now() / 1000 > userStore.token.exp &&
    (force === undefined || force === false)
  ) {
    userStore.disconnect();
    return Promise.reject('Token invalid');
  }
  const requestInit: RequestInit = {
    method,
    headers: {},
  };
  if (data !== undefined) {
    if (method === 'PATCH') {
      requestInit.headers = {
        'Content-Type': 'application/merge-patch+json',
      };
    } else {
      requestInit.headers = {
        mode: 'no-cors',
        'Content-Type': 'application/json',
      };
    }
    requestInit.body = JSON.stringify(data);
  }
  if (userStore.user.connected) {
    requestInit.headers = {
      Authorization: 'Bearer ' + userStore.token.current,
      ...requestInit.headers,
    };
  }
  return fetch(url, requestInit);
}
