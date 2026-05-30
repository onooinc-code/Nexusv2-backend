# 🎯 TASK: UP-001 - Task 02: Color Token Remapping
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Fix all 13 CSS color token violations from Section 1 of `uiuv_v2.md`. Remap all `--color-*` CSS variables to match the Nexus Design System spec (Nexus Blue #007AFF, Emerald #10B981, Crimson #EF4444, AI-Core Purple #6366F1, Amber #F59E0B).

## 2. Files to Create/Modify
- `resources/css/app.css` (lines 9–66): Update all color variable definitions in `:root` block
- `resources/css/app.css` (lines 69–87): Update light theme overrides in `[data-theme="light"]` block

## 3. Implementation Steps
1. Open `resources/css/app.css`
2. In `:root` block (lines 14–48), replace:
   - `--color-primary: #4ade80;` → `--color-primary: #007AFF;`
   - `--color-primary-hover: #22c55e;` → `--color-primary-hover: #007AFF;`
   - `--color-primary-muted: rgba(74, 222, 128, 0.1);` → `--color-primary-muted: rgba(0, 122, 255, 0.1);`
   - `--color-primary-border: rgba(74, 222, 128, 0.3);` → `--color-primary-border: rgba(0, 122, 255, 0.3);`
   - `--color-success: #4ade80;` → `--color-success: #10B981;`
   - `--color-error: #f87171;` → `--color-error: #EF4444;`
   - `--color-border-focus: #4ade80;` → `--color-border-focus: #007AFF;`
3. Add new color variables after line 30:
   - `--color-ai-core: #6366F1;` (Hédra Purple)
   - `--color-warning: #F59E0B;` (Amber - semantic alias)
4. Update `--shadow-glow` (line 54): `rgba(74, 222, 128, 0.15)` → `rgba(0, 122, 255, 0.15)`
5. In `[data-theme="light"]` block, update:
   - `--color-bg-glass: rgba(255, 255, 255, 0.7);` → `rgba(245, 245, 245, 0.85);`
   - `--color-border-focus` to `#007AFF` if present
6. Save file and verify Vite HMR updates

## ✅ Final Verification
- [ ] All 13 color token violations fixed
- [ ] `--color-primary` resolves to `#007AFF` in DevTools
- [ ] `--color-success` resolves to `#10B981`
- [ ] `--color-error` resolves to `#EF4444`
- [ ] `--color-ai-core` exists and equals `#6366F1`
- [ ] `--color-border-focus` equals `#007AFF`
- [ ] Light theme colors updated correctly
- [ ] No console errors
