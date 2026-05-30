# 07 - Header and Footer Specifications

## 🔝 The Global Header
The header is a **fixed, glassmorphism bar** at the top of the workspace area.

### Left Section: Context & Breadcrumbs
- **Breadcrumbs**: `Hub Name / Specific Entity Name` (e.g., `Contacts / John Doe`).
- **Function**: Allows quick navigation back up the hierarchy.

### Center Section: Global Search
- **Element**: A subtle, rounded input field.
- **Shortcut**: Shows `⌘ K` as a placeholder hint.

### Right Section: Action Icons
- **Workspace Switcher**: Dropdown to change between Personal and Team environments.
- **Notification Bell**: Red dot for unread alerts.
- **Connection Status**: Small green/red dot indicating WebSocket (Reverb) status.

---

## 🔝 Mobile Header
On mobile, the header is simplified:
- **Center**: Current Hub Title (e.g., "Agents").
- **Right**: Notification Bell and Search Icon.

---

## ⏬ The Global Footer (Utility Bar)
Nexus does not use a traditional website footer. Instead, it uses a **Utility Bar** anchored to the bottom.

### Desktop Utility Bar
- **Location**: Bottom of the Sidebar or Workspace.
- **Items**: 
    - **System Health**: Quick summary of API latencies.
    - **Token Usage**: Progress bar showing current session consumption.
    - **Help/Docs**: Icon link to documentation.

### 📱 Mobile Bottom Tab Bar
- **Height**: `64px`.
- **Blur**: Heavy backdrop blur.
- **Items**:
    1. **Agents** (Primary action in center, slightly larger).
    2. **Memory**.
    3. **Contacts**.
    4. **Tasks**.
    5. **Profile/Menu**.
