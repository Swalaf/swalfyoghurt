<?php

namespace App\Providers;

use App\Support\EnvWriter;
use App\Support\Installation;
use App\Support\Settings;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Before installation there is no database: keep sessions and cache on disk
        // even if .env is missing or still points at the database.
        if (! Installation::installed() && ! $this->app->runningUnitTests()) {
            config(['session.driver' => 'file', 'cache.default' => 'file']);
        }

        // Fresh upload with no APP_KEY: create one so the installer can run
        // (sessions and encrypted settings need it).
        if (! config('app.key') && ! $this->app->runningUnitTests() && ! Installation::installed()) {
            $key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
            rescue(fn () => EnvWriter::write(['APP_KEY' => $key]), report: false);
            config(['app.key' => $key]);
        }
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with('brand', Settings::brand())->with('accent', Settings::accent());
        });

        if (Installation::installed()) {
            $this->applyMailSettings();
        }
    }

    /** Admin → Settings → Email overrides the .env mailer. */
    protected function applyMailSettings(): void
    {
        $s = Settings::all();
        if (! empty($s['mail_host']) && ! $this->app->runningUnitTests()) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $s['mail_host'],
                'mail.mailers.smtp.port' => (int) ($s['mail_port'] ?? 587) ?: 587,
                'mail.mailers.smtp.username' => $s['mail_username'] ?? null,
                'mail.mailers.smtp.password' => rescue(fn () => decrypt($s['mail_password']), null, false),
            ]);
        }
        if (! empty($s['mail_from'])) {
            config(['mail.from.address' => $s['mail_from'], 'mail.from.name' => Settings::brand()]);
        }
    }
}
