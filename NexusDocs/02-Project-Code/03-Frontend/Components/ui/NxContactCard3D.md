# NxContactCard3D

## Specification
- **File**: `components/modules/contacts/NxContactCard3D.tsx`
- **Animation**: CSS 3D `rotateY` transition (800ms cubic-bezier).

## Features
- **3D Flip**: Card flips on click to reveal detailed relationship data.
- **Front Face**:
  - Profile Avatar with a gradient ring.
  - Name and Title.
  - High-level engagement stats.
- **Back Face**:
  - AI-generated relationship summary.
  - Emotional baseline summary.
  - Quick Action buttons (Message, Edit, Archive).
- **Tilt Effect**: Subtle mouse-follow tilt using CSS transforms or a library like `react-parallax-tilt`.