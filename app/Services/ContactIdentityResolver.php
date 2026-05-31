<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactIdentifier;
use App\Models\ContactAlias;
use Illuminate\Support\Facades\DB;

class ContactIdentityResolver
{
    /**
     * Resolve a contact based on a set of identifiers.
     *
     * @param array $identifiers Array of ['type' => string, 'value' => string]
     * @return Contact|null
     */
    public function resolve(array $identifiers): ?Contact
    {
        $candidates = array_filter($identifiers, function ($identifier) {
            return !empty($identifier['type']) && !empty($identifier['value']);
        });

        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $identifier) {
            $normalizedValue = $this->normalizeValue($identifier['type'], $identifier['value']);

            // 1. Try to resolve via exact ContactIdentifier match
            $contactIdentifier = ContactIdentifier::where('type', $identifier['type'])
                ->where('value', $normalizedValue)
                ->first();

            if ($contactIdentifier && $contactIdentifier->contact) {
                return $contactIdentifier->contact;
            }

            // 2. Try to resolve via Contact direct fields (email, phone)
            if ($identifier['type'] === ContactIdentifier::TYPE_EMAIL) {
                $contact = Contact::where('email', $normalizedValue)->first();
                if ($contact) {
                    return $contact;
                }
            }

            if ($identifier['type'] === ContactIdentifier::TYPE_PHONE) {
                $contact = Contact::where('phone', $normalizedValue)
                    ->orWhere('whatsapp_number', $normalizedValue)
                    ->first();
                if ($contact) {
                    return $contact;
                }
            }
        }

        // 3. Fallback: try to resolve via Alias names
        foreach ($candidates as $identifier) {
            if ($identifier['type'] === 'name') {
                $alias = ContactAlias::where('name', $identifier['value'])->first();
                if ($alias && $alias->contact) {
                    return $alias->contact;
                }
            }
        }

        return null;
    }

    /**
     * Link an identifier to a contact, ensuring uniqueness.
     */
    public function linkIdentifier(Contact $contact, string $type, string $value, bool $isPrimary = false): ContactIdentifier
    {
        $normalizedValue = $this->normalizeValue($type, $value);

        return DB::transaction(function () use ($contact, $type, $normalizedValue, $isPrimary) {
            if ($isPrimary) {
                ContactIdentifier::where('contact_id', $contact->id)
                    ->where('type', $type)
                    ->update(['is_primary' => false]);
            }

            return ContactIdentifier::updateOrCreate(
                [
                    'type' => $type,
                    'value' => $normalizedValue,
                ],
                [
                    'contact_id' => $contact->id,
                    'is_primary' => $isPrimary,
                ]
            );
        });
    }

    /**
     * Normalize values based on type (e.g. phone, email).
     */
    public function normalizeValue(string $type, string $value): string
    {
        return ContactIdentifier::normalize($type, $value);
    }
}
