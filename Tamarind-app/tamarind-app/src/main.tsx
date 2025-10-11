// src/main.tsx (or index.tsx) — run before React mount
import { defineCustomElements as defineJeepSqlite } from 'jeep-sqlite/loader';
import * as SQLitePlugin from '@capacitor-community/sqlite';

async function initJeepSqliteWeb(timeoutMs = 7000) {
  try { defineJeepSqlite(window); } catch {}
  if (!document.querySelector('jeep-sqlite')) {
    document.body.appendChild(document.createElement('jeep-sqlite'));
  }
  await Promise.race([
    customElements.whenDefined('jeep-sqlite'),
    new Promise((_, rej) => setTimeout(() => rej(new Error('jeep-sqlite definition timeout')), timeoutMs)),
  ]);
  const cap = (SQLitePlugin as any).CapacitorSQLite;
  if (!cap) throw new Error('CapacitorSQLite plugin not available on web');
  if (typeof cap.initWebStore === 'function') await cap.initWebStore();
}

(async () => {
  try {
    await initJeepSqliteWeb();
  } catch (err) {
    console.warn('jeep-sqlite init failed', err);
  }
  // now mount React app
  // createRoot(document.getElementById('root')!).render(<App />);
})();

import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
