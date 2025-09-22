import { useState } from 'react';
import GeotagForm from './components/GeotagForm';
import PendingList from './components/PendingList';
import SyncStatus from './components/SyncStatus';

export default function App() {
  const [tab, setTab] = useState('form');

  const renderTab = () => {
    switch (tab) {
      case 'form': return <GeotagForm />;
      case 'pending': return <PendingList />;
      case 'status': return <SyncStatus />;
      default: return <GeotagForm />;
    }
  };

  return (
    <div className="flex flex-col h-screen">
      <div className="flex-grow overflow-y-auto">
        {renderTab()}
      </div>

      <nav className="flex justify-around bg-gray-100 border-t p-2">
        <button onClick={() => setTab('form')} className={`text-sm ${tab === 'form' ? 'font-bold text-blue-600' : ''}`}>🌱 Form</button>
        <button onClick={() => setTab('pending')} className={`text-sm ${tab === 'pending' ? 'font-bold text-yellow-600' : ''}`}>📋 Pending</button>
        <button onClick={() => setTab('status')} className={`text-sm ${tab === 'status' ? 'font-bold text-green-600' : ''}`}>📊 Status</button>
      </nav>
    </div>
  );
}
