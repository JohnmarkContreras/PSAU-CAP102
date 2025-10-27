// src/services/auth.ts
import { Storage } from '@capacitor/storage';

export async function setToken(token: string) {
    await Storage.set({ key: 'api_token', value: token });
}

export async function getToken(): Promise<string | null> {
    const { value } = await Storage.get({ key: 'api_token' });
    return value || null;
}

export async function clearToken(): Promise<void> {
    await Storage.remove({ key: 'api_token' });
}