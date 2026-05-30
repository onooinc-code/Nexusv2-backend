# 🎯 TASK: UP-003 - Task 3: Event Broadcasting Infrastructure
- **Status:** ✅ COMPLETED
- **Dependencies:** Task 1 (Horizon Configuration & Base Job Infrastructure)

## 1. Objective
Create the Laravel channels authorization file (`routes/channels.php`) with private channel definitions for chat sessions and business logic. This establishes the foundation for secure event broadcasting with proper access control policies.

## 2. Files to Create/Modify
- `routes/channels.php`: Create file with private channel authorizations
- `app/Policies/SessionPolicy.php`: Create or verify policy for session access authorization
- `config/broadcasting.php`: Verify Reverb driver is configured (may already exist)

## 3. Implementation Steps
1. **Create routes/channels.php**
   - Define private channel `session.{sessionId}` with authorization:
     - Authorization callback: Check if authenticated user has access to session
     - Use SessionPolicy to verify ownership or shared access
   - Define private channel `conversation.{conversationId}` with authorization:
     - Check if user is participant in conversation
   - Define presence channel `users.{conversationId}` for real-time user presence:
     - Track active users in conversation
     - Return user info: `id`, `name`, `avatar_url` (no sensitive data)
   - Define private channel `job.batch.{batchId}` for job monitoring:
     - Check if user has permission to view batch jobs
   - Define private channel `admin.dlq` for admin-only dead-letter queue access:
     - Verify user is admin role

2. **Create/Update app/Policies/SessionPolicy.php**
   - Implement policy methods:
     - `view(User $user, Session $session)`: Check ownership or shared access
     - `update(User $user, Session $session)`: Check ownership
     - `subscribe(User $user, string $sessionId)`: Check access to session ID
   - Use role-based access control (check user.role or permission model)
   - Cache authorization decisions with TTL for performance

3. **Verify config/broadcasting.php**
   - Ensure `default` driver is set to `reverb` (or appropriate for Reverb integration)
   - Verify Reverb configuration section with host, port, scheme
   - Ensure `BROADCAST_DRIVER=reverb` in `.env`

4. **Create Event Authorization Base Class**
   - Create `app/Events/BroadcastableEvent.php` extending Event class
   - Implement methods:
     - `shouldBroadcast()`: Check if event should be broadcast
     - `broadcastOn()`: Define which channels
     - `broadcastWith()`: Limit broadcast data
   - This serves as base for Task 4 event refactoring

## ✅ Final Verification Checklist
- [ ] `routes/channels.php` file created with all channel definitions
- [ ] Private channels implement proper authorization callbacks
- [ ] Presence channels defined for real-time user tracking
- [ ] Channel authorization returns only non-sensitive user data
- [ ] SessionPolicy created with appropriate access checks
- [ ] Broadcasting config verified with Reverb driver
- [ ] No full Eloquent models are exposed in channel authorization
- [ ] Role-based access control properly implemented
- [ ] Channel names follow naming convention: `{type}.{id}`
