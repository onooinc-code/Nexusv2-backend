# Conversation Interface

## Purpose
Define the conversation UI model and behavior used across Homepage, Agents Hub and Channels (WhatsApp/PeopleConnect). This document specifies the two primary conversation modes, data model, UX flows, components, accessibility and small implementation tasks.

## Primary Modes (Homepage tabs)
- HedraSouly: personal agent channel, one-to-one with user's private AI assistant (context: private memory + user profile). Conversational tone: personal, proactive suggestions, memory-aware.
- PeopleConnect: multi-channel conversation center for external channels (WhatsApp, SMS, Email, WAHA). Conversational tone: channel-specific formatting, delivery receipts, phone-thread semantics.

## Data Model
- Session
  - id (uuid)
  - user_id
  - channel (enum: hedra | peopleconnect:<channel_name>)
  - title
  - state (open|archived|closed)
  - created_at, updated_at
- Topic (optional grouping inside Session)
  - id, session_id, title, metadata
- Message
  - id (uuid)
  - session_id
  - topic_id (nullable)
  - sender_type (user|agent|external-system)
  - sender_id
  - content (text|rich payload reference)
  - attachments (array of asset refs)
  - status (sending|sent|delivered|read|failed)
  - meta (tokens_used, model, prompt_id)
  - created_at, updated_at

Design notes: enforce message size limit; split long user inputs into multiple messages with continuity metadata (sequence_id)

## UX Requirements
- Two-tab experience on Homepage: left tab list, center conversation area toggles between `HedraSouly` and `PeopleConnect`.
- Quick switch preserves active session per tab; each tab keeps its own session history and selected thread.
- Typing indicator with estimated streaming tokens and model inference progress (for agent responses).
- Message composer supports: rich text, code blocks, attachments, channel-specific templates (e.g., WhatsApp buttons), scheduling (defer send), and pinned macros.
- Message sending: optimistic UI update with status transitions; show retry for failures and allow edit-in-place if not yet delivered.
- Long-context trimming: when token/context limits near threshold, suggest summary/trim and offer an automated summarization button.
- Session lifecycle: auto-archive rule (configurable) after N days of inactivity; manual archive/unarchive; session rename.
- Multi-message batching: allow user to compose a long message that the client splits and streams to the model while preserving logical continuity.

## Components
- ConversationTabs: manages `HedraSouly` & `PeopleConnect` states and persisted active session per tab.
- SessionList (left pane): grouped by unread/priority; search & filters (date, tags, channel).
- ChatWindow (center): message list with virtualized scroll, date separators, thread view, message grouping.
- Composer: rich editor with attachments, quick-actions, schedule picker, model selector and prompt templates.
- MessageBubble: renders text, media, actions (reply, forward, edit, react), delivery indicators.
- ThreadView: collapsible thread context + inline replies.
- RightPanel: session details, memory snippets, tools (execute job, run workflow).

## Interaction flows
- Start new session: click `New` → choose `HedraSouly` or `PeopleConnect` → create session with initial system prompt.
- Switch tab: preserve scroll and selected session; load minimal context then lazy-load older messages.
- Incoming external message: if PeopleConnect channel receives inbound, surface desktop/OS notification and mark session unread; auto-open in PeopleConnect tab if user is active.
- Send scheduled message: composer has schedule picker; job created in SchedulerHub; show pending state in message list until executed.

## Visual/Animation Guidelines
- Use gentle glassmorphism cards and subtle elevation for message bubbles.
- Streaming responses show skeleton typing + token progress bar; animate new messages sliding into view from bottom.
- Respect reduced-motion user setting.

## Accessibility
- All interactive elements must have keyboard focus order and ARIA labels.
- Provide readable contrast; ensure screen-reader-friendly message structure.

## Error states & Recovery
- Failed send: show actionable errors (network, rate-limit, blocked). Offer resend, save draft, or escalate to support.
- Stale session: on token limit or model failure, show `Regenerate` and `Trim context` actions.

## Testing Checklist
- Tab switching preserves state across reloads.
- Composer supports scheduled sends and attachments across channels.
- Message ordering consistent with server timestamps when reconnected.
- Summary suggestion triggers when context threshold crossed.

## Implementation tasks (small, testable)
- Create `Session` and `Message` migrations and models (include indexes for session_id, created_at).
- Build `ConversationTabs` component with persisted active tab per user preference.
- Implement optimistic message send flow with local queue and background reconciliation.
- Add `schedule` hook to composer that enqueues SchedulerHub job (API call) and displays pending message status.
- Add unit tests for message splitting logic and session archiving policies.
