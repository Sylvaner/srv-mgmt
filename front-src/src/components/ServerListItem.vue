<template>
  <q-expansion-item expand-separator @show="updateLogs">
    <template v-slot:header>
      <q-item-section avatar>
        <q-avatar :color="serverToUpdateColor" size="sm">
          <q-badge color="red" floating v-if="appsToUpdateCount > 0">
            {{ appsToUpdateCount }}
          </q-badge>
        </q-avatar>
      </q-item-section>
      <q-item-section>
        <q-item-label>
          {{ server.name }}
          <span v-if="server.ip !== '' && server.ip !== undefined">
            - {{ server.ip }}</span
          >
        </q-item-label>
        <q-item-label caption>{{ server.type.label }}</q-item-label>
      </q-item-section>
      <q-item-section side v-if="server.lastUpdate !== undefined">
        {{ showDate(server.lastUpdate) }}
      </q-item-section>
      <q-item-section side v-else-if="server.lastCheck !== undefined">
        {{ showDate(server.lastCheck) }}
      </q-item-section>
    </template>
    <template v-slot:default>
      <q-card>
        <q-card-section>
          <q-splitter v-model="splitterSize" style="height: 20rem">
            <template v-slot:before>
              <ServerInformation :server="server" />
            </template>

            <template v-slot:after>
              <div class="q-pa-md">
                <div class="text-h5 q-mb-md">Journaux</div>
                <q-list bordered separator v-if="logs.length > 0">
                  <q-item
                    v-for="(log, index) in logs"
                    :key="`log-${server.id}-${index}`"
                  >
                    <q-item-section>
                      <q-item-label
                        >{{ log.message }} - {{ log.username }}</q-item-label
                      >
                      <q-item-label caption>{{
                        showDate(log.date)
                      }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
                <span v-else>Vide</span>
              </div>
            </template>
          </q-splitter></q-card-section
        >
      </q-card>
    </template>
  </q-expansion-item>
</template>

<script setup lang="ts">
import ServerInformation from './ServerInformation.vue';
import { Server, ServerLogs } from 'src/components/models';
import { fetcher } from 'src/libs/Fetcher';
import { useSettingsStore } from 'src/stores/settings-store';
import { Ref, computed, inject, ref } from 'vue';
import { getLastAction, showDate } from 'src/libs/DateHelper';
import { EventBus } from 'quasar';

defineOptions({
  name: 'ServersPage',
});

const props = defineProps<ServerListItemProps>();
export interface ServerListItemProps {
  server: Server;
}
const splitterSize = ref(50);
const logs: Ref<ServerLogs[]> = ref([]);
const settingsStore = useSettingsStore();
const bus = inject('bus');

function updateLogs() {
  fetcher(`/api/servers/${props.server.id}/logs`, 'GET').then((response) => {
    if (response.status === 200) {
      response.json().then((result) => {
        logs.value = result;
      });
    }
  });
}

(bus as EventBus).on('server-update', (serverId: number) => {
  if (props.server.id === serverId) {
    updateLogs();
  }
});

const serverToUpdateColor = computed(() => {
  const lastAction = getLastAction(
    props.server.lastUpdate,
    props.server.lastCheck,
  );
  if (lastAction === null) {
    return 'red';
  }
  const updateDate = Date.parse(lastAction);
  if (updateDate + settingsStore.updateThreshold.alert < Date.now()) {
    return 'red';
  }
  if (updateDate + settingsStore.updateThreshold.warning < Date.now()) {
    return 'orange';
  }
  return 'green';
});

const appsToUpdateCount = computed(() => {
  let toUpdateCount = 0;
  for (const app of props.server.apps) {
    if (
      app.currentVersion !== app.latestVersion &&
      app.latestVersion !== undefined
    ) {
      ++toUpdateCount;
    }
  }
  return toUpdateCount;
});
</script>
