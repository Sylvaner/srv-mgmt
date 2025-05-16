<template>
  <q-splitter v-model="splittedPanel">
    <template v-slot:before>
      <q-input
        class="q-ma-md"
        label="Nom du type"
        v-model="newServerTypeLabel"
        lazy-rules
        :rules="[
          (val) => val.length >= 4 || 'Longueur minimum de 4 caractères',
        ]"
      />
      <q-btn
        class="q-ma-md"
        label="Ajouter un type"
        @click="addNewServerType"
      />
    </template>
    <template v-slot:after>
      <q-list dense bordered padding class="rounded-borders">
        <q-item
          clickable
          v-ripple
          v-for="serverType of serverTypes"
          :key="`edit-server-type-${serverType.id}`"
        >
          <q-item-section @click="showUpdateForm(serverType)">{{
            serverType.label
          }}</q-item-section>
        </q-item>
      </q-list>
      <q-card v-if="updateFormShowed">
        <q-input
          class="q-ma-md"
          label="Nom du type"
          v-model="updateServerType.label"
        />
        <div class="q-gutter-md row q-py-md">
          <q-btn
            label="Mettre à jour"
            class="col"
            color="primary"
            icon="edit"
            @click="update"
          />
          <q-btn
            class="col"
            color="red"
            icon="delete"
            label="Supprimer"
            @click="remove"
          />
        </div>
      </q-card>
    </template>
  </q-splitter>
</template>

<script setup lang="ts">
import { useQuasar } from 'quasar';
import { ServerType } from 'src/components/models';
import { fetcher } from 'src/libs/Fetcher';
import { Ref, ref } from 'vue';

defineOptions({
  name: 'SettingsServerTypes',
});

const splittedPanel = ref(50);
const serverTypes: Ref<ServerType[]> = ref([]);
const newServerTypeLabel: Ref<string> = ref('');
const updateServerType: Ref<ServerType> = ref({
  id: -1,
  label: '',
});
const updateFormShowed: Ref<boolean> = ref(false);
const $q = useQuasar();

$q.loading.show();
updateServerTypes();

/**
 * Obtenir la liste des types de serveurs
 */
function updateServerTypes() {
  fetcher('/api/server_types', 'GET').then((response) => {
    if (response.status === 200) {
      response.json().then((result) => {
        serverTypes.value = result;
        $q.loading.hide();
      });
    }
  });
}

function addNewServerType() {
  if (newServerTypeLabel.value.length >= 4) {
    fetcher('/api/server_types', 'POST', {
      label: newServerTypeLabel.value,
    }).then((response) => {
      if (response.status === 201) {
        updateServerTypes();
        newServerTypeLabel.value = '';
      }
    });
  }
}

function showUpdateForm(serverType: ServerType) {
  updateServerType.value = serverType;
  updateFormShowed.value = true;
}

function update() {
  fetcher(
    '/api/server_types/' + updateServerType.value.id,
    'PATCH',
    updateServerType.value,
  ).then((response) => {
    if (response.status === 200) {
      $q.notify(`Type de serveur ${updateServerType.value.label} mis à jour.`);
    }
    updateFormShowed.value = false;
  });
}

function remove() {
  fetcher('/api/server_types/' + updateServerType.value.id, 'DELETE').then(
    (response) => {
      updateServerTypes();
      if (response.status === 204) {
        $q.notify(`Type de serveur ${updateServerType.value.label} supprimé.`);
      }
      updateFormShowed.value = false;
    },
  );
}
</script>
