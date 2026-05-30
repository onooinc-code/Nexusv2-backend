# Nexus Next.js Migration Architecture

## Overview
This document outlines the architectural strategy for migrating the Nexus UI from Vue/Laravel-Vite to a standalone Next.js 14+ application.

## 1. Tech Stack
- **Framework**: Next.js 14 (App Router)
- **Styling**: Tailwind CSS
- **State Management**: Zustand (Global HUD, System State)
- **Real-time**: Laravel Echo + Pusher/Reverb
- **Icons**: Lucide React
- **Charts**: Apache ECharts (React-ECharts)

## 2. Directory Structure
```text
src/
├── app/                  # App Router pages
├── components/           # React components
│   ├── ui/               # Atomic Shadcn-like components
│   ├── layout/           # Shell, Nav, StatusBar
│   ├── modules/          # Feature-specific components (Chat, Contacts)
├── hooks/                # Custom React hooks
├── lib/                  # Utilities, API clients
├── store/                # Zustand stores
└── types/                # TypeScript definitions
```

## 3. Component Strategy
Components follow the `Nx` prefix convention. P1 components are prioritized for initial migration to establish the "System HUD" aesthetic.

## 4. State & Real-time
- **System HUD**: Managed via `useSystemStore` (Zustand).
- **Events**: Listeners for `TokenStreamed`, `JobProgressUpdated`, and `NotificationReceived` are initialized in a global `SystemProvider`.