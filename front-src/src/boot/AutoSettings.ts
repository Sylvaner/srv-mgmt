import { boot } from 'quasar/wrappers';
import { useSettingsStore } from 'src/stores/settings-store';
import { useUserStore } from 'src/stores/user-store';

// Renouvellement automatique du certificat
export default boot(async ({ store }) => {
  const userStore = useUserStore(store);
  if (userStore.user.connected) {
    const settingsStore = useSettingsStore(store);
    await settingsStore.update();
  }
});
