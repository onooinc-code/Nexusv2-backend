# 🎯 TASK: UP-002 - Task 01: NxGlassCard Component (F-UI-02)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Create `NxGlassCard.vue` — the standard glass container for profiles, memories, settings, and panels with elevation and hoverable props.

## 2. Files to Create/Modify
- `resources/js/Components/NxGlassCard.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxGlassCard.vue`
2. Props: `elevation: Number` (default: 2, range 1-3), `hoverable: Boolean` (default: false)
3. Slots: `#header`, default (`#body`), `#footer`
4. Template:
   ```vue
   <template>
     <article class="nx-glass-card" :class="[`elevation-${elevation}`, { hoverable }]">
       <div v-if="$slots.header" class="card-header">
         <slot name="header" />
       </div>
       <div class="card-body">
         <slot />
       </div>
       <div v-if="$slots.footer" class="card-footer">
         <slot name="footer" />
       </div>
     </article>
   </template>
   ```
5. Styles:
   - Base: `background: rgba(22,27,34,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; overflow: hidden;`
   - `.elevation-1`: `box-shadow: 0 4px 6px rgba(0,0,0,0.4);`
   - `.elevation-2`: `box-shadow: 0 10px 15px rgba(0,0,0,0.5);`
   - `.elevation-3`: `box-shadow: 0 20px 25px rgba(0,0,0,0.6);`
   - `.hoverable`: `transition: transform 150ms ease, box-shadow 150ms ease; &:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(0,0,0,0.5); }`
   - `.card-header`: `position: sticky; top: 0; z-index: 10; border-bottom: 1px solid rgba(255,255,255,0.05);`
   - `.card-body`: `flex: 1; overflow-y: auto;`
   - `.card-footer`: `border-top: 1px solid rgba(255,255,255,0.05);`
6. Save file and verify

## ✅ Final Verification
- [ ] Component created with elevation and hoverable props
- [ ] All 3 slots (header, body, footer) work
- [ ] Elevation 1/2/3 apply correct shadows
- [ ] Hoverable adds translateY(-2px) on hover
- [ ] Glassmorphism styling applied
- [ ] No console errors
