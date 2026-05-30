# Next.js Coding Standards

## 1. Server vs. Client Components
- **Default to Server Components**: Use Server Components (`src/app`) for data fetching and static layout.
- **Client Components**: Use `"use client"` only for:
  - Interactive UI (buttons, forms, charts).
  - Using browser APIs (localStorage, window).
  - Using hooks (`useState`, `useEffect`, `useContext`).
  - Real-time Echo listeners.

## 2. Data Fetching
- **Server Side**: Use `fetch()` with Next.js caching tags in Server Components.
- **Client Side**: Use **React Query** (TanStack Query) for mutations and complex client-side state fetching.
- **API Routes**: Implement route handlers in `src/app/api/` for proxying requests to the Laravel backend if needed for CORS or secret masking.

## 3. State Management
- **Global UI State**: Use **Zustand**. Keep stores small and focused (e.g., `useSystemStore`, `useChatStore`).
- **Form State**: Use **React Hook Form** with Zod validation.

## 4. File Naming
- Components: PascalCase (`NxStatusBar.tsx`).
- Hooks: camelCase (`useEcho.ts`).
- Utilities: camelCase (`formatDate.ts`).
- Styles: Tailwind classes only; avoid CSS modules unless necessary for complex 3D animations.