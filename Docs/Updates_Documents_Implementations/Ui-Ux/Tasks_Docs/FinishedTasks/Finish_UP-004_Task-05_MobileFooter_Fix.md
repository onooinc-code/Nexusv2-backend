# UP-004_Task-05: MobileFooter — Fix Bottom Tab Bar

## Task Overview
Fix existing `MobileFooter.vue` — mount in App.vue, fix tabs to Home/Memory/Contacts/Tasks/Search.

- **Status:** 🟢 FINISHED

## Feature Specification
- **Feature ID:** F-NAV-04
- **File:** `resources/js/Components/MobileFooter.vue` (modify)

## Requirements
1. Fix existing MobileFooter.vue
2. Tabs: Home, Memory, Contacts, Tasks, Search (5 tabs)
3. 64px height; heavily blurred glass background
4. Floating Hédra orb (NxVoiceOrb, D02) above tab bar center
5. Mount in App.vue — currently never rendered
6. Only visible at < 768px; hidden on desktop

## Implementation Details
- position: fixed; bottom: 0; left: 0; right: 0; height: 64px; z-index: 50
- background: rgba(22,27,34,0.85); backdrop-filter: blur(20px); border-top: 1px solid rgba(255,255,255,0.1)
- Tab: flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px
- Active tab: color: #007AFF; inactive: color: rgba(255,255,255,0.5)
- Touch target: min-height: 44px per mobile compliance rule

## Verification
- `npm run build` passes
- MobileFooter renders at bottom on mobile
- All 5 tabs navigate correctly
- Voice orb floats above tab bar
