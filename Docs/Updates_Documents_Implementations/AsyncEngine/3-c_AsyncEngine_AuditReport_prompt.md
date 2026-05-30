Act as a Principal Systems Auditor and Senior Laravel/Vue Engineer. 

I have provided two strict master blueprint files for the "Async & Real-Time Engine (WebSockets, Background Jobs, and Queues)":
1. @AsyncEngine_Architecture_Blueprint.md (Core Architecture & Rules)
2. @AsyncEngine_Implementation_Blueprint.md (Implementation Details & DB/Queue Configs)

Please perform a deep AST and textual audit of my ENTIRE current workspace (Vue components, Jobs, Events, channels.php, horizon.php, and Echo setups) against these two blueprints.

Analyze what has been implemented so far, extract all missing features, incomplete logic, bugs, and architectural violations. 

Create a new markdown file named `AsyncEngine_Detailed_AuditReport.md` in the root of the project (or docs folder) and populate it strictly using the following structure:

# ⚡ Async & Real-Time Engine - Deep Codebase Audit

## 1. 🖥️ Frontend & UI Deviations (Echo & Pinia)
(Audit the frontend Vue files. Is `window.Echo` configured correctly for Reverb? Are we using `.private()` channels? Is there a reconnection strategy?)

## 2. 📡 WebSocket & Event Security Gaps (channels.php & Events)
(Audit `routes/channels.php` and `app/Events`. Are there public channels that should be private? Do Events leak full Eloquent models in `broadcastWith()`?)

## 3. ⚙️ Queue Configuration & Horizon Deviations
(Audit `config/horizon.php`. Are the supervisors separated into strict queues like `critical` and `llm-inference`? Are timeout values configured correctly?)

## 4. 🏗️ Background Job Architectural Violations (Critical)
(Search `app/Jobs`. Are jobs missing `public $deleteWhenMissingModels = true;`? Is any job using `sleep()` instead of `$this->release($seconds)`? Are `$tries` or `$timeout` missing?)

## 5. ❌ General Missing Implementations
(List anything else requested in the blueprints that simply does not exist yet.)

**Execution Rule:** Do not generalize. Point to exact files, line numbers, and missing code blocks based on your analysis of the current codebase.
