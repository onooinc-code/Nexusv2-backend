# 🎯 TASK: UP-001 - Task 04: Glass Background Fix
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-02_Color_Token_Remapping

## 1. Objective
Fix the glassmorphism background values to match the spec: `.glass` should be `rgba(22,27,34,0.7)` not `rgba(255,255,255,0.05)`. Also fix `.glass-strong` and `.glass-subtle` variants, and update light theme glass background.

## 2. Files to Create/Modify
- `resources/css/app.css` (lines 93–115): Update all three glass utility classes
- `resources/css/app.css` (line 74): Update light theme `.glass` background

## 3. Implementation Steps
1. Open `resources/css/app.css`
2. In `.glass` class (line 94), change:
   - `background: var(--color-bg-glass);` → `background: rgba(22, 27, 34, 0.7);`
3. In `.glass-strong` class (line 102), change:
   - `background: rgba(255, 255, 255, 0.1);` → `background: rgba(22, 27, 34, 0.85);`
4. In `.glass-subtle` class (line 110), change:
   - `background: rgba(255, 255, 255, 0.03);` → `background: rgba(22, 27, 34, 0.5);`
5. In `[data-theme="light"]` block (line 74), change:
   - `--color-bg-glass: rgba(255, 255, 255, 0.7);` → `rgba(245, 245, 245, 0.85);`
6. Save file and verify Vite HMR updates

## ✅ Final Verification
- [ ] `.glass` background is `rgba(22, 27, 34, 0.7)`
- [ ] `.glass-strong` background is `rgba(22, 27, 34, 0.85)`
- [ ] `.glass-subtle` background is `rgba(22, 27, 34, 0.5)`
- [ ] Light theme `.glass` background is `rgba(245, 245, 245, 0.85)`
- [ ] All glass surfaces render with correct dark glass effect
- [ ] No console errors
