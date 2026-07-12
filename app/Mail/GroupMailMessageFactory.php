<?php

namespace App\Mail;

use App\Enums\UserApprovalStatus;
use App\Models\User;
use App\Settings\GroupProfileSettings;
use Illuminate\Notifications\Messages\MailMessage;

class GroupMailMessageFactory
{
    public function __construct(private readonly GroupProfileSettings $groupProfileSettings) {}

    public function verification(string $url): MailMessage
    {
        return $this->message()
            ->subject('Verify your email address')
            ->greeting('Welcome to '.$this->groupProfileSettings->group_short_name)
            ->line('Please confirm your email address so we can keep your account secure.')
            ->action('Verify email address', $url)
            ->line('If you did not create an account, no further action is required.');
    }

    public function passwordReset(string $url): MailMessage
    {
        return $this->message()
            ->subject('Reset your password')
            ->greeting('Reset your password')
            ->line('We received a password reset request for your account.')
            ->action('Reset password', $url)
            ->line('This password reset link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }

    public function leaderRegistrationSubmitted(User $applicant, string $url): MailMessage
    {
        return $this->message()
            ->subject('Leader account awaiting approval')
            ->greeting('A leader account needs review')
            ->line($applicant->name.' ('.$applicant->email.') has registered for leader access.')
            ->action('Review leader accounts', $url)
            ->line('Approve the account only after confirming the applicant is a current group volunteer.');
    }

    public function leaderRegistrationDecision(UserApprovalStatus $status, string $url): MailMessage
    {
        $approved = $status === UserApprovalStatus::APPROVED;

        return $this->message()
            ->subject($approved ? 'Your leader account has been approved' : 'Update on your leader account')
            ->greeting($approved ? 'Your account is ready' : 'Your account request was not approved')
            ->line($approved
                ? 'You can now sign in and use the leader tools.'
                : 'An administrator has reviewed your request and has not approved access at this time.')
            ->action($approved ? 'Open leader tools' : 'View account status', $url);
    }

    private function message(): MailMessage
    {
        return (new MailMessage)
            ->theme('scouts')
            ->from($this->groupProfileSettings->mail_from_address, $this->groupProfileSettings->mail_from_name)
            ->salutation('Regards,'.PHP_EOL.$this->groupProfileSettings->group_short_name);
    }
}
