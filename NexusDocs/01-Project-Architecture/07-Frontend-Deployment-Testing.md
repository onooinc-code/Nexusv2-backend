# Frontend Deployment & Testing

## 1. Deployment Strategy

### Vercel (Preferred)
- **Environment**: Automated deployments via GitHub integration.
- **Build Command**: `npm run build`.
- **Environment Variables**: Configure in Vercel Dashboard (API URLs, Reverb Keys).

### Docker (Self-Hosted)
- **Base Image**: `node:20-alpine`.
- **Multi-stage Build**:
  1. Build: `npm run build`.
  2. Runner: Lightweight node server or `next start`.

## 2. Testing Strategy

### Unit Testing
- **Framework**: Vitest + React Testing Library.
- **Scope**: Utility functions, individual `Nx` components, and Zustand store logic.

### End-to-End (E2E) Testing
- **Framework**: Playwright.
- **Critical Paths**:
  - User Authentication flow.
  - Agent Chat interaction.
  - Real-time notification arrival.
  - Contact 3D card flipping and data integrity.

### Performance Testing
- **Lighthouse**: Integrated into CI/CD to ensure >90 score on Accessibility and Best Practices.
- **Bundle Analysis**: `next-bundle-analyzer` to monitor dependency impact.