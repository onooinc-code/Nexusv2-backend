# 🎯 TASK: UP-001 - Task 03: Tailwind Config Extensions
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Update `tailwind.config.js` to extend the theme with correct fonts (Inter + JetBrains Mono) and color tokens matching the Nexus Design System spec.

## 2. Files to Create/Modify
- `tailwind.config.js` (lines 12–19): Extend `fontFamily` and add `colors` block

## 3. Implementation Steps
1. Open `tailwind.config.js`
2. Replace the `fontFamily` extension (lines 14–16):
   - Change `'Figtree'` to `'Inter'`
   - Add `'JetBrains Mono'` as mono font family
3. Add a `colors` block inside `theme.extend`:
   ```javascript
   colors: {
     'surface-high': '#0B0E14',
     'surface-mid': '#161B22',
     'action-primary': '#007AFF',
     'ai-core': '#6366F1',
     'status-success': '#10B981',
     'status-warning': '#F59E0B',
     'status-error': '#EF4444',
   }
   ```
4. Add `tracking-tight` utility in `letterSpacing`:
   ```javascript
   letterSpacing: {
     'tight': '-0.02em',
   }
   ```
5. Save file and verify Vite rebuilds without errors

## ✅ Final Verification
- [ ] Font family changed from Figtree to Inter
- [ ] JetBrains Mono added as mono font
- [ ] All 7 color tokens added to Tailwind theme
- [ ] `tracking-tight` utility available
- [ ] `npm run dev` works without errors
- [ ] No console warnings about missing fonts
