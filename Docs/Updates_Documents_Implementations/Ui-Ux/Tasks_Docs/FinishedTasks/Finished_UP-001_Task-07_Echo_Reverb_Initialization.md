# 🎯 TASK: UP-001 - Task 07: Laravel Echo + Reverb Initialization
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Initialize Laravel Echo with Reverb broadcaster in `bootstrap.js` so that WebSocket events can be received throughout the application.

## 2. Files to Create/Modify
- `resources/js/bootstrap.js`: Add Echo initialization with Reverb config

## 3. Implementation Steps
1. Open `resources/js/bootstrap.js`
2. Add imports:
   ```javascript
   import Echo from 'laravel-echo';
   import Pusher from 'pusher-js';
   ```
3. Add Echo initialization after axios setup:
   ```javascript
   window.Pusher = Pusher;

   window.Echo = new Echo({
     broadcaster: 'reverb',
     key: import.meta.env.VITE_REVERB_APP_KEY || 'local',
     wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
     wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
     forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
     enabled: import.meta.env.VITE_REVERB_ENABLED !== 'false',
     authEndpoint: '/broadcasting/auth',
   });
   ```
4. Save file and verify no errors

## ✅ Final Verification
- [ ] Echo imported from 'laravel-echo'
- [ ] Pusher imported and assigned to window.Pusher
- [ ] window.Echo initialized with Reverb config
- [ ] Environment variables used for config
- [ ] `npm run dev` works without errors
- [ ] Console shows `window.Echo` object exists
