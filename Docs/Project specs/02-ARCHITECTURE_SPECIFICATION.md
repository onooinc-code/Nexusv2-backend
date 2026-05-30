# 🏗️ Nexus - Technical Architecture Specification

## 1. System Architecture Overview

### 1.1 High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              NEXUS PLATFORM                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   AgentsHub  │  │ WorkflowsHub │  │  SettingsHub │  │ ContactsHub  │ │
│  │              │  │              │  │              │  │              │ │
│  │ • Agents     │  │ • Workflows  │  │ • Settings   │  │ • Contacts   │ │
│  │ • MCP        │  │ • Tasks      │  │ • Config     │  │ • Profiles   │ │
│  │ • Tools      │  │ • Monitoring │  │ • Validation │  │ • Intelligence││
│  │ • Skills     │  │ • Logging    │  │              │  │              │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │    LogsHub   │  │  MemoryHub   │  │  NexusHub    │  │ AIModelsHub  │ │
│  │              │  │              │  │              │  │              │ │
│  │ • Audit      │  │ • Working    │  │ • Dashboard  │  │ • Providers  │ │
│  │ • Streaming  │  │ • Episodic   │  │ • HedraSouly │  │ • Models     │ │
│  │ • Alerts     │  │ • Semantic   │  │ • PeopleConn │  │ • Keys       │ │
│  │ • Search     │  │ • Structured │  │ • QuickActs  │  │ • Fallbacks  │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                                           │
├─────────────────────────────────────────────────────────────────────────┤
│                           SERVICE LAYER                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  Routers     │  │  Pipelines   │  │   Engines    │  │   Builders   │ │
│  │              │  │              │  │              │  │              │ │
│  │ • Message    │  │ • Context    │  │ • Intent     │  │ • Prompt     │ │
│  │ • Task       │  │ • Memory     │  │ • Persona    │  │ • Profile    │ │
│  │ • Tone       │  │ • Response   │  │ • Memory     │  │ • Response   │ │
│  │ • Memory     │  │ • Extraction │  │ • AI         │  │ • Context    │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                                           │
├─────────────────────────────────────────────────────────────────────────┤
│                          DATA LAYER                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │    MySQL     │  │    Redis     │  │   Pinecone   │  │    Graph     │ │
│  │              │  │              │  │              │  │     DB       │ │
│  │ • Contacts   │  │ • Sessions   │  │ • Vectors    │  │              │ │
│  │ • Conversat. │  │ • Cache      │  │ • Embeddings │  │ • Relations  │ │
│  │ • Settings   │  │ • Queues     │  │ • Search     │  │ • Networks   │ │
│  │ • Logs       │  │ • Rate Limit │  │              │  │              │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                                           │
├─────────────────────────────────────────────────────────────────────────┤
│                        EXTERNAL INTEGRATIONS                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌──────────────┐                                                         │
│  │     WAHA     │                                                         │
│  │              │                                                         │
│  │ • WhatsApp   │                                                         │
│  │ • Messaging  │                                                         │
│  └──────────────┘                                                         │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Core Architectural Principles

### 2.1 API-First Design
Every feature must be accessible via a RESTful API before any UI is built.

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Client    │────>│    API      │────>│   Service   │
│   (Vue.js)  │<────│  (REST)     │<────│  (Business) │
└─────────────┘     └─────────────┘     └─────────────┘
```

**Rules:**
- All endpoints return JSON
- Consistent error format
- Versioned APIs (v1, v2, etc.)
- Rate limiting per endpoint
- Authentication required (except public endpoints)

### 2.2 Background-First Execution
All long-running operations execute as background jobs.

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Request   │────>│   Queue     │────>│    Job      │
│             │     │   (Redis)   │     │  (Worker)   │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │  Monitor    │
                    │  (Horizon)  │
                    └─────────────┘
```

**Job Types:**
- **Immediate**: Run as soon as possible
- **Scheduled**: Run at specific time
- **Recurring**: Run on schedule
- **Chained**: Run after another job
- **Batch**: Run multiple jobs together

### 2.3 Event-Driven Architecture
Components communicate through events, not direct calls.

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Component  │────>│    Event    │────>│  Listener   │
│    A        │     │   (Async)   │     │  (Component B)│
└─────────────┘     └─────────────┘     └─────────────┘
```

**Event Categories:**
- **System Events**: Application lifecycle
- **Domain Events**: Business logic events
- **Integration Events**: External system events

### 2.4 Cognitive Memory Layers
Five-layer memory architecture for intelligent context management.

```
┌─────────────────────────────────────────────────────────────────────┐
│                        MEMORY LAYERS                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │   Working    │  │   Episodic   │  │   Semantic   │               │
│  │   Memory     │  │   Memory     │  │   Memory     │               │
│  │              │  │              │  │              │               │
│  │ • Redis      │  │ • MySQL      │  │ • Vector DB  │               │
│  │ • Real-time  │  │ • Events     │  │ • Facts      │               │
│  │ • Context    │  │ • History    │  │ • Knowledge  │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐                                  │
│  │  Structured  │  │    Graph     │                                  │
│  │   Memory     │  │   Memory     │                                  │
│  │              │  │              │                                  │
│  │ • MySQL      │  │ • Graph DB   │                                  │
│  │ • Entities   │  │ • Relations  │                                  │
│  │ • Relations  │  │ • Networks   │                                  │
│  └──────────────┘  └──────────────┘                                  │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.5 Modular Hub Design
Each hub is a self-contained module with clear boundaries.

```
┌─────────────────────────────────────────────────────────────────────┐
│                          HUB STRUCTURE                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │                        Hub Name                                 │ │
│  ├─────────────────────────────────────────────────────────────────┤ │
│  │                                                                 │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │ │
│  │  │   Models     │  │   Services   │  │  Controllers │          │ │
│  │  │              │  │              │  │              │          │ │
│  │  │ • Entities   │  │ • Business   │  │ • API        │          │ │
│  │  │ • Relations  │  │ • Logic      │  │ • Routes     │          │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │ │
│  │                                                                 │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │ │
│  │  │    Actions   │  │    Events    │  │   Listeners  │          │ │
│  │  │              │  │              │  │              │          │ │
│  │  │ • Single     │  │ • Domain     │  │ • React      │          │ │
│  │  │ • Responsibility│ │ • Async    │  │ • Events     │          │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │ │
│  │                                                                 │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Routers Layer

Routers are the intelligent routing layer that determines execution paths.

### 3.1 MessageRouter
Routes incoming messages to appropriate handlers.

```php
class MessageRouter
{
    public function route(Message $message): Handler
    {
        // 1. Identify contact
        $contact = $this->identifyContact($message);
        
        // 2. Check engagement mode
        $mode = $contact->engagement_mode;
        
        // 3. Route based on mode
        return match($mode) {
            'auto' => new AutoHandler(),
            'manual' => new ManualHandler(),
            'copilot' => new CopilotHandler(),
            'gatekeeper' => new GatekeeperHandler(),
        };
    }
}
```

### 3.2 TaskRouter
Routes tasks to appropriate agents or workflows.

```php
class TaskRouter
{
    public function route(Task $task): Executor
    {
        // 1. Analyze task complexity
        $complexity = $this->analyzeComplexity($task);
        
        // 2. Check required tools
        $tools = $this->requiredTools($task);
        
        // 3. Select executor
        return match(true) {
            $complexity === 'simple' => new SimpleAgent(),
            $complexity === 'complex' => new TeamAgent(),
            count($tools) > 3 => new WorkflowExecutor(),
            default => new AutonomousAgent(),
        };
    }
}
```

### 3.3 ToneRouter
Determines appropriate communication tone.

```php
class ToneRouter
{
    public function determineTone(Contact $contact, Message $message): Tone
    {
        // 1. Check contact-specific tone
        if ($contact->tone_override) {
            return $contact->tone_override;
        }
        
        // 2. Check relationship type tone
        $typeTone = $this->getRelationshipTone($contact->type);
        
        // 3. Adjust for sentiment
        $sentiment = $this->analyzeSentiment($message);
        
        return $this->adjustTone($typeTone, $sentiment);
    }
}
```

### 3.4 MemoryRouter
Routes memory operations to appropriate storage.

```php
class MemoryRouter
{
    public function route(MemoryOperation $op): Storage
    {
        return match($op->type) {
            'working' => new RedisStorage(),
            'episodic' => new MySQLStorage(),
            'semantic' => new VectorStorage(),
            'structured' => new RelationalStorage(),
            'graph' => new GraphStorage(),
        };
    }
}
```

---

## 4. Pipelines Layer

Pipelines are ordered sequences of operations that process data.

### 4.1 Context Assembly Pipeline
Builds the complete context for AI processing.

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Input     │────>│   Filter    │────>│   Enrich    │
│   Message   │     │   Context   │     │   Context   │
└─────────────┘     └─────────────┘     └─────────────┘
                           │                     │
                           ▼                     ▼
                    ┌─────────────┐     ┌─────────────┐
                    │   Inject    │────>│   Output    │
                    │   Memory    │     │   Context   │
                    └─────────────┘     └─────────────┘
```

**Stages:**
1. **Input**: Receive message and metadata
2. **Filter**: Remove irrelevant information
3. **Enrich**: Add contact and conversation context
4. **Inject**: Add memory and rules
5. **Output**: Final context for AI

### 4.2 Memory Extraction Pipeline
Extracts insights from conversations.

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   AI        │────>│   Parse     │────>│   Extract   │
│   Response  │     │   Response  │     │   Facts     │
└─────────────┘     └─────────────┘     └─────────────┘
                           │                     │
                           ▼                     ▼
                    ┌─────────────┐     ┌─────────────┐
                    │   Validate  │────>│   Store     │
                    │   Facts     │     │   Memories  │
                    └─────────────┘     └─────────────┘
```

**Extraction Types:**
- **Beliefs**: Contact's opinions and preferences
- **Facts**: Verifiable information
- **Sentiments**: Emotional states
- **Intents**: Goals and objectives
- **Relationships**: Connections between entities

### 4.3 Response Delivery Pipeline
Prepares and delivers AI responses.

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   AI        │────>│   Format    │────>│   Humanize  │
│   Response  │     │   Response  │     │   Response  │
└─────────────┘     └─────────────┘     └─────────────┘
                           │                     │
                           ▼                     ▼
                    ┌─────────────┐     ┌─────────────┐
                    │   Split     │────>│   Deliver   │
                    │   Message   │     │   Message   │
                    └─────────────┘     └─────────────┘
```

**Delivery Features:**
- **Formatting**: Apply markdown and styling
- **Humanizing**: Add natural language patterns
- **Splitting**: Break long messages
- **Timing**: Add natural delays
- **Delivery**: Send via appropriate channel

---

## 5. Engines Layer

Engines are specialized processing units for specific domains.

### 5.1 Intent & Topic Engine
Understands user intentions and conversation topics.

```php
class IntentTopicEngine
{
    public function analyze(Message $message): Analysis
    {
        // 1. Extract intent
        $intent = $this->extractIntent($message);
        
        // 2. Identify topic
        $topic = $this->identifyTopic($message);
        
        // 3. Check topic drift
        $drift = $this->detectDrift($topic, $message->conversation);
        
        // 4. Return analysis
        return new Analysis($intent, $topic, $drift);
    }
}
```

### 5.2 Persona & Tone Engine
Determines appropriate communication style.

```php
class PersonaToneEngine
{
    public function determine(Contact $contact, Context $context): Persona
    {
        // 1. Get base persona
        $persona = $this->getBasePersona($contact->type);
        
        // 2. Apply contact-specific adjustments
        $persona = $this->applyContactAdjustments($persona, $contact);
        
        // 3. Adjust for current context
        $persona = $this->applyContextAdjustments($persona, $context);
        
        return $persona;
    }
}
```

### 5.3 Memory Management Engine
Manages all memory operations.

```php
class MemoryManagementEngine
{
    public function store(Memory $memory): void
    {
        // 1. Determine memory type
        $type = $this->classifyMemory($memory);
        
        // 2. Route to appropriate storage
        $storage = $this->router->route($type);
        
        // 3. Store memory
        $storage->store($memory);
        
        // 4. Update related memories
        $this->updateRelated($memory);
    }
    
    public function retrieve(Query $query): Collection
    {
        // 1. Parse query
        $parsed = $this->parseQuery($query);
        
        // 2. Search all memory types
        $results = $this->searchAll($parsed);
        
        // 3. Rank by relevance
        return $this->rank($results, $query);
    }
}
```

### 5.4 AI Orchestration Engine
Manages AI model selection and execution.

```php
class AIOrchestrationEngine
{
    public function execute(Request $request): Response
    {
        // 1. Select model based on requirements
        $model = $this->selectModel($request);
        
        // 2. Build prompt with all context
        $prompt = $this->buildPrompt($request);
        
        // 3. Execute with fallback chain
        $response = $this->executeWithFallback($model, $prompt);
        
        // 4. Cache response
        $this->cache($request, $response);
        
        return $response;
    }
}
```

---

## 6. Builders Layer

Builders construct complex objects from components.

### 6.1 Prompt Builder
Constructs AI prompts with layered instructions.

```php
class PromptBuilder
{
    public function build(Context $context): Prompt
    {
        $layers = collect();
        
        // 1. Base system instructions
        $layers->push($this->baseInstructions());
        
        // 2. Contact-specific rules
        $layers->push($this->contactRules($context->contact));
        
        // 3. Conversation context
        $layers->push($this->conversationContext($context->conversation));
        
        // 4. Relevant memories
        $layers->push($this->relevantMemories($context));
        
        // 5. Current message
        $layers->push($context->message);
        
        return new Prompt($layers);
    }
}
```

### 6.2 Profile Assembler Builder
Builds complete contact profiles.

```php
class ProfileAssemblerBuilder
{
    public function assemble(Contact $contact): Profile
    {
        return new Profile([
            'basic_info' => $this->getBasicInfo($contact),
            'rules' => $this->getRules($contact),
            'memories' => $this->getMemories($contact),
            'notes' => $this->getNotes($contact),
            'tags' => $this->getTags($contact),
            'custom_fields' => $this->getCustomFields($contact),
            'analytics' => $this->getAnalytics($contact),
        ]);
    }
}
```

### 6.3 Response Builder
Constructs AI responses with all metadata.

```php
class ResponseBuilder
{
    public function build(AIResponse $response, Context $context): FormattedResponse
    {
        return new FormattedResponse([
            'content' => $response->content,
            'tone' => $context->tone,
            'format' => $this->detectFormat($response->content),
            'metadata' => [
                'model' => $response->model,
                'tokens' => $response->tokens,
                'latency' => $response->latency,
            ],
        ]);
    }
}
```

---

## 7. Data Layer

### 7.1 Database Schema Overview

> Note: `conversation_sessions` is a dedicated Nexus conversation tracking table and is separate from Laravel's built-in `sessions` table.

```
┌─────────────────────────────────────────────────────────────────────┐
│                        DATABASE SCHEMA                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │   contacts   │  │ conversations│  │   messages   │               │
│  │              │  │              │  │              │               │
│  │ • id         │  │ • id         │  │ • id         │               │
│  │ • phone      │  │ • contact_id │  │ • conv_id    │               │
│  │ • name       │  │ • session_id │  │ • sender     │               │
│  │ • type       │  │ • topic_id   │  │ • content    │               │
│  │ • metadata   │  │ • status     │  │ • metadata   │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│           │                │                │                        │
│           │                │                │                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │contact_rules │  │contact_notes │  │contact_tags  │               │
│  │              │  │              │  │              │               │
│  │ • id         │  │ • id         │  │ • id         │               │
│  │ • contact_id │  │ • contact_id │  │ • contact_id │               │
│  │ • rule       │  │ • note       │  │ • tag        │               │
│  │ • priority   │  │ • created_at │  │ • color      │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │  agent_tasks │  │  task_steps  │  │   settings   │               │
│  │              │  │              │  │              │               │
│  │ • id         │  │ • id         │  │ • id         │               │
│  │ • title      │  │ • task_id    │  │ • key        │               │
│  │ • status     │  │ • step       │  │ • value      │               │
│  │ • current    │  │ • content    │  │ • type       │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

### 7.2 Redis Data Structures

```
┌─────────────────────────────────────────────────────────────────────┐
│                        REDIS STRUCTURES                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │   Sessions   │  │    Cache     │  │   Queues     │               │
│  │              │  │              │  │              │               │
│  │ • session:   │  │ • cache:     │  │ • queue:     │               │
│  │   {id}       │  │   {key}      │  │   {name}     │               │
│  │              │  │              │  │              │               │
│  │ TTL: 30min   │  │ TTL: varies  │  │ Persistent   │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐                                  │
│  │  Rate Limit  │  │   Pub/Sub    │                                  │
│  │              │  │              │                                  │
│  │ • rate:      │  │ • channel:   │                                  │
│  │   {user}     │  │   {name}     │                                  │
│  │              │  │              │                                  │
│  │ TTL: 1min    │  │ Real-time    │                                  │
│  └──────────────┘  └──────────────┘                                  │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 8. External Integrations

### 8.1 WAHA Integration
WhatsApp HTTP API for messaging.

```php
class WAHAIntegration
{
    public function receive(Webhook $webhook): Message
    {
        // 1. Validate webhook
        $this->validate($webhook);
        
        // 2. Parse message
        $data = $this->parse($webhook);
        
        // 3. Create message record
        return Message::create($data);
    }
    
    public function send(Message $message): Response
    {
        // 1. Format for WAHA
        $payload = $this->format($message);
        
        // 2. Send via WAHA
        $response = Http::post($this->wahaUrl('/send'), $payload);
        
        // 3. Update message status
        $message->update(['status' => 'sent']);
        
        return $response;
    }
}
```



---

## 9. Security Architecture

### 9.1 Authentication Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Login     │────>│  Validate   │────>│  Generate   │
│   Request   │     │ Credentials │     │    Token    │
└─────────────┘     └─────────────┘     └─────────────┘
                           │                     │
                           ▼                     ▼
                    ┌─────────────┐     ┌─────────────┐
                    │   Store     │────>│   Return    │
                    │   Session   │     │    Token    │
                    └─────────────┘     └─────────────┘
```

### 9.2 Authorization Layers

1. **Authentication**: Verify user identity
2. **Authorization**: Check permissions
3. **Rate Limiting**: Prevent abuse
4. **Input Validation**: Sanitize input
5. **Output Encoding**: Prevent XSS

---

## 10. Deployment Architecture

### 10.1 Infrastructure Components

```
┌─────────────────────────────────────────────────────────────────────┐
│                      DEPLOYMENT ARCHITECTURE                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │   Web        │  │   Queue      │  │   Cache      │               │
│  │   Server     │  │   Worker     │  │   Server     │               │
│  │              │  │              │  │              │               │
│  │ • Nginx      │  │ • Horizon    │  │ • Redis      │               │
│  │ • PHP-FPM    │  │ • Workers    │  │ • Cluster    │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│           │                │                │                        │
│           └────────────────┴────────────────┘                        │
│                            │                                         │
│                            ▼                                         │
│                    ┌─────────────┐                                   │
│                    │   Database  │                                   │
│                    │   MySQL     │                                   │
│                    └─────────────┘                                   │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

### 10.2 Scaling Strategy

1. **Horizontal Scaling**: Add more web servers
2. **Queue Scaling**: Add more workers
3. **Database Scaling**: Read replicas
4. **Cache Scaling**: Redis cluster
5. **CDN**: Static asset delivery
