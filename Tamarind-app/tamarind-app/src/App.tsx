// src/main.tsx — very first lines
import { defineCustomElements as definePWAElements } from '@ionic/pwa-elements/loader';
import { defineCustomElements as defineJeepSqlite } from 'jeep-sqlite/loader';

if (typeof window !== 'undefined') {
  try { definePWAElements(window); } catch (e) { console.warn('pwa-elements loader failed', e); }
  try { defineJeepSqlite(window); } catch (e) { console.warn('jeep-sqlite loader failed', e); }
}

import React, { useEffect } from 'react';
import { Switch, Route, Redirect } from 'react-router-dom';
import { IonApp, IonRouterOutlet, setupIonicReact } from '@ionic/react';
import { IonReactRouter } from '@ionic/react-router';
import Home from './pages/Home';
import CameraCapture from './components/Camera/CameraCapture';

/* keep your existing CSS imports here */
import '@ionic/react/css/core.css';
import '@ionic/react/css/normalize.css';
import '@ionic/react/css/structure.css';
import '@ionic/react/css/typography.css';
import '@ionic/react/css/padding.css';
import '@ionic/react/css/float-elements.css';
import '@ionic/react/css/text-alignment.css';
import '@ionic/react/css/text-transformation.css';
import '@ionic/react/css/flex-utils.css';
import '@ionic/react/css/display.css';
import '@ionic/react/css/palettes/dark.system.css';
import './theme/variables.css';

// src/main.tsx or src/App.tsx (where your app initializes)
import { App as CapacitorApp } from '@capacitor/app';
import { processPendingQueue } from './utils/queueProcessor';


// run once at startup
void processPendingQueue({ deleteOnSuccess: false });

// on resume (app comes to foreground)
CapacitorApp.addListener('appStateChange', (state) => {
  if (state.isActive) {
    void processPendingQueue({ deleteOnSuccess: false });
  }
});

// optional: run when network becomes available (Capacitor Network plugin)
import { Network } from '@capacitor/network';
Network.addListener('networkStatusChange', (status) => {
  if (status.connected) void processPendingQueue({ deleteOnSuccess: false });
});


setupIonicReact();

const App: React.FC = () => {
  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        // If running on web, wait for jeep-sqlite to be defined before opening DB
        if (typeof window !== 'undefined' && customElements) {
          if (!customElements.get('jeep-sqlite')) {
            await customElements.whenDefined('jeep-sqlite');
          }
        }
        if (!mounted) return;
      } catch (e) {
        // keep console error for diagnostics only
        console.warn('DB init failed', e);
      }
    })();
    return () => {
      mounted = false;
    };
  }, []);

  return (
    <IonApp>
      <IonReactRouter>
        <IonRouterOutlet>
          <Switch>
            <Route exact path="/capture" component={CameraCapture} />
            <Route exact path="/home" component={Home} />
            <Route exact path="/" render={() => <Redirect to="/capture" />} />
          </Switch>
        </IonRouterOutlet>
      </IonReactRouter>
    </IonApp>
  );
};

export default App;