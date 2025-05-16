export interface Server {
  id: number;
  name: string;
  ip: string;
  type: ServerType;
  lastUpdate?: string;
  lastCheck?: string;
  documentation?: string;
  apps: App[];
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
  updateType: AppUpdateType;
}

export interface AppUpdateType {
  id: number;
  name: string;
}
export interface ServerFormData {
  id?: number;
  name: string;
  ip: string;
  type?: number;
  lastUpdate?: string;
  lastCheck?: string;
  apps: App[];
  documentation?: string;
}

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
