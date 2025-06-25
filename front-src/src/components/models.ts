export interface ServerFormData {
  id?: number;
  name: string;
  ip: string;
  type?: number;
  documentation?: string;
  disabled: boolean;
  apps: App[];
}

export interface Server extends Omit<ServerFormData, 'type'> {
  id: number;
  type: ServerType;
  lastUpdate?: string;
  lastCheck?: string;
}

export interface ServerType {
  id: number;
  label: string;
}

export interface ServerLogs {
  id: number;
  date: string;
  message: string;
  username: string;
}

export interface User {
  id: number;
  login: string;
}

export interface App {
  id: number;
  name: string;
  currentVersion: string;
  lastUpdate: string;
  latestVersion: string;
  newVersion: string;
  documentation: string;
  updateResource: string;
  extraUpdateResource: string;
  disabled: boolean;
  updateType: AppUpdateType;
}

export interface AppUpdateType {
  id: number;
  name: string;
}
// ServerFormData est maintenant défini plus haut

export interface Log {
  id: number;
  date: string;
  message: string;
  username: string;
  server: {
    id: number;
    name: string;
  };
}
