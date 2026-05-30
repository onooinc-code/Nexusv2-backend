# P4 Nice-to-Have Features

## 1. NxCelebration
- **File**: `components/ui/NxCelebration.tsx`
- **Description**: Canvas-based confetti or particle effect for major milestones.
- **Trigger**: Task completion or achieving a high engagement score milestone.

## 2. NxAiSummary
- **File**: `components/modules/chat/NxAiSummary.tsx`
- **Description**: A "TL;DR" generator for long chat sessions or complex task logs.
- **Features**:
  - One-click summary generation.
  - Bulleted list of key takeaways.

## 3. Session Undo
- **Description**: Optimistic UI state management that allows "undoing" recent actions (e.g., archiving a contact or deleting a memory) within a 5-second window.
- **Implementation**: Managed via a temporary buffer in Zustand.