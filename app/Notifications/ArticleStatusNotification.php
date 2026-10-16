<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Editorial status notification (PRD §15.4; P2-T03.4).
 *
 * Delivered synchronously (no ShouldQueue): P1's `sendOnce()` dedupe proof
 * covers synchronous redelivery, and admin volumes need no queue. Carries a
 * stable `dedupe_key` so repeat transitions collapse while unread.
 */
class ArticleStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $event,
        public readonly int $articleId,
        public readonly string $articleTitle,
        public readonly string $dedupeKey,
        public readonly ?string $note = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $email = $notifiable instanceof User ? $notifiable->email_notifications : false;

        return $email ? ['mail', 'database'] : ['database'];
    }

    /**
     * @return array<string, string|int>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message(),
            'event' => $this->event,
            'article_id' => $this->articleId,
            'note' => $this->note ?? '',
            'dedupe_key' => $this->dedupeKey,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->message())->line($this->message());
    }

    private function message(): string
    {
        $title = '“'.$this->articleTitle.'”';

        return match ($this->event) {
            'submitted' => "Article submitted for review: {$title}.",
            'rejected' => "Article sent back to draft: {$title}.",
            'published' => "Article published: {$title}.",
            'unpublished' => "Article unpublished: {$title}.",
            default => "Article update: {$title}.",
        };
    }
}
