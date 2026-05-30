# nexus

Nexus Laravel application.

## Setup

- Copy `.env.example` to `.env`
- Run `composer install`
- Run `npm install`
- Run `php artisan key:generate`

## UI Polish & Performance

- Added route-based page transitions with a smooth slide/fade animation
- Standardized loading states using shared loader components and skeleton placeholders
- Improved hover effects, button press feedback, and micro-interactions across the UI
- Added mobile haptic feedback with safe `navigator.vibrate` detection
- Lazy-loaded analytics and provider onboarding components for better performance
- Updated documentation and verification notes for final polish deployment

## License

MIT
