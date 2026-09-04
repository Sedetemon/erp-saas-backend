<?php

namespace App\Notifications;

use App\Modules\Messaging\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Détermine les canaux de notification.
     */
    public function via($notifiable)
    {
        // On peut retourner ['mail', 'database'] ou seulement ['database'] selon les besoins.
        // Pour l'instant, nous activons les deux.
        return ['mail', 'database'];
    }

    /**
     * Notification par email.
     */
    public function toMail($notifiable)
    {
        $conversation = $this->message->conversation;
        $sender = $this->message->sender;

        // Générer une URL pour voir la conversation (à adapter selon votre frontend)
        $url = url('/messages?conversation=' . $conversation->id);

        return (new MailMessage)
            ->subject('Nouveau message de ' . $sender->name)
            ->line('Vous avez reçu un nouveau message dans la conversation : ' . $conversation->subject)
            ->line($this->message->content)
            ->action('Voir le message', $url)
            ->line('Merci d\'utiliser notre plateforme !');
    }

    /**
     * Notification en base de données.
     */
    public function toDatabase($notifiable)
    {
        $sender = $this->message->sender;

        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'content' => $this->message->content,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }

    /**
     * Notification pour les canaux push (Firebase, etc.)
     * On peut le préparer même si on ne l'implémente pas encore.
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Nouveau message de ' . $this->message->sender->name,
            'body' => $this->message->content,
            'data' => [
                'conversation_id' => $this->message->conversation_id,
                'message_id' => $this->message->id,
            ],
        ];
    }
}
