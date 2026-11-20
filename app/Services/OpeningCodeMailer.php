<?php

namespace App\Services;

use App\Models\TenderReviewOtp;
use App\Notifications\TenderOpeningOtpNotification;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Issues a code holder their one-time opening code and records what the mail server said about
 * it, so the person collecting codes can see which holders were actually reachable.
 *
 * Both the opening button (TenderController::openBidding) and the resend button
 * (TenderReviewController::resendOpeningOtp) come through here, so a code is generated, mailed
 * and reported on in exactly one way.
 *
 * WHAT A STATUS HERE MEANS, AND WHAT IT DOES NOT
 *
 *   accepted    The relay took the message and gave it a queue id. That is the strongest thing
 *               SMTP can tell us in-band, and it is NOT delivery: a relay may accept an address
 *               and bounce it asynchronously afterwards, which is what Gmail does for an
 *               address whose domain resolves but has no mailbox behind it. The bounce arrives
 *               at the MAIL_FROM mailbox minutes later and is invisible from in here.
 *   rejected    The relay refused the message during the conversation - a bad recipient it
 *               knows about, a sender it will not relay for, a rejected login, a dead host.
 *               This one is conclusive: nothing was sent.
 *   error       The send failed before or outside the SMTP conversation.
 *   no-address  The holder has no email address at all, so only their in-app notification
 *               carries the code.
 */
class OpeningCodeMailer
{
    /**
     * Correlates a sent message back to the row that asked for it. A registered listener on
     * MessageSent (see AppServiceProvider) reads this header rather than guessing by recipient,
     * which keeps working if these notifications are ever queued.
     */
    public const ROW_HEADER = 'X-Vms-Code-Row';

    /** Minutes a freshly issued code stays usable. */
    public const TTL_MINUTES = 30;

    /**
     * Generates a fresh code for this holder, mails it, and records the outcome on the row.
     *
     * Returns ['ok', 'status', 'detail'] - 'ok' being true only for a relay that accepted it.
     */
    public function issue(TenderReviewOtp $row, ?string $openerName = null): array
    {
        $code      = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        $row->update([
            'otp_code'          => $code,
            'otp_expires_at'    => $expiresAt,
            'otp_sent_at'       => now(),
            'otp_verified_at'   => null,
            'otp_collected_by'  => null,
            'mail_status'       => null,
            'mail_response'     => null,
            'mail_message_id'   => null,
            'mail_attempted_at' => now(),
        ]);

        $holder = $row->committeeMember;

        if (!$holder) {
            return $this->record($row, 'error', 'This code holder\'s user account no longer exists.');
        }

        if (trim((string) $holder->email) === '') {
            return $this->record($row, 'no-address',
                'No email address on this account, so the code is only in their login notifications.');
        }

        try {
            $holder->notify(new TenderOpeningOtpNotification(
                $row->tender ?: $row->tender()->first(), $code, $expiresAt, $openerName, $row->id
            ));
        } catch (TransportExceptionInterface $e) {
            // The relay said no, during the conversation. Its own words are the useful part.
            $said = $this->serverReplies($e->getDebug()) ?: $e->getMessage();
            Log::error("Opening code to {$holder->email} was refused by the mail server: " . $said);

            return $this->record($row, 'rejected', $said);
        } catch (\Throwable $e) {
            Log::error("Opening code to {$holder->email} failed: " . $e->getMessage());

            return $this->record($row, 'error', $e->getMessage());
        }

        // The MessageSent listener will have written the relay's reply by now. If something
        // swallowed the event, the send still succeeded - say so without inventing a reply.
        $row->refresh();

        if ($row->mail_status === null) {
            return $this->record($row, 'accepted', 'Sent. The mail server returned no reply we could record.');
        }

        return [
            'ok'     => $row->mail_status === 'accepted',
            'status' => $row->mail_status,
            'detail' => (string) $row->mail_response,
        ];
    }

    /**
     * Called by the MessageSent listener. Keeps the relay's closing reply and its queue id.
     */
    public function recordAccepted(int $rowId, string $debug, ?string $messageId): void
    {
        $row = TenderReviewOtp::find($rowId);

        if (!$row) {
            return;
        }

        $row->update([
            'mail_status'     => 'accepted',
            'mail_response'   => $this->scrub($this->serverReplies($debug, 1) ?: 'Accepted by the mail server.'),
            'mail_message_id' => $messageId ? substr($messageId, 0, 255) : null,
        ]);
    }

    /** Writes an outcome onto the row and hands back the same shape issue() returns. */
    private function record(TenderReviewOtp $row, string $status, string $detail): array
    {
        $detail = $this->scrub($detail);

        $row->update(['mail_status' => $status, 'mail_response' => $detail]);

        return ['ok' => $status === 'accepted', 'status' => $status, 'detail' => $detail];
    }

    /**
     * Pulls the mail server's own reply lines out of an SMTP transcript, most recent last.
     *
     * Only the server's half is kept. Our half is dropped on purpose: the transcript of an
     * authenticated send contains the AUTH LOGIN exchange, and the line after the password
     * challenge is MAIL_PASSWORD in base64. Keeping only the "<" lines means a credential
     * cannot reach the database or a toast through this path at all. scrub() is the second
     * line of defence, for a reply that quotes back something we sent.
     */
    private function serverReplies(string $debug, int $keep = 3): string
    {
        $replies = [];

        foreach (preg_split('/\r?\n/', $debug) as $line) {
            // "[2026-11-19T09:12:04.123456+00:00] < 250 2.0.0 OK 17... - gsmtp"
            if (preg_match('/^\[[^\]]*\]\s*<\s*(\d{3}[ -].*)$/', trim($line), $m)) {
                $replies[] = trim($m[1]);
            }
        }

        if ($replies === []) {
            return '';
        }

        // The interesting reply is the last one - the 250 that closed a successful send, or the
        // 5xx that ended a refused one. A refusal is sometimes continued over several lines.
        return implode(' ', array_slice($replies, -$keep));
    }

    /**
     * Last-resort redaction. Nothing reaching here should contain a credential, but this text is
     * stored and shown on screen, so the mail password and username are removed if they somehow
     * appear, and the result is trimmed to a length a toast can carry.
     */
    private function scrub(string $text): string
    {
        $secrets = array_filter([
            (string) config('mail.mailers.smtp.password'),
            (string) config('mail.mailers.smtp.username'),
        ], fn($s) => trim($s) !== '');

        foreach ($secrets as $secret) {
            $text = str_ireplace([$secret, base64_encode($secret)], '[redacted]', $text);
        }

        // Any lone base64-looking blob long enough to be a credential goes too.
        $text = preg_replace('/\b[A-Za-z0-9+\/]{24,}={0,2}\b/', '[redacted]', (string) $text);

        return trim(mb_substr(preg_replace('/\s+/', ' ', (string) $text), 0, 500));
    }
}
