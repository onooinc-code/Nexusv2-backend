# UP-006_Task-01: NxContactCard3D — Virtual 3D Flip Card

## Task Overview
Create NxContactCard3D.vue — CSS 3D perspective flip card.

## Feature Specification
- **Feature ID:** F-CP-01
- **File:** `resources/js/Components/NxContactCard3D.vue` (new)

## Requirements
1. CSS 3D perspective flip card
2. Front: avatar with gradient ring, canonical name, presence dot (C12), channel status (C05), key stats
3. Back: AI-generated relationship summary, emotional baseline snapshot, top 3 personality traits, quick actions
4. Props: contact: Object, flipped: Boolean
5. Animation: rotateY(180deg) with transition: 800ms cubic-bezier(0.23, 1, 0.32, 1)
6. Avatar ring: gradient rotation 360deg over 4s when contact "active today"
7. Hover (desktop): rotateX(3deg) rotateY(-3deg) tilt following mouse

## Implementation Details
- perspective: 1000px on container
- transform-style: preserve-3d on card inner
- backface-visibility: hidden on front/back faces
- Gradient ring: conic-gradient(from 0deg, #007AFF, #6366F1, #007AFF)
- Touch target: entire card is clickable, min-height: 120px

## Verification
- `npm run build` passes
- Card flips on click
- Tilt follows mouse on desktop
- Gradient ring rotates when contact active today
