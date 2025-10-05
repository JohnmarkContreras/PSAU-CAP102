import { useState, useEffect } from "react";
import { Geolocation } from "@capacitor/geolocation";
import { Camera } from "@capacitor/camera";
import axios from "axios";

export default function GeotagForm() {
const [form, setForm] = useState({
    code: "",
    age: "",
    height: "",
    stem_diameter: "",
    canopy_diameter: "",
    latitude: "",
    longitude: "",
    image: "",
    id: null, // unique id for pending items
});

useEffect(() => {
    captureLocation();
    syncPendingGeotags();
}, []);

const handleChange = (e) => {
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }));
};

const captureLocation = async () => {
    try {
    const pos = await Geolocation.getCurrentPosition();
    setForm(prev => ({
        ...prev,
        latitude: pos.coords.latitude.toFixed(6),
        longitude: pos.coords.longitude.toFixed(6),
    }));
    } catch {
    alert("⚠️ Unable to fetch GPS. Please enable location.");
    }
};

const captureImage = async () => {
    try {
    const photo = await Camera.getPhoto({
        quality: 70,
        allowEditing: false,
        resultType: "base64",
        source: "camera",
    });
    setForm(prev => ({ ...prev, image: photo.base64String }));
    } catch {
    alert("⚠️ Camera not available.");
    }
};

const handleSubmit = async () => {
    if (!form.latitude || !form.longitude) return alert("⚠️ Location missing.");
    if (!form.image) return alert("⚠️ Please capture a tree photo.");

    try {
    await axios.post("http://127.0.0.1:8000/api/mobile-geotags", form);
    alert("✅ Geotag submitted online!");
    setForm(prev => ({ ...prev, code: "", age: "", height: "", stem_diameter: "", canopy_diameter: "", image: "" }));
    } catch {
    // save offline
    const id = Date.now(); // simple unique id
    const stored = JSON.parse(localStorage.getItem("pendingGeotags") || "[]");
    stored.push({ ...form, id, sync_status: "queued" });
    localStorage.setItem("pendingGeotags", JSON.stringify(stored));
    window.dispatchEvent(new Event("pendingGeotagsUpdated"));
    alert("📦 Saved offline. Will sync later.");
    }
};

const syncPendingGeotags = async () => {
    const stored = JSON.parse(localStorage.getItem("pendingGeotags") || "[]");
    if (!stored.length) return;

    const remaining = [];
    for (let item of stored) {
    try {
        await axios.post("http://127.0.0.1:8000/api/mobile-geotags", item);
        item.sync_status = "synced";
    } catch {
        item.sync_status = "failed";
        remaining.push(item);
    }
    }
    localStorage.setItem("pendingGeotags", JSON.stringify(remaining.concat(stored.filter(i => !remaining.includes(i)))));
    window.dispatchEvent(new Event("pendingGeotagsUpdated"));
};

return (
    <div className="p-4 space-y-4">
    <h1 className="text-xl font-bold text-green-700">🌳 Submit Tree Geotag</h1>
    <input name="code" placeholder="Tree Code" value={form.code} onChange={handleChange} className="input" />
    <input name="age" placeholder="Age" value={form.age} onChange={handleChange} className="input" />
    <input name="height" placeholder="Height" value={form.height} onChange={handleChange} className="input" />
    <input name="stem_diameter" placeholder="Stem Diameter" value={form.stem_diameter} onChange={handleChange} className="input" />
    <input name="canopy_diameter" placeholder="Canopy Diameter" value={form.canopy_diameter} onChange={handleChange} className="input" />

    <div className="text-sm text-gray-700">📍 Current Location: <span className="font-mono">{form.latitude}, {form.longitude}</span></div>

    <div className="flex gap-2">
        <button className="btn bg-blue-500 text-white" onClick={captureLocation}>🔄 Update Location</button>
        <button className="btn bg-yellow-500 text-white" onClick={captureImage}>📸 Take Photo</button>
    </div>

    {form.image && <img src={`data:image/jpeg;base64,${form.image}`} alt="Preview" className="w-full h-auto mt-2 rounded shadow" />}

    <div className="flex gap-2">
        <button className="btn bg-green-600 text-white" onClick={handleSubmit}>✅ Submit</button>
        <button className="btn bg-gray-500 text-white" onClick={syncPendingGeotags}>🔃 Sync Now</button>
    </div>
    </div>
);
}
