import { useEffect, useState } from 'react';

export default function SyncStatus() {
const [summary, setSummary] = useState({ queued: 0, synced: 0, failed: 0 });

useEffect(() => {
    const stored = JSON.parse(localStorage.getItem('pendingGeotags') || '[]');
    const counts = {
    queued: stored.filter(i => i.sync_status === 'queued').length,
    synced: stored.filter(i => i.sync_status === 'synced').length,
    failed: stored.filter(i => i.sync_status === 'failed').length,
    };
    setSummary(counts);
}, []);

return (
    <div className="p-4 space-y-4">
    <h2 className="text-lg font-bold">📊 Sync Status</h2>
    <div className="grid grid-cols-3 gap-4 text-center">
        <div className="bg-yellow-100 p-2 rounded">
        <p className="text-sm">Queued</p>
        <p className="text-xl font-bold text-yellow-600">{summary.queued}</p>
        </div>
        <div className="bg-green-100 p-2 rounded">
        <p className="text-sm">Synced</p>
        <p className="text-xl font-bold text-green-600">{summary.synced}</p>
        </div>
        <div className="bg-red-100 p-2 rounded">
        <p className="text-sm">Failed</p>
        <p className="text-xl font-bold text-red-600">{summary.failed}</p>
        </div>
    </div>
    </div>
);
}
