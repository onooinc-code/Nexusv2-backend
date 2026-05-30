# API Documentation for Laravel Backend

## Base URL
https://os.square-ltd.com/ns

## Authentication
This API uses Laravel Sanctum for authentication.

### Obtaining an API Token
To authenticate, include a valid API token in the Authorization header of your requests.

#### Example Token (for user ID 6)
```
8|JiKCceQ7kbNU6SQ8uoFSHqh8AnPNEO4yfdggjlGt79e26d29
```

### Header Format
```
Authorization: Bearer <your-token-here>
```

#### Example with the above token:
```
Authorization: Bearer 8|JiKCceQ7kbNU6SQ8uoFSHqh8AnPNEO4yfdggjlGt79e26d29
```

## NextJS Frontend Integration
For your NextJS frontend project, you will need to:

1. Store the API token securely (e.g., in an environment variable or secure storage).
2. Set the base URL for API requests: `https://os.square-ltd.com/ns`
3. Include the Authorization header in every request that requires authentication.

### Example using fetch in NextJS:
```javascript
const API_BASE_URL = 'https://os.square-ltd.com/ns';
const API_TOKEN = '8|JiKCceQ7kbNU6SQ8uoFSHqh8AnPNEO4yfdggjlGt79e26d29'; 

const fetchFromAPI = async (endpoint, options = {}) => {
  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers: {
      'Authorization': `Bearer ${API_TOKEN}`,
      'Content-Type': 'application/json',
      ...options.headers,
    },
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.status}`);
  }

  return response.json();
};
```

## Important Notes
- This token is associated with user ID 6 in the system.
- For production use, ensure tokens are generated and managed securely.
- Tokens can be revoked or regenerated as needed via the Laravel Sanctum API.
- Always use HTTPS in production to protect token transmission.

## Token Generation (for reference)
To generate a new token for a user, you can use Laravel Tinker:
```bash
php artisan tinker
$user = App\\Models\\User::find(<user-id>);
$token = $user->createToken('token-name')->plainTextToken;
echo $token;
```
