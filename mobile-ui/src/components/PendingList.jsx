import { useEffect, useState } from "react";

export default function PendingList() {
const [trees, setTrees] = useState([]);

const loadTrees = () => {
    const stored = JSON.parse(localStorage.getItem("pendingGeotags") || "[]");
    setTrees(stored);
};

useEffect(() => {
    loadTrees();
    const handler = () => loadTrees();
    window.addEventListener("pendingGeotagsUpdated", handler);
    return () => window.removeEventListener("pendingGeotagsUpdated", handler);
}, []);

const removeTree = (id) => {
    const stored = JSON.parse(localStorage.getItem("pendingGeotags") || "[]");
    const filtered = stored.filter((t) => t.id !== id);
    localStorage.setItem("pendingGeotags", JSON.stringify(filtered));
    setTrees(filtered);
    window.dispatchEvent(new Event("pendingGeotagsUpdated"));
};

const syncTree = async (tree) => {
    try {
    await axios.post("http://127.0.0.1:8000/api/mobile-geotags", tree);
    tree.sync_status = "synced";
    localStorage.setItem("pendingGeotags", JSON.stringify(trees));
    window.dispatchEvent(new Event("pendingGeotagsUpdated"));
    alert(`✅ Synced: ${tree.code}`);
    } catch {
    tree.sync_status = "failed";
    alert(`❌ Failed: ${tree.code}`);
    }
};

return (
    <div className="p-4">
    <h2 className="font-bold mb-2">📋 Pending Trees</h2>
    {trees.length === 0 ? (
        <p>No pending trees 🌱</p>
    ) : (
        <ul className="space-y-2">
        {trees.map((t) => (
            <li key={t.id} className="p-2 border rounded flex items-center gap-3">
            {t.image && <img src={`data:image/jpeg;base64,${t.image}`} alt="t" className="w-12 h-12 object-cover rounded" />}
            <div className="flex-1">
                <div className="text-sm">Code: <strong>{t.code}</strong></div>
                <div className="text-xs text-gray-500">Lat: {t.latitude} | Lng: {t.longitude}</div>
                <div className="text-xs text-red-500">Status: {t.sync_status || "queued"}</div>
            </div>
            <button onClick={() => syncTree(t)} className="px-2 py-1 bg-green-500 text-white rounded text-xs">🔃 Sync</button>
            <button onClick={() => removeTree(t.id)} className="px-2 py-1 bg-red-500 text-white rounded text-xs">❌ Remove</button>
            </li>
        ))}
        </ul>
    )}
    <button className="mt-3 px-3 py-2 bg-blue-600 text-white rounded" onClick={loadTrees}>🔄 Refresh</button>
    </div>
);
}
