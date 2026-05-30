# API Contracts & Data Structures

## Overview
This document defines the expected JSON structures from the Laravel backend to ensure frontend type safety.

## 1. Task / Job Object
```json
{
  "id": "uuid",
  "name": "string",
  "status": "pending | processing | completed | failed",
  "progress": 0-100,
  "attempts": "number",
  "payload": "object",
  "error": "string | null"
}
```

## 2. Agent Thought Step
```json
{
  "trace_id": "uuid",
  "type": "thinking | tool-call | observation | response",
  "content": "string",
  "metadata": {
    "tool_name": "string?",
    "duration": "ms"
  }
}
```

## 3. Contact Profile
```json
{
  "id": "uuid",
  "name": "string",
  "avatar_url": "string",
  "emotion_baseline": {
    "joy": 0.8,
    "trust": 0.6,
    "anticipation": 0.7,
    "surprise": 0.2,
    "sadness": 0.1,
    "anger": 0.05
  },
  "engagement_score": 85
}