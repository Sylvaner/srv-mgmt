<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
        />

        <q-toolbar-title>SRV Managment</q-toolbar-title>

        <q-btn @click="disconnectUser" flat v-if="userStore.user.name !== ''">{{
          userStore.user.name
        }}</q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" bordered>
      <q-list>
        <MenuLink v-for="link in linksList" :key="link.title" v-bind="link" />
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { inject, ref } from 'vue';
import MenuLink, { MenuLinkProps } from 'components/MenuLink.vue';
import { useUserStore } from 'src/stores/user-store';
import { EventBus, useQuasar } from 'quasar';
import { useRouter } from 'vue-router';

defineOptions({
  name: 'MainLayout',
});

const userStore = useUserStore();
const bus = inject('bus') as EventBus;
const linksList: MenuLinkProps[] = [
  {
    title: 'Serveurs',
    caption: 'Liste',
    icon: 'dns',
    link: '/',
  },
  {
    title: 'Journaux',
    caption: 'Historique',
    icon: 'engineering',
    link: '/logs',
  },
  {
    title: 'Configuration',
    caption: 'Paramètres',
    icon: 'settings',
    link: 'settings',
  },
];

const leftDrawerOpen = ref(false);
const $q = useQuasar();
const router = useRouter();

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value;
}

function disconnectUser() {
  userStore.disconnect();
  router.push('/login');
}

bus.on('loading-start', () => {
  $q.loading.show();
});

bus.on('loading-end', () => {
  $q.loading.hide();
});
</script>
