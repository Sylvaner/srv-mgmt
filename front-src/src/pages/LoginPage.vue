<template>
  <q-page>
    <div class="row justify-center">
      <div class="col-4">
        <h4>Connexion</h4>
        <q-input v-model="login" label="Identifiant" />
        <q-input
          v-model="password"
          :type="showPassword ? 'text' : 'password'"
          label="Mot de passe"
          @keydown.enter="tryToConnect"
        >
          <template v-slot:append>
            <q-icon
              :name="showPassword ? 'visibility' : 'visibility_off'"
              class="cursor-pointer"
              @click="showPassword = !showPassword"
            />
          </template>
        </q-input>
        <div>
          <q-btn
            class="q-mt-md primary full-width"
            :icon-right="validIcon"
            label="Se connecter"
            @click="tryToConnect"
          />
        </div>
        <q-banner
          v-if="badPassword"
          inline-actions
          class="q-mt-md text-white bg-red"
        >
          Mauvais mot de passe
        </q-banner>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { connect } from 'src/libs/Fetcher';
import { Ref, ref } from 'vue';
import { useRouter } from 'vue-router';

const login: Ref<string> = ref('');
const password: Ref<string> = ref('');
const showPassword: Ref<boolean> = ref(false);
const badPassword: Ref<boolean> = ref(false);
const validIcon: Ref<string> = ref('send')
const router = useRouter();

function tryToConnect() {
  validIcon.value = 'hourglass_empty';
  connect(login.value, password.value).then((result) => {
    badPassword.value = !result;
    if (result) {
      router.push({ path: '/' });
    }
    validIcon.value = 'send';
  });
}
</script>
