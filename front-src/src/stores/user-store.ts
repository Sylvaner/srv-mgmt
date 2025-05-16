import { defineStore } from 'pinia';

interface TokenContent {
  iat: number;
  exp: number;
  username: string;
  roles: string[];
}

interface State {
  token: {
    current: string;
    refreshToken: string;
    iat: number;
    exp: number;
  };
  user: {
    connected: boolean;
    name: string;
    roles: string[];
  };
}

export const useUserStore = defineStore('user', {
  state: (): State => ({
    token: {
      current: '',
      refreshToken: '',
      iat: 9999999999,
      exp: 9999999999,
    },
    user: {
      connected: false,
      name: '',
      roles: [],
    },
  }),
  actions: {
    update(token: string, refreshToken: string) {
      try {
        const tokenContent: TokenContent = JSON.parse(
          atob(token.split('.')[1]),
        );
        this.token.current = token;
        this.token.refreshToken = refreshToken;
        this.user.name = tokenContent.username;
        this.user.roles = tokenContent.roles;
        this.token.iat = tokenContent.iat;
        this.token.exp = tokenContent.exp;
        this.user.connected = true;
        this.save();
      } catch (e) {
        console.error(e);
      }
    },
    disconnect() {
      console.log(localStorage);
      this.$reset();
      this.save();
    },
    save() {
      localStorage.setItem('refreshToken', this.token.refreshToken);
    },
  },
});
