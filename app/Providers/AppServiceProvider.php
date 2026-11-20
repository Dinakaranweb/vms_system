<?php

namespace App\Providers;

use App\Services\OpeningCodeMailer;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->recordOpeningCodeMailReplies();
    }

    /**
     * Keeps the mail server's closing reply for an opening code against the row that asked
     * for it, so the opening screen can say which holders the relay actually took.
     *
     * A failed send throws instead of firing this event, and OpeningCodeMailer catches that
     * itself - this listener only ever sees successes. It is registered here rather than
     * inside the service because the event fires after the service has handed control to the
     * mailer, and matching on the header rather than the recipient keeps it correct if these
     * notifications are ever queued.
     */
    private function recordOpeningCodeMailReplies(): void
    {
        Event::listen(function (MessageSent $event) {
            $header = $event->message->getHeaders()->get(OpeningCodeMailer::ROW_HEADER);

            if (!$header) {
                return;
            }

            $rowId = (int) trim($header->getBodyAsString());

            if ($rowId <= 0) {
                return;
            }

            try {
                app(OpeningCodeMailer::class)->recordAccepted(
                    $rowId,
                    $event->sent->getSymfonySentMessage()->getDebug(),
                    $event->sent->getSymfonySentMessage()->getMessageId() ?: null
                );
            } catch (\Throwable $e) {
                // Reporting on a sent mail must never be able to undo the send itself.
                \Log::warning('Could not record the mail reply for opening code row ' . $rowId . ': ' . $e->getMessage());
            }
        });
    }
}
