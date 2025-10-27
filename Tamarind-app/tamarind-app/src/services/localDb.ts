// src/services/localDb.ts
import type { SQLiteDBConnection } from '@capacitor-community/sqlite';
import * as SQLitePlugin from '@capacitor-community/sqlite';
import { defineCustomElements as defineJeepSqlite } from 'jeep-sqlite/loader';

let conn: SQLiteDBConnection | undefined;
let opening: Promise<SQLiteDBConnection> | null = null;

async function ensureJeepSqliteReady(timeoutMs = 7000): Promise<void> {
try {
    defineJeepSqlite(window);
} catch {}
if (!document.querySelector('jeep-sqlite')) {
    document.body.appendChild(document.createElement('jeep-sqlite'));
}
if (customElements.get('jeep-sqlite')) return;
await Promise.race([
    customElements.whenDefined('jeep-sqlite'),
    new Promise((_, rej) =>
    setTimeout(() => rej(new Error('jeep-sqlite definition timeout')), timeoutMs)
    ),
]);
}

export async function openDb(): Promise<SQLiteDBConnection> {
if (conn) return conn;
if (opening) return opening;

opening = (async () => {
    if (typeof window !== 'undefined' && typeof customElements !== 'undefined') {
    await ensureJeepSqliteReady();
    }

    const sqlite = (SQLitePlugin as any).CapacitorSQLite;
    if (!sqlite) throw new Error('CapacitorSQLite plugin not available');

    const dbName = 'capstone';

    console.debug('[localDb] openDb dbName=', dbName);

    try {
    // try the usual positional signature first (native and many web builds)
    conn = await sqlite.createConnection(dbName, false, 'no-encryption', 1, false);
    } catch (err: any) {
    const msg = String(err?.message ?? err).toLowerCase();
    console.warn('[localDb] createConnection(positional) failed:', msg);

    // If plugin complains about missing db name, try object-style call the web adapter expects
    try {
        if (typeof sqlite.createConnection === 'function') {
        // some web builds expect an object
        conn = await sqlite.createConnection({ database: dbName, encrypted: false, mode: 'no-encryption', version: 1 });
        }
    } catch (err2: any) {
        const msg2 = String(err2?.message ?? err2).toLowerCase();
        console.warn('[localDb] createConnection(object) failed:', msg2);

        // If error mentions already exists, retrieve it; otherwise rethrow the last error
        if (msg.includes('already exists') || msg.includes('already open') || msg2.includes('already exists') || msg2.includes('already open')) {
        conn = await sqlite.retrieveConnection(dbName, false);
        } else {
        throw err2 ?? err;
        }
    }
    }
    if (!conn) throw new Error('Database connection not initialized');

    try {
    // Try checking plugin registry first (some plugin builds support isConnection)
    let isConn = false;
    try {
        const isResp = await sqlite.isConnection?.(dbName, false);
        isConn = Boolean(isResp?.result);
    } catch {
        isConn = false;
    }

    if (isConn) {
        conn = await sqlite.retrieveConnection(dbName, false);
    } else {
        try {
        conn = await sqlite.createConnection(dbName, false, 'no-encryption', 1, false);
        } catch (err: any) {
        const msg = String(err?.message || err).toLowerCase();
        if (msg.includes('already exists') || msg.includes('already open') || msg.includes('connection')) {
            conn = await sqlite.retrieveConnection(dbName, false);
        } else {
            throw err;
        }
        }
    }

    if (!conn) throw new Error('Database connection not initialized');

    if (typeof (conn as any).open === 'function') {
        await (conn as any).open();
    }

    await conn.execute(`
        CREATE TABLE IF NOT EXISTS pending_geotag_trees (
        id INTEGER PRIMARY KEY NOT NULL,
        client_uuid TEXT UNIQUE,
        server_id INTEGER,
        code TEXT,
        latitude REAL,
        longitude REAL,
        image_local_path TEXT,
        sync_status TEXT,
        thumb_path TEXT,
        rejection_reason TEXT,
        created_at TEXT,
        updated_at TEXT,
        server_processed_at TEXT
        );
    `);

    return conn;
    } finally {
    opening = null;
    }
})();

return opening;
}

// ----------------------
// CRUD HELPERS
// ----------------------

export interface RowValues {
[column: string]: string | number | null | undefined;
}

export type QueryRow = Record<string, any>;

export async function insert(table: string, row: RowValues): Promise<number> {
const db = await openDb();
const columns = Object.keys(row);
if (columns.length === 0) throw new Error('insert: no columns provided');

const placeholders = columns.map(() => '?').join(', ');
const sql = `INSERT INTO ${table} (${columns.join(', ')}) VALUES (${placeholders})`;
const values = columns.map(k => (row[k] === undefined ? null : row[k]));

const result = await (db as any).execute(sql, values);
const lastId =
    (result as any)?.lastId ??
    (result as any)?.changes?.lastId ??
    (result as any)?.insertId ??
    (result as any)?.result?.insertId ??
    0;

return Number(lastId);
}

export async function findBy(
table: string,
where: Record<string, any> = {},
columns: string[] = ['*']
): Promise<QueryRow[]> {
const db = await openDb();
const cols = columns.length ? columns.join(', ') : '*';
const whereKeys = Object.keys(where);
const whereClause = whereKeys.length ? 'WHERE ' + whereKeys.map(k => `${k} = ?`).join(' AND ') : '';
const sql = `SELECT ${cols} FROM ${table} ${whereClause}`.trim();
const params = whereKeys.map(k => (where[k] === undefined ? null : where[k]));

let result: any;
if (typeof (db as any).query === 'function') {
    result = await (db as any).query(sql, params);
    return result?.values ?? result?.rows ?? [];
} else if (typeof (db as any).execute === 'function') {
    result = await (db as any).execute(sql, params);
    return result?.values ?? result?.rows ?? result?.result ?? [];
} else if (typeof (db as any).select === 'function') {
    return await (db as any).select(sql, params);
} else if (typeof (db as any).executeSql === 'function') {
    result = await (db as any).executeSql(sql, params);
    const rows: QueryRow[] = [];
    if (result?.rows) {
    for (let i = 0; i < result.rows.length; i++) rows.push(result.rows.item(i));
    }
    return rows;
}

return [];
}

export async function update(
table: string,
updates: Record<string, any>,
where: Record<string, any>
): Promise<number> {
const db = await openDb();
const updateKeys = Object.keys(updates);
if (updateKeys.length === 0) throw new Error('update: no columns to update');
const whereKeys = Object.keys(where);
if (whereKeys.length === 0) throw new Error('update: where clause required');

const setClause = updateKeys.map(k => `${k} = ?`).join(', ');
const whereClause = whereKeys.map(k => `${k} = ?`).join(' AND ');
const sql = `UPDATE ${table} SET ${setClause} WHERE ${whereClause}`;
const params = [
    ...updateKeys.map(k => (updates[k] === undefined ? null : updates[k])),
    ...whereKeys.map(k => (where[k] === undefined ? null : where[k])),
];

let result: any;
if (typeof (db as any).run === 'function') {
    result = await (db as any).run(sql, params);
} else if (typeof (db as any).execute === 'function') {
    result = await (db as any).execute(sql, params);
} else if (typeof (db as any).executeSql === 'function') {
    result = await (db as any).executeSql(sql, params);
} else {
    result = await (db as any).execute(sql, params);
}

const affected =
    (result as any)?.changes?.rowsAffected ??
    (result as any)?.rowsAffected ??
    (result as any)?.affectedRows ??
    (result as any)?.result?.rowsAffected ??
    0;

return Number(affected);
}

export async function deleteBy(table: string, where: Record<string, any>): Promise<number> {
const db = await openDb();
const whereKeys = Object.keys(where);
if (whereKeys.length === 0) throw new Error('deleteBy: where clause required');

const whereClause = whereKeys.map(k => `${k} = ?`).join(' AND ');
const sql = `DELETE FROM ${table} WHERE ${whereClause}`;
const params = whereKeys.map(k => (where[k] === undefined ? null : where[k]));

const result = await (db as any).execute(sql, params);
const affected =
    (result as any)?.changes?.rowsAffected ??
    (result as any)?.rowsAffected ??
    (result as any)?.affectedRows ??
    (result as any)?.result?.rowsAffected ??
    0;

return Number(affected);
}

export async function closeDb(): Promise<void> {
if (!conn) return;
const sqlite = (SQLitePlugin as any).CapacitorSQLite;
if (sqlite && typeof sqlite.closeConnection === 'function') {
    await sqlite.closeConnection('capstone', false).catch(() => {});
}
conn = undefined;                                                                                                       
}