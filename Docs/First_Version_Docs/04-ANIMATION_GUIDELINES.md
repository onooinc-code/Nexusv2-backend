# 04 - Animation Guidelines

## ✨ The Animation Philosophy
Animations in Nexus are not decorative; they are **functional**. They provide feedback, imply hierarchy, and make the AI's "thought process" visible.

## 🕒 Timing & Easing
- **Quick (Action)**: `150ms` (Hover states, small toggles).
- **Standard (Movement)**: `300ms` (Sidebar expanding, page transitions).
- **Smooth (Entrance)**: `500ms` (Large modal entries, AI generated content appearing).

**Easing**: `cubic-bezier(0.4, 0, 0.2, 1)` (The "Standard" ease for natural movement).

---

## 🤖 Hédra (AI) States
Hédra's presence is represented by a "Pulse" orb.

| State | Animation | CSS Property |
|-------|-----------|--------------|
| **Idle** | Slow breathing glow | `opacity: 0.4 to 0.7` over 4s |
| **Thinking** | Rapid rotating gradient | `transform: rotate(0deg to 360deg)` over 1s |
| **Speaking** | Sound-reactive scale | `transform: scale(1.0 to 1.3)` based on audio |
| **Error** | Red jitter | `transform: translateX(-2px to 2px)` over 100ms |

---

## 🏗️ Structural Transitions

### Page In/Out
- **In**: Slide up `12px` + Fade in.
- **Out**: Pure fade out (faster: `200ms`).

### List Item Reordering
Use **FLIP (First Last Invert Play)** animations when tasks or contacts are sorted. Items should slide smoothly to their new positions rather than snapping.

---

## 🔘 Feedback Animations

### Button Press
- Subtle scale down: `transform: scale(0.96)`.
- Release: Spring back to `1.0`.

### Success Check
When a task is completed, use a drawing SVG path animation for the checkmark (`stroke-dashoffset`).

---

## 🔇 Reduced Motion
For users with vestibular disorders, all movement animations (sliding, zooming) are disabled if `prefers-reduced-motion: reduce` is detected. Only **fade transitions** are preserved.
