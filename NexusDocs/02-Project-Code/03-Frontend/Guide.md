# Frontend Developer Guide

## Component Development
All components must follow the Nexus UI design language:
- **Glassmorphism**: Use `backdrop-blur-md` and semi-transparent backgrounds.
- **Animations**: Use Framer Motion for complex transitions; Tailwind for simple ones.
- **Prefix**: Use `Nx` prefix for all shared UI components.

## State Management
- Use **Zustand** for global UI state.
- Use **React Query** (TanStack Query) for server-state and API caching.

## Real-time Integration
Integration with Laravel Reverb/Pusher is handled via the `useEcho` hook.