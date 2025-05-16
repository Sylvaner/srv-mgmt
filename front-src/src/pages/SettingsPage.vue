<template>
  <q-page>
    <q-tabs v-model="tab">
      <q-tab name="general" label="Général" />
      <q-tab name="servers" label="Serveurs" />
      <q-tab name="server_types" label="Types de serveurs" />
    </q-tabs>
    <q-separator />
    <q-tab-panels v-model="tab" animated>
      <q-tab-panel name="general">
        <SettingsGeneral />
      </q-tab-panel>
      <q-tab-panel name="servers">
        <SettingsServers />
      </q-tab-panel>
      <q-tab-panel name="server_types">
        <SettingsServerTypes />
      </q-tab-panel>
    </q-tab-panels>
  </q-page>
</template>

<script setup lang="ts">
import SettingsGeneral from 'components/settings/SettingsGeneral.vue';
import SettingsServers from 'components/settings/SettingsServers.vue';
import SettingsServerTypes from 'components/settings/SettingsServerTypes.vue';
import { ref } from 'vue';

defineOptions({
  name: 'SettingsPage',
});

const tab = ref('general');
</script>
