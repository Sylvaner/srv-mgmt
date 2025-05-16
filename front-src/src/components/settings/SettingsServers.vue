<template>
  <q-splitter v-model="splittedPanel">
    <template v-slot:before>
      <div class="q-pa-md">
        <q-btn
          color="primary"
          icon="add"
          label="Ajouter un serveur"
          @click="showAddServerForm"
        />
      </div>
      <div class="q-pa-md">
        <q-list dense bordered padding class="rounded-borders">
          <q-item
            clickable
            v-ripple
            v-for="server of serversStore.sortedListByName"
            :key="`edit-server-${server.id}`"
          >
            <q-item-section @click="showEditServerForm(server.id)">{{
              server.name
            }}</q-item-section>
          </q-item>
        </q-list>
      </div>
    </template>
    <template v-slot:after>
      <div class="q-pa-md" v-if="showServerForm">
        <div class="text-h5 q-mb-md">
          {{ formMode === ServerFormMode.Add ? 'Ajout' : 'Modification' }}
        </div>
        <div class="q-gutter-md row">
          <q-input
            class="col"
            filled
            v-model="server.name"
            label="Nom du serveur"
            lazy-rules
            :rules="[
              (val) => (val && val.length >= 4) || 'Au moins 4 caractères',
            ]"
          />
          <q-input class="col" filled v-model="server.ip" label="Adresse IP" />
        </div>
        <div class="q-gutter-md row">
          <q-input
            class="col"
            filled
            v-model="server.documentation"
            label="Documentation"
          />
          <q-select
            class="col"
            v-model="server.type"
            :options="serverTypes"
            option-label="label"
            option-value="id"
            emit-value
            map-options
            label="Type de serveur"
            lazy-rules
            :rules="[(val) => val !== undefined || 'Sélectionner un type']"
          />
        </div>
        <template v-if="formMode === ServerFormMode.Edit">
          <SettingsAppForm
            class="q-my-md"
            v-for="app of server.apps"
            :key="`app-${server.id}-${app.id}`"
            :app="app"
            :app-update-types="appUpdateTypes"
            :edit="true"
            @onDelete="deletedApp"
            @onChange="refreshServerForm"
          >
          </SettingsAppForm>
          <SettingsAppForm
            class="q-my-md"
            :app="newApp"
            :app-update-types="appUpdateTypes"
            :edit="false"
            :server-id="server.id"
            @onDelete="deletedApp"
            @onChange="refreshServerForm"
          >
          </SettingsAppForm>
        </template>
        <div class="q-gutter-md row q-py-md">
          <q-btn
            :label="formMode === ServerFormMode.Add ? 'Ajouter' : 'Modifier'"
            class="col"
            :disable="!validForm"
            @click="onSubmit"
            color="primary"
            :icon="formMode === ServerFormMode.Add ? 'add' : 'edit'"
          />
          <q-btn
            v-if="formMode === ServerFormMode.Edit"
            class="col"
            color="red"
            icon="delete"
            label="Supprimer"
            @click="removeServer"
          />
        </div>
      </div>
    </template>
  </q-splitter>
</template>

<script setup lang="ts">
import { useQuasar } from 'quasar';
import {
  App,
  AppUpdateType,
  ServerFormData,
  ServerType,
} from 'src/components/models';
import SettingsAppForm from 'components/settings/SettingsAppForm.vue';
import { fetcher } from 'src/libs/Fetcher';
import { useServersStore } from 'src/stores/servers-store';
import { Ref, ref, computed } from 'vue';

defineOptions({
  name: 'SettingsServers',
});

enum ServerFormMode {
  Add,
  Edit,
}

const splittedPanel = ref(50);
const showServerForm = ref(false);
const serverTypes: Ref<ServerType[]> = ref([]);
const appUpdateTypes: Ref<AppUpdateType[]> = ref([]);
const server: Ref<ServerFormData> = ref({
  name: '',
  ip: '',
  apps: [],
  documentation: '',
});
const newApp: Ref<App> = ref({
  id: -1,
  name: '',
  currentVersion: '',
  lastUpdate: '',
  latestVersion: '',
  newVersion: '',
  updateResource: '',
  extraUpdateResource: '',
  documentation: '',
  updateType: {
    id: 0,
    name: '',
  },
});
const $q = useQuasar();
const formMode: Ref<ServerFormMode> = ref(ServerFormMode.Add);
const serversStore = useServersStore();
serversStore.updateAll();

$q.loading.show();
/**
 * Obtenir la liste des types de serveurs et des types de mises à jour
 */
fetcher('/api/server_types', 'GET').then((response) => {
  if (response.status === 200) {
    response.json().then((result) => {
      serverTypes.value = result;
    });
  }
  fetcher('/api/app_update_types', 'GET').then((response) => {
    if (response.status === 200) {
      $q.loading.hide();
      response.json().then((result) => {
        appUpdateTypes.value = result;
        if (result.length > 0) {
          newApp.value.updateType.id = result[0].id;
          newApp.value.updateType.name = result[0].name;
        }
      });
    }
  });
});

function showAddServerForm() {
  resetForm();
  formMode.value = ServerFormMode.Add;
  showServerForm.value = true;
}

function showEditServerForm(serverId: number) {
  formMode.value = ServerFormMode.Edit;
  const serverToEdit = { ...serversStore.servers[serverId] };
  server.value = {
    id: serverToEdit.id,
    name: serverToEdit.name,
    ip: serverToEdit.ip,
    apps: serverToEdit.apps,
    type: serverToEdit.type.id,
    documentation: serverToEdit.documentation,
  };
  showServerForm.value = true;
}

function resetForm() {
  server.value.id = undefined;
  server.value.name = '';
  server.value.ip = '';
  server.value.apps = [];
  server.value.documentation = '';
  formMode.value = ServerFormMode.Add;
}

function onSubmit() {
  if (!validForm.value) {
    return false;
  }
  let method = 'POST';
  let submitUrl = '/api/servers';
  if (formMode.value === ServerFormMode.Edit) {
    method = 'PATCH';
    submitUrl += '/' + server.value.id;
  }
  fetcher(submitUrl, method, server.value).then((response) => {
    serversStore.updateAll();
    if (method === 'POST' && response.status === 201) {
      showServerForm.value = false;
      $q.notify(`Serveur ${server.value.name} créé.`);
      resetForm();
    } else if (method === 'PATCH' && response.status === 200) {
      showServerForm.value = false;
      $q.notify(`Serveur ${server.value.name} modifié.`);
      resetForm();
    }
  });
}

const validForm = computed(() => {
  if (server.value.name.length < 4) {
    return false;
  }
  if (server.value.type === undefined) {
    return false;
  }
  return true;
});

function refreshServerForm() {
  serversStore.updateAll().then(() => {
    showEditServerForm(server.value.id!);
  });
}

function deletedApp(appName: string) {
  $q.notify(
    `Serveur ${server.value.name} modifié, application ${appName} supprimée.`,
  );
  refreshServerForm();
}

function removeServer() {
  fetcher('/api/servers/' + server.value.id, 'DELETE').then((response) => {
    serversStore.updateAll();
    if (response.status === 204) {
      $q.notify(`Serveur ${server.value.name} supprimé.`);
    }
    resetForm();
  });
}
</script>
