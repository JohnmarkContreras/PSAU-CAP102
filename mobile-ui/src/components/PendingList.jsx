import { useState, useEffect } from "react";
import { Preferences } from "@capacitor/preferences";

export default function PendingList() {
const [trees, setTrees] = useState([]);

const loadTrees = async () => {
    const { value } = await Preferences.get({ key: "pendingGeotags" });
    setTrees(value ? JSON.parse(value) : []);
};

useEffect(() => {
    loadTrees();
    const handler = () => loadTrees();
    window.addEventListener("pendingGeotagsUpdated", handler);
    return () => window.removeEventListener("pendingGeotagsUpdated", handler);
}, []);

const clearOne = async (id) => {
    const { value } = await Preferences.get({ key: "pendingGeotags" });
    const arr = value ? JSON.parse(value) : [];
    const filtered = arr.filter((t) => t.id !== id);
    await Preferences.set({ key: "pendingGeotags", value: JSON.stringify(filtered) });
    window.dispatchEvent(new Event("pendingGeotagsUpdated"));
};

return (
    <div className="p-4">
    <h2 className="font-bold mb-2">📋 Pending Trees</h2>
    <button className="mb-3 px-3 py-2 bg-blue-600 text-white rounded" onClick={loadTrees}>Refresh</button>

    {trees.length === 0 ? (
        <p>No pending trees 🌱</p>
    ) : (
        <ul className="space-y-2">
        {trees.map((t) => (
            <li key={t.id} className="p-2 border rounded flex items-center gap-3">
            {t.image ? <img src={`data:image/jpeg;base64,${t.image}`} alt="t" className="w-12 h-12 object-cover rounded" /> : null}
            <div className="flex-1">
                <div className="text-sm">Code: <span className="font-semibold">{t.code}</span></div>
                <div className="text-xs text-gray-500">Lat: {t.latitude} | Lng: {t.longitude}</div>
                <div className="text-xs text-red-500">Status: {t.sync_status || "queued"}</div>
            </div>
            <button onClick={() => clearOne(t.id)} className="px-2 py-1 bg-red-500 text-white rounded text-xs">Remove</button>
            </li>
        ))}
        </ul>
    )}
    </div>
);
}
