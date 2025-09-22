// src/storage/localStore.js
import { Preferences } from '@capacitor/preferences';

export async function saveGeotagOffline(geotag) {
const existing = await Preferences.get({ key: 'offlineGeotags' });
const queue = existing.value ? JSON.parse(existing.value) : [];
queue.push(geotag);
await Preferences.set({ key: 'offlineGeotags', value: JSON.stringify(queue) });
}

export async function getOfflineGeotags() {
const { value } = await Preferences.get({ key: 'offlineGeotags' });
return value ? JSON.parse(value) : [];
}

export async function clearOfflineGeotags() {
await Preferences.remove({ key: 'offlineGeotags' });
}
