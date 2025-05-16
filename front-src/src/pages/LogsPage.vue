<template>
  <div class="q-pa-md">
    <q-infinite-scroll @load="onLoad" :offset="250" ref="logsScroll">
      <div v-for="(item, index) in items" :key="index" class="row">
        <template v-if="item.message !== 'END_OF_LIST'">
          <div class="col">{{ showDate(item.date) }}</div>
          <div class="col">{{ item.server.name }}</div>
          <div class="col">{{ item.message }}</div>
          <div class="col">{{ item.username }}</div>
        </template>
        <div v-else class="col">Fin</div>
      </div>
      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>
  </div>
</template>

<script setup lang="ts">
import { QInfiniteScroll } from 'quasar';
import { Log } from 'src/components/models';
import { showDate } from 'src/libs/DateHelper';
import { fetcher } from 'src/libs/Fetcher';
import { Ref, ref } from 'vue';

defineOptions({
  name: 'LogsPage',
});

const items: Ref<Log[]> = ref([]);
const logsScroll = ref(QInfiniteScroll);

function onLoad(index: number, done: () => void) {
  fetcher(`/api/logs?pagination=true&page=${index}`, 'GET').then((response) => {
    if (response.status === 200) {
      response.json().then((result: Log[]) => {
        if (result.length === 0) {
          items.value.push({
            id: -1,
            date: '',
            message: 'END_OF_LIST',
            username: '',
            server: {
              id: -1,
              name: '',
            },
          });
          logsScroll.value.stop();
        } else {
          for (const row of result) {
            items.value.push(row);
          }
          done();
        }
      });
    }
  });
}
</script>
