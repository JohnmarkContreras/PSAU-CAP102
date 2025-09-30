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
});

const [pending, setPending] = useState([]); // ✅ track offline data

// ✅ Capture GPS + load pending data
useEffect(() => {
    captureLocation();
    loadPending();
}, []);

const loadPending = () => {
    const stored = JSON.parse(localStorage.getItem("pendingGeotags") || "[]");
    setPending(stored);
};

const handleChange = (e) => {
    setForm((prev) => ({
    ...prev,
    [e.target.name]: e.target.value,
    }));
};

const captureLocation = async () => {
    try {
    const pos = await Geolocation.getCurrentPosition();
    setForm((prev) => ({
        ...prev,
        latitude: pos.coords.latitude.toFixed(6),
        longitude: pos.coords.longitude.toFixed(6),
    }));
    } catch (err) {
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
    setForm((prev) => ({
        ...prev,
        image: photo.base64String,
    }));
    } catch (err) {
    alert("⚠️ Camera not available.");
    }
};

const handleSubmit = async () => {
    if (!form.latitude || !form.longitude) {
    return alert("⚠️ Location missing. Please update GPS before submitting.");
    }
    if (!form.image) {
    return alert("⚠️ Please capture a tree photo.");
    }

    try {
    await axios.post("http://127.0.0.1:8000/api/mobile-geotags", form);
    alert("✅ Geotag submitted online!");
    setForm((prev) => ({
        code: "",
        age: "",
        height: "",
        stem_diameter: "",
        canopy_diameter: "",
        latitude: prev.latitude,
        longitude: prev.longitude,
        image: "",
    }));
    } catch (err) {
    // 📦 Save offline if request fails
    const stored = JSON.parse(localStorage.getItem("pendingGeotags") || "[]");
    stored.push({ ...form, sync_status: "queued" });
    localStorage.setItem("pendingGeotags", JSON.stringify(stored));
    setPending(stored); // ✅ update UI
    alert("📦 Saved offline. Will sync later.");
    }
};

const syncPendingGeotags = async () => {
    const stored = JSON.parse(localStorage.getItem("pendingGeotags") || "[]");
    if (!stored.length) return alert("No pending geotags to sync.");

    const remaining = [];
    for (let item of stored) {
    try {
        await axios.post("http://127.0.0.1:8000/api/mobile-geotags", item);
        console.log("✅ Synced:", item.code);
    } catch (err) {
        console.warn("❌ Sync failed for:", item.code);
        remaining.push(item);
    }
    }

    localStorage.setItem("pendingGeotags", JSON.stringify(remaining));
    setPending(remaining); // ✅ refresh list

    if (remaining.length === 0) {
    alert("✅ All pending geotags synced!");
    }
};

const cancelPending = (index) => {
    const updated = [...pending];
    updated.splice(index, 1);
    localStorage.setItem("pendingGeotags", JSON.stringify(updated));
    setPending(updated);
};

return (
    <div className="p-4 space-y-4">
    <h1 className="text-xl font-bold text-green-700">🌳 Submit Tree Geotag</h1>

    {/* Inputs */}
    <input
        name="code"
        placeholder="Tree Code"
        value={form.code}
        onChange={handleChange}
        autoCapitalize="none"
        autoCorrect="off"
        className="w-full border rounded px-3 py-2 text-sm focus:ring focus:ring-green-300"
    />
    <input
        name="age"
        placeholder="Age"
        value={form.age}
        onChange={handleChange}
        className="w-full border rounded px-3 py-2 text-sm focus:ring focus:ring-green-300"
    />
    <input
        name="height"
        placeholder="Height"
        value={form.height}
        onChange={handleChange}
        className="w-full border rounded px-3 py-2 text-sm focus:ring focus:ring-green-300"
    />
    <input
        name="stem_diameter"
        placeholder="Stem Diameter"
        value={form.stem_diameter}
        onChange={handleChange}
        className="w-full border rounded px-3 py-2 text-sm focus:ring focus:ring-green-300"
    />
    <input
        name="canopy_diameter"
        placeholder="Canopy Diameter"
        value={form.canopy_diameter}
        onChange={handleChange}
        className="w-full border rounded px-3 py-2 text-sm focus:ring focus:ring-green-300"
    />

    {/* GPS Display */}
    <div className="text-sm text-gray-700">
        📍 Current Location:{" "}
        <span className="font-mono">
        {form.latitude}, {form.longitude}
        </span>
    </div>

    {/* Buttons */}
    <div className="flex gap-2">
        <button
        className="flex-1 bg-blue-500 text-white rounded px-3 py-2 shadow"
        onClick={captureLocation}
        >
        🔄 Update Location
        </button>
        <button
        className="flex-1 bg-yellow-500 text-white rounded px-3 py-2 shadow"
        onClick={captureImage}
        >
        📸 Take Photo
        </button>
    </div>

    {/* Image Preview */}
    {form.image && (
        <img
        src={`data:image/jpeg;base64,${form.image}`}
        alt="Preview"
        className="w-full h-auto mt-2 rounded shadow"
        />
    )}

    {/* Submit + Sync */}
    <div className="flex gap-2">
        <button
        className="flex-1 bg-green-600 text-white rounded px-3 py-2 shadow"
        onClick={handleSubmit}
        >
        ✅ Submit
        </button>
        <button
        className="flex-1 bg-gray-500 text-white rounded px-3 py-2 shadow"
        onClick={syncPendingGeotags}
        >
        🔃 Sync Now
        </button>
    </div>

    {/* Pending List */}
    {pending.length > 0 && (
        <div className="mt-4 border-t pt-4">
        <h2 className="font-bold text-red-600">📦 Pending Submissions</h2>
        <ul className="space-y-2">
            {pending.map((item, idx) => (
            <li
                key={idx}
                className="flex justify-between items-center border rounded p-2 bg-yellow-50"
            >
                <div>
                <div className="font-mono">{item.code}</div>
                <div className="text-xs text-gray-600">
                    {item.latitude}, {item.longitude}
                </div>
                </div>
                <button
                className="bg-red-500 text-white px-2 py-1 rounded text-xs"
                onClick={() => cancelPending(idx)}
                >
                ❌ Cancel
                </button>
            </li>
            ))}
        </ul>
        </div>
    )}
    </div>
);
}
