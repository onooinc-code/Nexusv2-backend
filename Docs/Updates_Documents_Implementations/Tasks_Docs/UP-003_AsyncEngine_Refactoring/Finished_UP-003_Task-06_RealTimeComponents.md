# 🎯 TASK: UP-003 - Task 6: Real-time Components Development
- **Status:** ✅ COMPLETED
- **Dependencies:** Task 5 (Laravel Echo & Reverb Frontend Integration)

## 1. Objective
Create Vue 3 components for real-time chat streaming (LiveChatStream), job/batch progress monitoring (GlobalJobMonitor), and connection status indication (ConnectionStatus). These components use Echo listeners to receive and display real-time events.

## 2. Files to Create/Modify
- `resources/js/Components/LiveChatStream.vue`: New component for token-by-token LLM streaming
- `resources/js/Components/GlobalJobMonitor.vue`: New component for batch job progress
- `resources/js/Components/ConnectionStatus.vue`: New component for WebSocket connection indicator
- `resources/js/Pages/ConversationsView.vue`: Updated to subscribe to conversation channels and render connection state
- `resources/js/composables/useLiveChat.js`: New composable for chat streaming logic

## 3. Implementation Steps
1. **Create `resources/js/Components/LiveChatStream.vue`**
   - Props: `conversationId`, `messageId`, `isLoading`
   - Data: 
     - `streamingContent`: string (accumulated tokens)
     - `isStreaming`: boolean
     - `displayedTokens`: number
     - `startTime`: timestamp
   - Setup:
     - Use `useEchoChannel()` to subscribe to private channel `conversation.{conversationId}`
     - Listen for `TokenStreamed` events: append token to `streamingContent`
     - Listen for `MessageCompleted` event: finalize message, update `isStreaming = false`
     - Auto-scroll to latest content
   - Emit events: `streaming-complete`, `error`
   - Render: Display streaming text with cursor animation when active
   - Cleanup: Unsubscribe from Echo channel on unmount

2. **Create resources/js/Pages/Components/GlobalJobMonitor.vue**
   - Props: `batchId` (optional, show all batches if not provided)
   - Data:
     - `activeBatches`: Map of `{batchId: {progress, total, status, percentage}}`
     - `completedBatches`: Array of completed batch records
   - Setup:
     - If `batchId` provided: Subscribe to `job.batch.{batchId}` channel
     - If not provided: Subscribe to user-level `job.batches.all` channel
     - Listen for `BatchProgressUpdated` events: update `activeBatches` map
     - Listen for batch completion events: move to `completedBatches`, emit event
   - Render:
     - Display progress bar for each active batch: `[progress/total] percentage%`
     - Show batch name, job count, estimated time remaining
     - Green checkmark for completed batches
     - Collapse/expand for history
   - Computed: `totalProgress = sum(all batch percentages) / batch count`
   - Auto-hide completed batches after 30 seconds (optional)

3. **Create resources/js/Pages/Components/ConnectionStatus.vue**
   - No props (uses global useEchoStore)
   - Data from Pinia store: `connectionStatus`, `useFallback`
   - Render status indicator:
     - Green dot + "Connected": `connectionStatus === 'connected' && !useFallback`
     - Yellow dot + "Reconnecting": `connectionStatus === 'reconnecting'`
     - Orange dot + "Fallback Mode": `useFallback === true`
     - Red dot + "Disconnected": `connectionStatus === 'disconnected' || connectionStatus === 'error'`
   - Tooltip on hover: Show last connection time, reconnection attempts if applicable
   - Click action: Show connection debug info (WS URL, connection duration, etc.)
   - Optional: Audio alert on disconnect (user preference)

4. **Create resources/js/composables/useLiveChat.js**
   - Export helper functions:
     - `startStreamingMessage(conversationId, messageId)`: Initialize streaming UI
     - `appendToken(token)`: Add token to stream display
     - `completeStream()`: Finalize stream display
     - `getStreamingContent()`: Return accumulated content
     - `clearStream()`: Reset streaming data
   - Handle edge cases:
     - Rapid token arrival (buffer and render in batches)
     - Network interruption during stream
     - User navigation away during stream

5. **Modify `resources/js/Pages/ConversationsView.vue`**
   - Import and use LiveChatStream or Echo subscription helpers
   - Remove old REST polling logic for message updates where applicable
   - Replace with Echo listener for messages:
     ```javascript
     Echo.private(`conversation.${conversationId}`)
       .listen('MessageReceived', (event) => {
         // Handle incoming message
       });
     ```
   - Add ConnectionStatus component to header
   - Implement graceful fallback to REST polling if WS unavailable:
     - Detect when `useEchoStore.shouldUsePolling === true`
     - Switch to polling endpoint (e.g., `/api/messages/{conversationId}`)
     - Re-enable WS when `useFallback === false`
   - Track unsent messages during disconnection
   - Resync on reconnection

6. **Implement Streaming Message Rendering**
   - Use Vue's transition components for smooth text appearance
   - Animate text cursor at end of streaming content
   - Prevent layout shift: Use monospace font for token display
   - Performance: Don't re-render entire message per token, update textContent only

## ✅ Final Verification Checklist
- [ ] LiveChatStream component created and subscribes to Echo channels
- [ ] TokenStreamed events properly appended to display
- [ ] MessageCompleted event finalizes streaming message
- [ ] GlobalJobMonitor tracks multiple batch progress in real-time
- [ ] BatchProgressUpdated events update progress bar
- [ ] ConnectionStatus component displays connection state
- [ ] Status indicator colors match specification (green/yellow/orange/red)
- [ ] Components use useEchoStore Pinia store correctly
- [ ] Echo unsubscribe on component unmount
- [ ] Fallback to polling works when WS unavailable
- [ ] No console errors or warnings
- [ ] Components are responsive and work on mobile
- [ ] Streaming text cursor animation smooth and visible
- [ ] Progress bars update without layout shift
- [ ] Memory leaks prevented: Echo listeners cleaned up
