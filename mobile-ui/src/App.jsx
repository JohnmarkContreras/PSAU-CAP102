import React from 'react';
import GeotagForm from './components/GeotagForm';
import PendingList from './components/PendingList';
import SyncStatus from './components/SyncStatus';
import "./index.css";
export default function App() {
  return (
    <div className="p-4 space-y-6 bg-gray-50 min-h-screen">
      <h1 className="text-2xl font-bold text-center text-green-700 mb-4">
        🌳 Tamarind App
      </h1>

      <GeotagForm />
      <PendingList />
      <SyncStatus />
    </div>
  );
}