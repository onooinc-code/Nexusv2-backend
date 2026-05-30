# UP-006_Task-05: NxChannelStatus — Channel Badges

## Task Overview
Create NxChannelStatus.vue — communication channel indicator badges.

## Feature Specification
- **Feature ID:** F-CP-05
- **File:** `resources/js/Components/NxChannelStatus.vue` (new)

## Requirements
1. Row of channel indicator badges: WhatsApp, SMS, Email
2. Each badge: channel icon + colored status dot
3. Props: channels: Array<{ type, status, lastMessageAt }>
4. Click: opens PeopleConnect tab filtered to that channel
5. Animation: status dots use NxConnectionDot-style animations

## Implementation Details
- Badge: display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 9999px
- Channel colors: WhatsApp → #25D366; SMS → #007AFF; Email → #64748B
- Status dot: width: 6px; height: 6px; border-radius: 50%

## Verification
- `npm run build` passes
- WhatsApp/SMS/Email badges render with correct icons
- Status dots show correct colors
- Click opens PeopleConnect filtered
