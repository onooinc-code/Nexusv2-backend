# 🎯 TASK: UP-002 - Task 04: NxActionButton Component (F-UI-05)
- **Status:** 🔴 PENDING
- **Dependencies:** UP-001_Task-01_Package_Installation

## 1. Objective
Create `NxActionButton.vue` — a standardized interaction button with optimistic UI support, 4 variants, and 44×44px touch target compliance.

## 2. Files to Create/Modify
- `resources/js/Components/NxActionButton.vue` (new)

## 3. Implementation Steps
1. Create `resources/js/Components/NxActionButton.vue`
2. Props: `variant: 'primary' | 'secondary' | 'danger' | 'ghost'` (default: 'primary'), `loading: Boolean` (default: false), `disabled: Boolean` (default: false), `optimistic: Boolean` (default: false), `optimisticState: 'pending' | 'success' | 'error' | null`
3. Emits: `click`, `update:optimisticState`
4. Slots: `#default`, `#loading`
5. Template:
   ```vue
   <template>
     <button
       class="nx-action-button"
       :class="[variant, { loading, disabled, [optimisticState]: optimisticState }]"
       :disabled="disabled || loading"
       @click="handleClick"
     >
       <span v-if="loading" class="loading-spinner">
         <slot name="loading">
           <NxLiveLoader :taskId="null" :status="'loading'" />
         </slot>
       </span>
       <span v-else class="button-content">
         <slot />
       </span>
     </button>
   </template>
   ```
6. Computed:
   - `buttonClass`: computed from variant + optimisticState
7. Styles:
   - Base: `min-height: 44px; min-width: 44px; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px;`
   - `primary`: `background: #007AFF; color: white; border: none;`
   - `secondary`: `background: transparent; color: #007AFF; border: 1px solid rgba(0,122,255,0.3);`
   - `danger`: `background: #EF4444; color: white; border: none;`
   - `ghost`: `background: transparent; color: var(--color-text-secondary); border: none;`
   - `pending`: `opacity: 0.7;`
   - `success`: `background: #10B981 !important; color: white;`
   - `error`: `background: #EF4444 !important; color: white; animation: shake 100ms;`
   - Hover: `opacity: 0.9; transform: scale(0.98);`
8. Save file and verify

## ✅ Final Verification
- [ ] Component created with all props
- [ ] All 4 variants render correct colors
- [ ] Loading state shows spinner
- [ ] Optimistic states (pending/success/error) work
- [ ] Touch target is ≥ 44×44px
- [ ] Click and update:optimisticState events emit
- [ ] No console errors
