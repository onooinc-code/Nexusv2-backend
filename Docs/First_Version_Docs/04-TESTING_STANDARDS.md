# 04 - Testing Standards

## Purpose
Establish uniform testing criteria and processes for Nexus to ensure reliability and early bug detection.

---

## 1. Test Types
- **Unit tests**: business logic, services, routers, engines
- **Integration tests**: hub interactions, event flows, database operations
- **End-to-end tests**: user flows and UI journeys
- **Regression tests**: critical workflows and edge cases

## 2. Test Coverage
- Aim for 90% coverage in backend modules
- Ensure critical path features are fully tested
- Track coverage per hub and per feature group

## 3. Test Structure
- Use descriptive test names
- Arrange tests in `Arrange / Act / Assert`
- Use fixtures and factories for repeatable data
- Keep tests small and focused

## 4. Backend Testing Standards
- Use Pest for Laravel unit and integration tests
- Mock external API providers where possible
- Use in-memory or temporary databases for speed
- Reset state between tests

## 5. Frontend Testing Standards
- Use Vitest for component and composable tests
- Use Vue Testing Library for interaction tests
- Prefer behaviour-driven test cases
- Use visual regression for key UI screens

## 6. CI Testing Requirements
- Run all unit tests on every pull request
- Run integration tests on feature/merge branches
- Run E2E tests on pre-release or nightly builds
- Fail fast on test errors

## 7. Quality Metrics
- No new feature is merged without tests
- All bug fixes include regression coverage
- Document uncovered edge cases in issue tracker
