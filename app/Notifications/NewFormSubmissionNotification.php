<?php

namespace App\Notifications;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFormSubmissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Form $form,
        protected FormSubmission $submission
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the queues for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => (string) config('dotforms.queues.notifications', 'notifications'),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $viewUrl = route('forms.public', ['slug' => $this->form->slug]);

        return (new MailMessage)
            ->subject('New submission for '.$this->form->title)
            ->line('Your form has received a new submission.')
            ->line('Form: '.$this->form->title)
            ->line('Submission ID: #'.$this->submission->id)
            ->line('Submitted At: '.optional($this->submission->submitted_at)?->toDateTimeString())
            ->action('View Public Form', $viewUrl);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'form_id' => $this->form->id,
            'submission_id' => $this->submission->id,
        ];
    }
}
