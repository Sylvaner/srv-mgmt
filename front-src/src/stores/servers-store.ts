import { defineStore } from 'pinia';
import { Server } from 'src/components/models';
import { fetcher } from 'src/libs/Fetcher';

interface State {
  servers: Record<number, Server>;
}

export const useServersStore = defineStore('server', {
  state: (): State => ({
    servers: {},
  }),
  actions: {
    updateAll(): Promise<void> {
      return new Promise<void>((resolve, reject) => {
        fetcher('/api/servers', 'GET').then((response) => {
          if (response.status === 200) {
            response
              .json()
              .then((result) => {
                for (const server of result) {
                  this.servers[server.id] = server;
                }
                const newIds = result.map((s: Server) => s.id);
                const serverToDelete = Object.values(this.servers)
                  .map((s) => s.id)
                  .filter((item) => !newIds.includes(item));
                for (const serverId of serverToDelete) {
                  delete this.servers[serverId];
                }
                resolve();
              })
              .catch(() => reject);
          } else {
            reject();
          }
        });
      });
    },
    update(serverId: number) {
      fetcher('/api/servers/' + serverId, 'GET').then((response) => {
        if (response.status === 200) {
          response.json().then((server) => {
            this.servers[server.id] = server;
          });
        }
      });
    },
  },
  getters: {
    sortedListByName: (state) =>
      Object.values(state.servers).sort((a, b) => a.name.localeCompare(b.name)),
    sortedListByDate: (state) =>
      Object.values(state.servers).sort((a, b) => {
        let dateA = new Date(a.lastCheck || '2020-01-01 00:00:00').getTime();
        if (a.lastUpdate !== undefined) {
          const lastUpdateA = new Date(a.lastUpdate).getTime();
          if (lastUpdateA > dateA) {
            dateA = lastUpdateA;
          }
        }
        let dateB = new Date(b.lastCheck || '2020-01-01 00:00:00').getTime();
        if (b.lastUpdate !== undefined) {
          const lastUpdateB = new Date(b.lastUpdate).getTime();
          if (lastUpdateB > dateA) {
            dateB = lastUpdateB;
          }
        }
        return dateA - dateB;
      }),
  },
});
