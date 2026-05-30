# NxApiKeyModal

## Specification
- **File**: `components/modals/NxApiKeyModal.tsx`
- **Security**: Masked input with auto-hide timer.

## Features
- **Key Management**: List of configured AI providers and their respective keys.
- **Masking**: Keys are displayed as `••••••••` by default.
- **Reveal Toggle**: Clicking the "eye" icon reveals the key for 30 seconds before auto-masking.
- **Copy**: One-click copy to clipboard with feedback tooltip.
- **Usage Stats**: Brief summary of token usage per key directly in the modal.