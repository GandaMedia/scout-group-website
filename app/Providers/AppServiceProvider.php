<?php

namespace App\Providers;

use App\Mail\GroupMailMessageFactory;
use DavidBadura\FakerMarkdownGenerator\FakerProvider as MarkdownFakerProvider;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

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
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            if (app()->environment(['local', 'testing'])) {
                return null;
            }

            if (! in_array($response->statusCode(), [403, 404, 500, 503], true)) {
                return null;
            }

            return $response
                ->render('ErrorPage', ['status' => $response->statusCode()])
                ->withSharedData();
        });

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
