# Testing Guide

## Setup
- Run `npm install` then `npm run dev` for hot reload.
- Tests use Vitest + Vue Test Utils for unit tests.
- Feature tests use PHPUnit via `php artisan test`.

## Running Tests
```bash
# Frontend unit tests
npm run test:unit

# Frontend e2e (if Playwright configured)
npm run test:e2e

# Backend feature tests
php artisan test
```

## Unit Test Examples
```js
import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/vue';
import NxActionButton from '@/Components/NxActionButton.vue';

describe('NxActionButton', () => {
  it('renders with default slot', () => {
    const { getByText } = render(NxActionButton, { slots: { default: 'Click' } });
    expect(getByText('Click')).toBeTruthy();
  });
});
```

## Feature Test Examples
```php
// tests/Feature/ContactsTest.php
public function test_can_list_contacts()
{
    $response = $this->getJson('/api/contacts');
    $response->assertStatus(200)
             ->assertJsonStructure(['data' => [['id','name']]]);
}
```

## Store Test Examples
```js
import { setActivePinia, createPinia } from 'pinia';
import { describe, it, beforeEach } from 'vitest';
import { useContacts } from '@/stores/useContacts';

describe('useContacts', () => {
  beforeEach(() => setActivePinia(createPinia()));
  it('creates contact', async () => {
    const store = useContacts();
    await store.createContact({ name: 'Test' });
    expect(store.contacts).toHaveLength(1);
  });
});
```

## Mocking
- Mock `apiClient` with `vi.fn()` for service tests.
- Mock Echo channel with `vi.spyOn`.
- Use `Http::fake()` for Laravel API tests.

## Coverage Targets
- >80% component coverage.
- >70% store coverage.
- 100% critical path tests.