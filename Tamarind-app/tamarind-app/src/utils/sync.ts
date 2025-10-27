    // src/utils/sync.ts
    import { Filesystem, Directory } from '@capacitor/filesystem';
    import axios, { AxiosError } from 'axios';
    import { v4 as uuidv4 } from 'uuid';
    import { openDb, insert, findBy, update } from '../services/localDb';
    import { getToken } from '../services/auth';

    const API_BASE = 'http://10.0.2.2:8000/api';
    const MAX_RETRIES = 3;

    export async function captureAndQueue(
    base64Image: string,
    payload: { code: string; latitude: number; longitude: number; taken_at?: string }
    ) {
    await openDb();
    const client_uuid = uuidv4();
    const filename = `${client_uuid}.jpg`;

    await Filesystem.writeFile({ path: filename, data: base64Image, directory: Directory.Data });

    await insert('pending_geotag_trees', {
        client_uuid,
        code: payload.code,
        latitude: payload.latitude,
        longitude: payload.longitude,
        image_local_path: filename,
        sync_status: 'pending',
        created_at: new Date().toISOString(),
    });

    void uploadWithRetry(client_uuid);
    }

    async function uploadOnce(clientUuid: string) {
    const rows = await findBy('pending_geotag_trees', { client_uuid: clientUuid });
    for (const row of rows) {
        const token = await getToken();
        const file = await Filesystem.readFile({ path: row.image_local_path, directory: Directory.Data });

        const base64: string = String((file as { data?: unknown }).data ?? '');
        const blob = base64ToBlob(base64, 'image/jpeg');

        const form = new FormData();
        form.append('client_uuid', clientUuid);
        form.append('code', row.code);
        form.append('latitude', String(row.latitude));
        form.append('longitude', String(row.longitude));
        form.append('file', blob, `${clientUuid}.jpg`);

        const res = await axios.post(`${API_BASE}/pending-geotag-trees`, form, {
        headers: {
            Authorization: token ? `Bearer ${token}` : '',
            'Content-Type': 'multipart/form-data',
        },
        timeout: 30000,
        });
        return res.data;
    }
    }

    export async function uploadWithRetry(clientUuid: string) {
    let attempt = 0;
    while (attempt < MAX_RETRIES) {
        attempt++;
        try {
        const res = await uploadOnce(clientUuid);
        await update(
            'pending_geotag_trees',
            { server_id: res.id, sync_status: 'synced_pending_processing', server_processed_at: res.processed_at || null },
            { client_uuid: clientUuid }
        );
        return;
        } catch (err: unknown) {
        const axiosErr = err as AxiosError | undefined;
        const status = axiosErr?.response?.status;
        const message =
            (axiosErr && typeof axiosErr.message === 'string' && axiosErr.message) ||
            (axiosErr?.response && (axiosErr.response as any).data?.message) ||
            String(err);

        if (typeof status === 'number' && status >= 400 && status < 500) {
            await update(
            'pending_geotag_trees',
            { sync_status: 'failed', rejection_reason: message },
            { client_uuid: clientUuid }
            );
            return;
        }

        if (attempt >= MAX_RETRIES) {
            await update(
            'pending_geotag_trees',
            { sync_status: 'failed', rejection_reason: message },
            { client_uuid: clientUuid }
            );
            return;
        }

        await sleep(1000 * Math.pow(2, attempt));
        }
    }
    }

    function base64ToBlob(b64Data: string, contentType = '', sliceSize = 512): Blob {
    const byteCharacters = atob(b64Data);
    const byteArrays: Uint8Array[] = [];

    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        const slice = byteCharacters.slice(offset, offset + sliceSize);
        const byteNumbers = new Array<number>(slice.length);
        for (let i = 0; i < slice.length; i++) byteNumbers[i] = slice.charCodeAt(i);
        byteArrays.push(new Uint8Array(byteNumbers));
    }

    return new Blob(byteArrays as unknown as BlobPart[], { type: contentType });
    }

    function sleep(ms: number) {
    return new Promise((res) => setTimeout(res, ms));
}