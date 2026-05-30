# 06 - Component Library

## 🧩 Shared Vue 3 Components
Nexus is built with reusable, atomic components. All components are located in `resources/js/Components/`.

---

## 🧱 Atomic Elements

### `NxButton.vue`
- **Props**: `variant` (primary, secondary, danger, glass), `size`, `loading`, `disabled`.
- **Slots**: Default (text), Icon (Left/Right).

### `NxInput.vue`
- **Props**: `type`, `label`, `error`, `placeholder`, `modelValue`.
- **Features**: Built-in validation display.

### `NxBadge.vue`
- **Props**: `color`, `label`.
- **Use Case**: Statuses (Active, Pending), Tags, Count indicators.

---

## 🏗️ Structural Components

### `NxCard.vue`
- **Style**: Glassmorphism container.
- **Slots**: Header, Body, Footer.

### `NxModal.vue`
- **Props**: `show`, `title`, `size`.
- **Features**: Teleported to body, backdrop blur, Escape-key to close.

### `NxSidebarItem.vue`
- **Props**: `icon`, `label`, `active`, `count`.
- **Style**: Subtle hover lift, active state with indicator line.

---

## 🤖 AI Specialized Components

### `NxAiBubble.vue`
- **Purpose**: Individual AI message in a chat.
- **Features**: Markdown rendering, Code syntax highlighting, "Copy" button.

### `NxAiPulse.vue`
- **Purpose**: The Hédra state orb.
- **Props**: `state` (idle, thinking, active).

### `NxTokenMeter.vue`
- **Purpose**: Visual bar showing context window usage.
- **Features**: Color change from green to red as limits are reached.

---

## 📊 Data Visualization

### `NxMiniChart.vue`
- **Type**: Sparkline (SVG).
- **Use Case**: Fast views of usage stats in the dashboard.

### `NxStatusPill.vue`
- **Purpose**: High-visibility status indicators for logs and tasks.
