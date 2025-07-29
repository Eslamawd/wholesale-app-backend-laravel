<?php
namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends BaseVerifyEmail
{


    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     *
     */

    public $notifiable;

    protected function verificationUrl($notifiable)
    {

          $id = $notifiable->getKey(); // ID المستخدم
          $hash = sha1($notifiable->getEmailForVerification()); // Hash من الإيميل
          $email = $notifiable->getEmailForVerification();


    \Log::info('Building verification URL', compact('id', 'email', 'hash'));
    \Log::info('Building verification URL', compact('notifiable'));
        // 1. الرابط المؤقت الموقّع من Laravel
        $temporarySignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $id,
                'hash' => $hash,
            ]
        );

        // 2. استخراج البارامترات من الرابط
        
    // نجيب فقط الـ query من الرابط:
    $query = parse_url($temporarySignedUrl, PHP_URL_QUERY);

    $frontend = config('app.frontend_url', 'http://localhost:5174');

    $fullUrl = $frontend . '/verify-email/' . $id . '/' . $hash . '?' . $query;

    \Log::info('Building verification URL', compact('fullUrl'));

    return $fullUrl;

    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Click the button below to verify your email address.')
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not create an account, no further action is required.');
    }
}
