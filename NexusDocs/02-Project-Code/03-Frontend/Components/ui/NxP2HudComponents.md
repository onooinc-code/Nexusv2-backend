# P2 HUD Components

## 1. NxQueuePill
- **File**: `components/ui/NxQueuePill.tsx`
- **Description**: Clickable glass pill showing current job queue depth.
- **Requirements**:
  - Color logic: grey (0), blue (>0), crimson (failures).
  - Bounce animation on count increase.
  - Opens `NxQueueModal` on click.

## 2. NxAgentBadge
- **File**: `components/ui/NxAgentBadge.tsx`
- **Description**: Displays count of active/thinking agents.
- **Requirements**:
  - Integrates `NxAiPulse` orb.
  - Navigates to Agents Hub on click.

## 3. NxRateLimitBanner
- **File**: `components/ui/NxRateLimitBanner.tsx`
- **Description**: Dismissible warning banner for API rate limits.
- **Requirements**:
  - Slides down below status bar.
  - Shows countdown to reset.

## 4. NxTokenBudget
- **File**: `components/ui/NxTokenBudget.tsx`
- **Description**: SVG ring showing daily token usage vs budget.
- **Requirements**:
  - Color thresholds: Blue (<70%), Amber (70-90%), Crimson (>90%).