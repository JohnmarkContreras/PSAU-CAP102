import { useEffect, useState } from 'react';
import axios from 'axios';

export default function PendingList() {
const [pending, setPending] = useState([]);

useEffect(() => {
    const stored = localStorage.getItem('pendingGeotags');
    if (stored) setPending(JSON.parse(stored));
}, []);

const syncItem = async (item, index) => {
    try {
    await axios.post('https://your-api-url/api/mobile-geotags', item);
    const updated = [...pending];
    updated[index].sync_status = 'synced';
    updated[index].synced_at = new Date().toISOString();
    setPending(updated);
    localStorage.setItem('pendingGeotags', JSON.stringify(updated));
    } catch (err) {
    const updated = [...pending];
    updated[index].sync_status = 'failed';
    setPending(updated);
    localStorage.setItem('pendingGeotags', JSON.stringify(updated));
    }
};

return (
    <div className="p-4 space-y-4">
    <h2 className="text-lg font-bold">Pending Geotags</h2>
    {pending.length === 0 && <p>No pending items</p>}
    {pending.map((item, i) => (
        <div key={i} className="border p-2 rounded">
        <p><strong>Code:</strong> {item.code}</p>
        <p><strong>Status:</strong> {item.sync_status || 'queued'}</p>
        <button className="btn bg-blue-500 text-white mt-2" onClick={() => syncItem(item, i)}>🔄 Sync</button>
        </div>
    ))}
    </div>
);
}
