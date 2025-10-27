// src/pages/CameraCapture.tsx
import React, { useState } from 'react';
import {
IonPage,
IonHeader,
IonToolbar,
IonTitle,
IonContent,
IonButton,
IonSpinner,
IonItem,
IonLabel,
IonInput,
IonSelect,
IonSelectOption,
IonNote,
IonList,
IonToast,
} from '@ionic/react';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { v4 as uuidv4 } from 'uuid';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { Geolocation } from '@capacitor/geolocation';
import { captureAndQueue } from '@/utils/sync';
import type { InputChangeEventDetail } from '@ionic/core';
type CodeType = 'SWEET' | 'SOUR' | 'SEMI_SWEET';

interface FormValues {
codeType: CodeType | '';
codeNumber: string;
dbh: string;
height: string;
age?: string;
canopyDiameter?: string;
}

const initialForm: FormValues = {
codeType: '',
codeNumber: '',
dbh: '',
height: '',
age: '',
canopyDiameter: '',
};


const CameraCapture: React.FC = () => {
const [loading, setLoading] = useState(false);
const [form, setForm] = useState<FormValues>(initialForm);
const [errors, setErrors] = useState<Partial<Record<keyof FormValues, string>>>({});
const [toast, setToast] = useState<{ show: boolean; message?: string }>({ show: false });

function setField<K extends keyof FormValues>(key: K, value: FormValues[K]) {
    setForm(prev => ({ ...prev, [key]: value }));
    setErrors(prev => ({ ...prev, [key]: undefined }));
}

function validate(): boolean {
    const e: Partial<Record<keyof FormValues, string>> = {};
    if (!form.codeType) e.codeType = 'Select a code type';
    if (!form.codeNumber || form.codeNumber.trim() === '') e.codeNumber = 'Enter a code number';
    if (!form.dbh || Number(form.dbh) <= 0) e.dbh = 'Enter DBH greater than 0';
    if (!form.height || Number(form.height) <= 0) e.height = 'Enter height greater than 0';
    // optional fields: validate if present and numeric
    if (form.age && Number(form.age) < 0) e.age = 'Age must be positive';
    if (form.canopyDiameter && Number(form.canopyDiameter) < 0) e.canopyDiameter = 'Canopy diameter must be positive';
    setErrors(e);
    return Object.keys(e).length === 0;
}

// inside src/pages/CameraCapture.tsx (above takePhotoAndQueue)
async function takePhotoFallback(): Promise<string> {
  // Try Capacitor Camera first
try {
    const photo = await Camera.getPhoto({
    resultType: CameraResultType.Base64,
    source: CameraSource.Camera,
    quality: 80,
    allowEditing: false,
    });
    return String(photo.base64String ?? '').replace(/^data:image\/[a-z]+;base64,/, '');
} catch (err) {
    console.warn('Camera.getPhoto failed, falling back to getUserMedia:', err);
    // Fallback to getUserMedia
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    throw new Error('Camera not available in this browser');
    }

    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    const video = document.createElement('video');
    video.autoplay = true;
    video.playsInline = true;
    video.srcObject = stream;

    // Wait for video metadata to be ready
    await new Promise((res, rej) => {
    const onLoaded = () => {
        video.removeEventListener('loadedmetadata', onLoaded);
        res(null);
    };
    const onError = (e: any) => {
        video.removeEventListener('error', onError);
        rej(e);
    };
    video.addEventListener('loadedmetadata', onLoaded);
    video.addEventListener('error', onError);
    });

    // Draw one frame
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 1280;
    canvas.height = video.videoHeight || 720;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
    stream.getTracks().forEach(t => t.stop());
    throw new Error('Canvas not available');
    }
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // stop camera
    stream.getTracks().forEach(t => t.stop());

    const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
    return dataUrl.replace(/^data:image\/[a-z]+;base64,/, '');
}
}

async function takePhotoAndQueue() {
if (!validate()) {
    setToast({ show: true, message: 'Fix validation errors first' });
    return;
}

setLoading(true);
try {
    const rawBase64 = await takePhotoFallback();
    let latitude = 0;
    let longitude = 0;
    const photo = await Camera.getPhoto({
    resultType: CameraResultType.Base64,
    source: CameraSource.Camera,
    quality: 80,
    allowEditing: false,
    });

    try {
    const pos = await Geolocation.getCurrentPosition();
    latitude = pos.coords.latitude;
    longitude = pos.coords.longitude;
    } catch (geoErr) {
    console.warn('Geolocation error, proceeding without coords', geoErr);
    }

    const metadata = {
    code: `${form.codeType}-${form.codeNumber}`,
    codeType: form.codeType,
    codeNumber: form.codeNumber,
    dbh: Number(form.dbh),
    height: Number(form.height),
    age: form.age ? Number(form.age) : undefined,
    canopyDiameter: form.canopyDiameter ? Number(form.canopyDiameter) : undefined,
    latitude,
    longitude,
    capturedAt: new Date().toISOString(),
    };

    await captureAndQueue(rawBase64, metadata);

    setToast({ show: true, message: 'Capture queued successfully' });
    setForm(initialForm);
} catch (err) {
    console.error('capture error', err);
    setToast({ show: true, message: 'Capture failed. See console for details.' });
} finally {
    setLoading(false);
}
}

return (
    <IonPage>
    <IonHeader>
        <IonToolbar>
        <IonTitle style={{'--color': '#0ba84cff' } as React.CSSProperties}>Insert Tamarind Record</IonTitle>
        </IonToolbar>
    </IonHeader>

    <IonContent className="ion-padding">
        <IonList>
        <IonItem>
            <IonLabel position="stacked">Tree type</IonLabel>
            <IonSelect
            value={form.codeType}
            placeholder="Select type"
            onIonChange={(e) => setField('codeType', e.detail.value as CodeType)}
            >
            <IonSelectOption value="SWEET">SWEET</IonSelectOption>
            <IonSelectOption value="SOUR">SOUR</IonSelectOption>
            <IonSelectOption value="SEMI_SWEET">SEMI_SWEET</IonSelectOption>
            </IonSelect>
            {errors.codeType && <IonNote color="danger">{errors.codeType}</IonNote>}
        </IonItem>

        <IonItem>
            <IonLabel position="stacked">Code Number (example: 101)</IonLabel>
            <IonInput
                value={form.codeNumber}
                placeholder="101"
                inputMode="numeric"
                onIonInput={(e: CustomEvent<InputChangeEventDetail>) =>
                    setField('codeNumber', (e.detail.value ?? '') as string)
                }
                />
            {errors.codeNumber && <IonNote color="danger">{errors.codeNumber}</IonNote>}
        </IonItem>

        <IonItem>
            <IonLabel position="stacked">DBH (cm)</IonLabel>
            <IonInput
                value={form.dbh}
                inputMode="decimal"
                onIonInput={(e: CustomEvent<InputChangeEventDetail>) =>
                    setField('dbh', (e.detail.value ?? '') as string)
                }
                />
            {errors.dbh && <IonNote color="danger">{errors.dbh}</IonNote>}
        </IonItem>

        <IonItem>
            <IonLabel position="stacked">Height (m)</IonLabel>
            <IonInput
                value={form.height}
                inputMode="decimal"
                onIonInput={(e: CustomEvent<InputChangeEventDetail>) =>
                    setField('height', (e.detail.value ?? '') as string)
                }
                />
            {errors.height && <IonNote color="danger">{errors.height}</IonNote>}
        </IonItem>

        <IonItem>
            <IonLabel position="stacked">Age (years, optional)</IonLabel>
                <IonInput
                value={form.age}
                inputMode="numeric"
                onIonInput={(e: CustomEvent<InputChangeEventDetail>) =>
                    setField('age', (e.detail.value ?? '') as string)
                }
                />
            {errors.age && <IonNote color="danger">{errors.age}</IonNote>}
        </IonItem>

        <IonItem>
            <IonLabel position="stacked">Canopy Diameter (m, optional)</IonLabel>
                <IonInput
                value={form.canopyDiameter}
                inputMode="decimal"
                onIonInput={(e: CustomEvent<InputChangeEventDetail>) =>
                    setField('canopyDiameter', (e.detail.value ?? '') as string)
                }
                />
            {errors.canopyDiameter && <IonNote color="danger">{errors.canopyDiameter}</IonNote>}
        </IonItem>
        </IonList>

        <IonButton expand="block" onClick={takePhotoAndQueue} disabled={loading}>
        {loading ? <IonSpinner name="crescent" /> : 'Take Photo & Queue'}
        </IonButton>
        
        <IonToast
        isOpen={toast.show}
        message={toast.message}
        duration={2000}
        onDidDismiss={() => setToast({ show: false })}
        />
    </IonContent>
    </IonPage>
);
};

export default CameraCapture;