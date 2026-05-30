# 02 - Hubs UI Layout

## 🏗️ General Hub Structure
Every hub in the Nexus interface follows a standardized three-pane layout to ensure familiarity and ease of use.

### 1. The Navigation Sidebar (Left)
- **Width**: `80px` (collapsed) or `240px` (expanded).
- **Function**: Global switching between hubs (Agents, Memory, Contacts, etc.).
- **Bottom**: User profile, settings, and workspace switcher.

### 2. The Hub Sidebar (Middle-Left)
- **Width**: `320px`.
- **Purpose**: Lists the primary entities of the current hub.
- **Example**: 
    - **Agents Hub**: List of active AI personalities.
    - **Memory Hub**: Chronological interaction list.
    - **Contacts Hub**: Alphabetical contact list.
- **Features**: Search bar at top, Filter/Sort icons.

### 3. The Workspace (Center/Right)
- **Width**: Flexible.
- **Purpose**: The main interaction area for the selected entity.
- **Features**: Breadcrumbs, action header (Edit, Delete, Share), and tabbed content views.

---

## 🤖 Agents Hub Layout
- **Primary View**: Conversation Interface.
- **Floating Controls**: Persona selector, model switcher.
- **Right Panel**: "Agent Intel" — current state, recent tasks, and cognitive load indicators.

---

## 🧠 Memory Hub Layout
- **Visualization**: A searchable timeline of events.
- **Interaction**: Clicking an event expands a "Context Map" showing linked contacts, files, and AI insights.
- **Tabs**: `All`, `Episodic`, `Semantic`, `Structured`.

---

## 👤 Contacts Hub Layout
- **Summary View**: Business card style profile with "Relationship Score".
- **Detail View**: 
    - **Left**: Bio, links, tags.
    - **Center**: "Intelligence Timeline" — things Hédra knows about them.
    - **Right**: Communication history across all channels.

---

## ⚙️ Settings Hub Layout
- **Structure**: Vertical tabbed navigation (General, Security, AI Providers, Notifications).
- **Presentation**: Clean, centered form layouts with immediate "Saved" feedback.

---

## 📊 Analytics & Logs Hub Layout
- **Dashboard Grid**: Responsive widgets showing token usage, cost, and activity heatmaps.
- **Log Table**: Fixed-header table with high-density rows, monospaced text, and color-coded log levels.

---

## 📱 Global Elements
- **The Command Bar (`Cmd+K`)**: A floating search/action bar that appears over any hub.
- **Hédra Pulse**: A small, persistent AI status orb in the bottom-right that changes color/motion based on Hédra's activity (Thinking, Idle, Listening).
