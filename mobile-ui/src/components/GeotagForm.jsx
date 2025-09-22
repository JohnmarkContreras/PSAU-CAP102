import { useState } from 'react';
import { useEffect } from 'react';
import { Geolocation } from '@capacitor/geolocation';
import { Camera } from '@capacitor/camera';
import axios from 'axios';

export default function GeotagForm() {
const [form, setForm] = useState({
    code: '',
    age: '',
    height: '',
    stem_diameter: '',
    canopy_diameter: '',
    latitude: '',
    longitude: '',
    image: '',
});

// ✅ Auto-capture GPS on component mount
    useEffect(() => {
        const getLocation = async () => {
        const pos = await Geolocation.getCurrentPosition();
        setForm(f => ({
            ...f,
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
        }));
        };
        getLocation();
    }, []);

//if submission fails: This keeps your app resilient even without internet
const stored = JSON.parse(localStorage.getItem('pendingGeotags') || '[]');
stored.push({ ...form, sync_status: 'queued' });
localStorage.setItem('pendingGeotags', JSON.stringify(stored));


const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
};

const captureLocation = async () => {
    const pos = await Geolocation.getCurrentPosition();
    setForm({ ...form, latitude: pos.coords.latitude, longitude: pos.coords.longitude });
};

const captureImage = async () => {
    const photo = await Camera.getPhoto({
    quality: 70,
    allowEditing: false,
    resultType: 'base64',
    source: 'camera',
    });
    setForm({ ...form, image: photo.base64String });
};

const handleSubmit = async () => {
    try {
    await axios.post('https://your-api-url/api/mobile-geotags', form);
    alert('Geotag submitted!');
    } catch (err) {
    const stored = JSON.parse(localStorage.getItem('pendingGeotags') || '[]');
    stored.push({ ...form, sync_status: 'queued' });
    localStorage.setItem('pendingGeotags', JSON.stringify(stored));
    alert('Saved offline. Will sync later.');
    }
};

return (
    <div className="p-4 space-y-4">
    <h1 className="text-xl font-bold">Submit Tree Geotag</h1>

    <input name="code" placeholder="Tree Code" className="input" onChange={handleChange} />
    <input name="age" placeholder="Age" className="input" onChange={handleChange} />
    <input name="height" placeholder="Height" className="input" onChange={handleChange} />
    <input name="stem_diameter" placeholder="Stem Diameter" className="input" onChange={handleChange} />
    <input name="canopy_diameter" placeholder="Canopy Diameter" className="input" onChange={handleChange} />

    <button className="btn" onClick={captureLocation}>📍 Capture GPS</button>
    <button className="btn" onClick={captureImage}>📸 Take Photo</button>
    {form.image && (
    <img
        src={`data:image/jpeg;base64,${form.image}`}
        alt="Preview"
        className="w-full h-auto mt-2 rounded shadow"
    />
    )}

    <button className="btn bg-green-600 text-white" onClick={handleSubmit}>✅ Submit</button>
    </div>
);
}
