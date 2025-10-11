// src/utils/queueProcessor.ts
import { findBy, update, deleteBy } from '../services/localDb';
import { uploadWithRetry } from './sync'; 

export async function processPendingQueue(options?: { deleteOnSuccess?: boolean }) {
    const rows = await findBy('pending_geotag_trees', { sync_status: 'pending' }, ['client_uuid']);
    const seen = new Set<string>();
    for (const r of rows) {
        const clientUuid = String(r.client_uuid);
        if (!clientUuid || seen.has(clientUuid)) continue;
        seen.add(clientUuid);
        try {
        await uploadWithRetry(clientUuid);
        if (options?.deleteOnSuccess) {
            await deleteBy('pending_geotag_trees', { client_uuid: clientUuid });
        }
        } catch (err) {
        // uploadWithRetry already marks failures in the row; keep looping
        console.debug('processPendingQueue: upload failed for', clientUuid, err);
        }
    }
}