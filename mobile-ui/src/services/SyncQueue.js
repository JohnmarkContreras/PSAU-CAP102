// src/services/syncQueue.js
import api from './api';
import { getOfflineGeotags, clearOfflineGeotags } from '../storage/localStore';

export async function syncGeotags() {
const queue = await getOfflineGeotags();
if (!queue.length) return;

try {
    for (const geotag of queue) {
    await api.post('/geotags', { ...geotag, source: 'mobile-react' });
    }
    await clearOfflineGeotags();
    console.log('Synced offline geotags');
} catch (err) {
    console.error('Sync failed', err);
}
}
