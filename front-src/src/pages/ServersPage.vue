<template>
  <q-page>
    <div class="q-pa-md">
      <div class="text-h4 q-mb-md">Serveurs</div>
      <div class="row">
        <div class="col">
          <q-toggle
            v-model="filter"
            toggle-indeterminate
            toggle-order="ft"
            :indeterminate-value="null"
            :label="`${filter === null ? 'Tous' : filter ? 'À jour' : 'Mise à jour'}`"
            :color="filter === null ? 'grey' : filter ? 'green' : 'red'"
            keep-color
            class="q-pr-md"
          />
          <q-checkbox v-model="byDate" label="Tri chronologique" dense />
        </div>
        <div class="col">
          <q-input
            type="text"
            v-model="textFilter"
            label="Filtrer"
            :dense="true"
          />
        </div>
      </div>
    </div>
    <q-list bordered class="full-width">
      <ServerListItem
        v-for="server in filteredList"
        :key="`server-${server.id}`"
        :server="server"
      />
    </q-list>
  </q-page>
</template>

<script setup lang="ts">
import ServerListItem from 'components/ServerListItem.vue';
import { Server } from 'src/components/models';
import { getLastAction } from 'src/libs/DateHelper';
import { useServersStore } from 'src/stores/servers-store';
import { useSettingsStore } from 'src/stores/settings-store';
import { computed, ref } from 'vue';

defineOptions({
  name: 'ServersPage',
});

const filter = ref(null);
const byDate = ref(false);
const textFilter = ref('');
const serversStore = useServersStore();
const settingsStore = useSettingsStore();
serversStore.updateAll();

/**
 * Liste filtrée des serveurs
 */
const filteredList = computed(() => {
  let rawList: Server[] = [];
  if (byDate.value) {
    rawList = serversStore.sortedListByDate;
  } else {
    rawList = serversStore.sortedListByName;
  }
  rawList = rawList.filter((value) => value.disabled === false);
  rawList = rawList.filter(
    (value) => value.name.indexOf(textFilter.value) >= 0,
  );
  // Aucun filtre n'a été sélectionné
  if (filter.value === null) {
    return rawList;
  }
  // Filtre en fonction du statut
  return rawList.filter((server: Server) => {
    let result = false;
    const lastAction = getLastAction(server.lastUpdate, server.lastCheck);
    if (lastAction === null) {
      result = true;
    } else {
      const updateDate = Date.parse(lastAction);
      if (updateDate + settingsStore.updateThreshold.warning < Date.now()) {
        result = true;
      } else {
        for (const app of server.apps) {
          if (
            (app.currentVersion !== app.latestVersion &&
              app.latestVersion !== undefined) ||
            app.lastUpdate === undefined
          ) {
            result = true;
          }
        }
      }
    }
    if (filter.value === true) {
      return !result;
    } else {
      return result;
    }
  });
});
</script>

<style>
.q-toggle__inner--falsy .q-toggle__thumb:after {
  background-color: currentColor;
}
</style>
