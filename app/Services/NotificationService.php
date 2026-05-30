<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification via the specified channel.
     */
    public function send(NotificationLog $notification): bool
    {
        $channel = $notification->channel;

        try {
            $success = match ($channel) {
                NotificationTemplate::CHANNEL_EMAIL => $this->sendEmail($notification),
                NotificationTemplate::CHANNEL_SMS => $this->sendSms($notification),
                NotificationTemplate::CHANNEL_WHATSAPP => $this->sendWhatsApp($notification),
                NotificationTemplate::CHANNEL_PUSH => $this->sendPush($notification),
                default => throw new \InvalidArgumentException("Unknown channel: {$channel}"),
            };

            if ($success) {
                $notification->markSent();
                return true;
            }

            $notification->markFailed('Failed to send notification');
            return false;
        } catch (\Exception $e) {
            $notification->markFailed($e->getMessage());
            Log::error('Notification failed', [
                'notification_id' => $notification->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(NotificationLog $notification): bool
    {
        // TODO: Implement actual email sending
        // Mail::to($notification->recipient)
        //     ->subject($notification->subject ?? 'Notification')
        //     ->send(new NotificationMail($notification->body));

        // For now, simulate success
        return true;
    }

    /**
     * Send SMS notification.
     */
    protected function sendSms(NotificationLog $notification): bool
    {
        // TODO: Implement actual SMS sending via Twilio or similar
        // $twilio = new TwilioClient();
        // $twilio->sendSms($notification->recipient, $notification->body);

        // For now, simulate success
        return true;
    }

    /**
     * Send WhatsApp notification.
     */
    protected function sendWhatsApp(NotificationLog $notification): bool
    {
        // TODO: Implement actual WhatsApp sending via WAHA or similar
        // Http::post(config('services.waha.url') . '/api/send', [
        //     'to' => $notification->recipient,
        //     'body' => $notification->body,
        // ]);

        // For now, simulate success
        return true;
    }

    /**
     * Send push notification.
     */
    protected function sendPush(NotificationLog $notification): bool
    {
        // TODO: Implement actual push notification via Firebase or similar
        // $firebase = new FirebaseClient();
        // $firebase->send($notification->recipient, $notification->body);

        // For now, simulate success
        return true;
    }

    /**
     * Send notification to a contact using a template.
     */
    public function sendToContact(
        Contact $contact,
        string $templateKey,
        array $variables = [],
        string $channel = null
    ): NotificationLog {
        $template = NotificationTemplate::where('key', $templateKey)->firstOrFail();

        $body = $template->render($variables);
        $subject = $template->subject ? $template->renderSubject($variables) : null;

        // Determine channel
        $channel = $channel ?? $template->channels[0] ?? NotificationTemplate::CHANNEL_EMAIL;

        // Get recipient from contact
        $recipient = $this->getContactRecipient($contact, $channel);

        $notification = NotificationLog::create([
            'contact_id' => $contact->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'template_key' => $templateKey,
            'subject' => $subject,
            'body' => $body,
            'payload' => $variables,
            'status' => NotificationLog::STATUS_PENDING,
        ]);

        $this->send($notification);

        return $notification;
    }

    /**
     * Get the appropriate recipient for a contact based on channel.
     */
    protected function getContactRecipient(Contact $contact, string $channel): string
    {
        return match ($channel) {
            NotificationTemplate::CHANNEL_EMAIL => $contact->email ?? '',
            NotificationTemplate::CHANNEL_SMS => $contact->phone ?? '',
            NotificationTemplate::CHANNEL_WHATSAPP => $contact->phone ?? '',
            NotificationTemplate::CHANNEL_PUSH => $contact->email ?? '',
            default => '',
        };
    }
}