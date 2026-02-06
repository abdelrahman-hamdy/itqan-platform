<?php

namespace App\Console\Commands;

use App\Models\Academy;
use App\Models\User;
use App\Notifications\TeacherAccountActivatedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendTestTeacherActivationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-teacher-activation {email : The email address to send the test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test teacher account activation email to verify the template';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info("Sending test teacher activation email to: {$email}");

        // Get the first academy for testing
        $academy = Academy::first();

        if (! $academy) {
            $this->error('No academy found in the database. Please seed the database first.');

            return self::FAILURE;
        }

        // Create a fake user object for the notification
        $testUser = new User([
            'first_name' => 'معلم',
            'last_name' => 'تجريبي',
            'email' => $email,
            'academy_id' => $academy->id,
        ]);

        // We need to use Notification facade to send to an anonymous notifiable
        // since we don't want to create a real user in the database
        Notification::route('mail', $email)
            ->notify(new class($academy, $testUser) extends TeacherAccountActivatedNotification
            {
                private User $testUser;

                public function __construct(Academy $academy, User $testUser)
                {
                    parent::__construct($academy);
                    $this->testUser = $testUser;
                }

                public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
                {
                    $loginUrl = $this->getLoginUrl();

                    return (new \Illuminate\Notifications\Messages\MailMessage)
                        ->subject('تم تفعيل حسابك - '.$this->academy->name)
                        ->view('emails.teacher-account-activated', [
                            'user' => $this->testUser,
                            'academy' => $this->academy,
                            'loginUrl' => $loginUrl,
                            'subject' => 'تم تفعيل حسابك - '.$this->academy->name,
                        ]);
                }
            });

        $this->info('Test email sent successfully!');
        $this->info("Academy used: {$academy->name}");
        $this->info('Login URL: '.route('login', ['subdomain' => $academy->subdomain]));

        return self::SUCCESS;
    }
}
