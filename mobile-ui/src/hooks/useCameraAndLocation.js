// src/hooks/useCameraAndLocation.js
import { Camera } from '@capacitor/camera';
import { Geolocation } from '@capacitor/geolocation';

export async function captureTreeData() {
const photo = await Camera.getPhoto({ quality: 90, resultType: 'base64' });
const position = await Geolocation.getCurrentPosition();

return {
    image: photo.base64String,
    latitude: position.coords.latitude,
    longitude: position.coords.longitude,
};
}
