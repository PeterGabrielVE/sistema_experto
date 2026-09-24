<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a new user their account exists. The password is never sent by mail:
 * the administrator gives it in person, or the user sets one with "¿Olvidaste Contraseña?".
 */
class AccountCreatedNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Su cuenta en :app fue creada', ['app' => config('app.name')]))
            ->greeting(__('Hola :name', ['name' => $notifiable->name]))
            ->line(__('Un administrador creó su cuenta con el rol :role.', [
                'role' => $notifiable->role()?->label() ?? __('sin rol'),
            ]))
            ->line(__('Usuario: :email', ['email' => $notifiable->email]))
            ->action(__('Ingresar'), route('login'))
            ->line(__('Si no conoce su contraseña, use "¿Olvidaste Contraseña?" en la pantalla de acceso.'));
    }
}
