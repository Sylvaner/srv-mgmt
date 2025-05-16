import { defineStore } from 'pinia';
import { fetcher } from 'src/libs/Fetcher';

const DAYS_TO_MILLISECONDS = 86400000;

interface State {
  settings: {
    alert: number;
    warning: number;
  };
  updateThreshold: {
    warning: number;
    alert: number;
  };
}

export const useSettingsStore = defineStore('settings', {
  state: (): State => ({
    settings: {
      alert: 30,
      warning: 15,
    },
    updateThreshold: {
      warning: 1296000000, // 0.5 mois
      alert: 2592000000, // 1 mois
    },
  }),
  actions: {
    update(): Promise<void> {
      return new Promise<void>((resolve, reject) => {
        fetcher('/api/settings', 'GET').then((response) => {
          if (response.status === 200) {
            response
              .json()
              .then((result) => {
                for (const settingItem of result) {
                  if (settingItem.name === 'alert_threshold') {
                    this.settings.alert = parseInt(settingItem.value);
                    this.updateThreshold.alert =
                      this.settings.alert * DAYS_TO_MILLISECONDS;
                  } else if (settingItem.name === 'warning_threshold') {
                    this.settings.warning = parseInt(settingItem.value);
                    this.updateThreshold.warning =
                      this.settings.warning * DAYS_TO_MILLISECONDS;
                  }
                }
                resolve();
              })
              .catch(() => reject);
          } else {
            reject();
          }
        });
      });
    },
  },
});
