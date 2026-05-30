# 🎯 TASK: UP-001 - Task 05: Global Typography Fixes
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Add global typography styles: Inter and JetBrains Mono fonts, body line-height 1.6, H1/H2 tracking -0.02em, and font-variant-numeric: tabular-nums on mono.

## 2. Files to Create/Modify
- `resources/css/app.css`: Add font imports and global typography styles

## 3. Implementation Steps
1. Open `resources/css/app.css`
2. After the `@tailwind utilities;` directive (line 3), add:
   ```css
   @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
   ```
3. Before the `:root` block, add global body styles:
   ```css
   body {
     line-height: 1.6;
     font-family: 'Inter', sans-serif;
   }
   ```
4. After the `:root` block, add H1/H2 tracking:
   ```css
   h1, h2 {
     letter-spacing: -0.02em;
   }
   ```
5. Add mono font variant:
   ```css
   .font-mono {
     font-variant-numeric: tabular-nums;
   }
   ```
6. Save file and verify fonts load

## ✅ Final Verification
- [ ] Inter font loads and renders on body text
- [ ] JetBrains Mono loads and renders on code blocks
- [ ] Body line-height is 1.6 (check in DevTools)
- [ ] H1/H2 have letter-spacing -0.02em
- [ ] `.font-mono` has font-variant-numeric: tabular-nums
- [ ] No console 404 errors for fonts
