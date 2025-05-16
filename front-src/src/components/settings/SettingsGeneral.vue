<template>
  <q-card>
    <div class="q-pa-md">
      <q-input
        v-model="alertThreshold"
        label="Seuil d'alerte"
        hint="Nombre de jours avant qu'une alerte soit déclenchée"
      >
        <template v-slot:after>
          <q-btn
            round
            dense
            flat
            icon="check"
            @click="update('alert_threshold', alertThreshold)"
          />
        </template>
      </q-input>
    </div>
    <div class="q-pa-md">
      <q-input
        v-model="warningThreshold"
        label="Seuil d'avertissement"
        hint="Nombre de jours avant qu'un avertissement soit affichée"
      >
        <template v-slot:after>
          <q-btn
            round
            dense
            flat
            icon="check"
            @click="update('warning_threshold', warningThreshold)"
          />
        </template>
      </q-input>
    </div>
  </q-card>
</template>

<script setup lang="ts">
import { useQuasar } from 'quasar';
import { fetcher } from 'src/libs/Fetcher';
import { Ref, ref } from 'vue';
import { useSettingsStore } from 'src/stores/settings-store';

defineOptions({
  name: 'SettingsServerTypes',
});

const alertThreshold: Ref<number> = ref(0);
const warningThreshold: Ref<number> = ref(0);

const $q = useQuasar();
const settingsStore = useSettingsStore();

fillCurrentSettings();

/**
 * Obtenir la liste des types de serveurs
 */
function fillCurrentSettings() {
  alertThreshold.value = settingsStore.settings.alert;
  warningThreshold.value = settingsStore.settings.warning;
}

function update(name: string, value: number) {
  fetcher('/api/settings/' + name, 'PATCH', {
    name,
    value,
  }).then((response) => {
    if (response.status === 200) {
      $q.notify('Valeur mis à jour.');
      settingsStore.update();
    }
  });
}
</script>
