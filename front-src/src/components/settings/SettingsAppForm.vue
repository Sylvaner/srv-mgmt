<template>
  <q-card class="q-pa-md">
    <div class="q-gutter-md row">
      <div class="text-h6 q-mb-md">
        {{ edit ? 'Modifier une application' : 'Ajouter une application' }}
      </div>
    </div>
    <div class="q-gutter-md row">
      <q-input filled v-model="editableApp.name" label="Nom" class="col" />
      <q-input
        class="col"
        filled
        v-model="editableApp.updateResource"
        label="Information de mise à jour"
      />
    </div>
    <div class="q-gutter-md row q-pt-md">
      <q-input
        class="col"
        filled
        v-model="editableApp.extraUpdateResource"
        label="Information complémentaire"
      />
    </div>
    <div class="q-gutter-md row q-py-md">
      <q-input
        filled
        v-model="editableApp.documentation"
        label="Documentation"
        class="col"
      />
      <q-select
        v-model="editableApp.updateType"
        :options="appUpdateTypes"
        option-label="name"
        option-value="id"
        emit-value
        map-options
        label="Type de mise à jour"
        class="col"
      />
    </div>
    <div class="q-gutter-md row">
      <q-btn
        class="col"
        color="primary"
        :icon="edit ? 'edit' : 'add'"
        :label="edit ? 'Mettre à jour' : 'Ajouter'"
        @click="apply"
      />
      <q-btn
        v-if="edit"
        class="col"
        color="red"
        icon="delete"
        label="Supprimer"
        @click="remove"
      />
    </div>
  </q-card>
</template>

<script setup lang="ts">
import { fetcher } from 'src/libs/Fetcher';
import { App, AppUpdateType } from '../models';
import { ref } from 'vue';

defineOptions({
  name: 'SettingsAppForm',
});

export interface AppFormProps {
  app: App;
  appUpdateTypes: AppUpdateType[];
  edit: boolean;
  serverId?: number;
}

const props = defineProps<AppFormProps>();
const emit = defineEmits<{
  (e: 'onDelete', appName: string): void;
  (e: 'onChange'): void;
}>();
const editableApp = ref({
  name: props.app.name,
  updateType: props.app.updateType.id,
  updateResource: props.app.updateResource,
  extraUpdateResource: props.app.extraUpdateResource,
  documentation: props.app.documentation,
});

function apply() {
  if (props.edit) {
    fetcher('/api/apps/' + props.app.id, 'PATCH', editableApp.value).then(
      (response) => {
        if (response.status === 200) {
          emit('onChange');
        }
      },
    );
  } else if (props.serverId !== undefined) {
    const appData = {
      ...editableApp.value,
      server: props.serverId,
      currentVersion: '',
      lastUpdate: '1980-01-01 00:00:00',
      latestVersion: '',
    };
    fetcher('/api/apps', 'POST', appData).then((response) => {
      if (response.status === 201) {
        editableApp.value.name = '';
        editableApp.value.updateResource = '';
        editableApp.value.documentation = '';
        editableApp.value.extraUpdateResource = '';
        emit('onChange');
      }
    });
  }
}

function remove() {
  fetcher('/api/apps/' + props.app.id, 'DELETE', editableApp.value).then(
    (response) => {
      if (response.status === 204) {
        emit('onDelete', props.app.name);
      }
    },
  );
}
</script>
