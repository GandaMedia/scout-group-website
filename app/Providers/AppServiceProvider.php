<?php

namespace App\Providers;

use App\Mail\GroupMailMessageFactory;
use DavidBadura\FakerMarkdownGenerator\FakerProvider as MarkdownFakerProvider;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind a single Faker instance app-wide and add the Markdown provider
        $this->app->singleton(FakerGenerator::class, function ($app): FakerGenerator {
            $locale = (string) config('app.faker_locale', 'en_US');
            $faker = FakerFactory::create($locale);

            // Attach the Markdown provider so methods like markdown() are available
            $faker->addProvider(new MarkdownFakerProvider($faker));

            return $faker;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return app(GroupMailMessageFactory::class)->verification($url);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return app(GroupMailMessageFactory::class)->passwordReset($url);
        });
    }
}
