<template>
  <div class="q-pa-md">
    <div class="text-h5 q-mb-md">Informations</div>
    <q-list bordered separator>
      <q-item>
        <q-item-section>
          <q-item-label>
            Dernière mise à jour
            <q-btn
              v-if="
                server.documentation !== undefined &&
                server.documentation !== ''
              "
              size="sm"
              rounded
              dense
              class="q-ml-sm"
              icon="description"
              :href="server.documentation"
              target="_blank"
            />
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-item-label>
            <template
              v-if="
                server.lastUpdate !== undefined ||
                server.lastCheck !== undefined
              "
            >
              {{ showLastAction(server.lastUpdate, server.lastCheck) }}&nbsp;
            </template>
            <q-btn
              round
              color="primary"
              icon="update"
              size="sm"
              class="q-mr-sm"
              v-if="!updated"
              @click="updateServer"
            >
              <q-tooltip>Mettre à jour</q-tooltip>
            </q-btn>
            <q-btn
              round
              color="primary"
              icon="how_to_reg"
              size="sm"
              v-if="!updated"
              @click="checkServer"
            >
              <q-tooltip>Vérifier</q-tooltip>
            </q-btn>
            <q-icon v-else size="sm" name="check" />
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>

    <q-list bordered separator>
      <q-item
        v-for="app in server.apps"
        :key="`server-${server.id}-app-${app.id}`"
      >
        <q-item-section>
          <q-item-label>
            {{ app.name }} - {{ app.currentVersion }}
            <q-btn
              v-if="app.documentation !== undefined && app.documentation !== ''"
              size="sm"
              rounded
              dense
              class="q-ml-sm"
              icon="description"
              :href="app.documentation"
              target="_blank"
            />
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-item-label>
            {{ showDate(app.lastUpdate, true) }}
            <q-btn
              v-if="
                app.currentVersion !== app.latestVersion &&
                app.latestVersion !== undefined
              "
              size="md"
              dense
              color="primary"
              @click="updateApp(app)"
              >{{ app.latestVersion }}</q-btn
            >
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </div>
</template>

<script setup lang="ts">
import { dateToSql, showLastAction, showDate } from 'src/libs/DateHelper';
import { App, Server } from './models';
import { inject, ref } from 'vue';
import { useServersStore } from 'src/stores/servers-store';
import { fetcher } from 'src/libs/Fetcher';
import { EventBus } from 'quasar';

defineOptions({
  name: 'ServerInformation',
});

export interface ServerInformationProps {
  server: Server;
}

const props = defineProps<ServerInformationProps>();

const updated = ref(false);
const serversStore = useServersStore();
const bus = inject('bus');

function updateServer() {
  updated.value = true;
  serversStore.servers[props.server.id].lastUpdate = dateToSql(
    new Date(Date.now()),
  );
  fetcher(`/api/servers/${props.server.id}`, 'PATCH', {
    lastUpdate: serversStore.servers[props.server.id].lastUpdate,
  }).then((response) => {
    if (response.status === 200) {
      (bus as EventBus).emit('server-update', props.server.id);
    }
  });
}

function checkServer() {
  updated.value = true;
  serversStore.servers[props.server.id].lastCheck = dateToSql(
    new Date(Date.now()),
  );
  fetcher(`/api/servers/${props.server.id}`, 'PATCH', {
    lastCheck: serversStore.servers[props.server.id].lastCheck,
  }).then((response) => {
    if (response.status === 200) {
      (bus as EventBus).emit('server-update', props.server.id);
    }
  });
}

function updateApp(app: App) {
  app.currentVersion = app.latestVersion;
  app.lastUpdate = dateToSql(new Date(Date.now()));
  fetcher(`/api/apps/${app.id}`, 'PATCH', {
    currentVersion: app.currentVersion,
    lastUpdate: app.lastUpdate,
  }).then((response) => {
    if (response.status === 200) {
      (bus as EventBus).emit('server-update', props.server.id);
    }
  });
}
</script>
