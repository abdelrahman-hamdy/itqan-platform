<?php

namespace App\Providers;

use App\Contracts\PaymentServiceInterface;
use App\Services\NotificationService;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use App\Services\Payment\EasyKashSignatureService;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentMethodService;
use App\Services\Payment\PaymentStateMachine;
use App\Services\Payment\PaymobSignatureService;
use App\Services\PaymentService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register PaymentGatewayManager as singleton
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager($app);
        });

        // Register PaymentStateMachine as singleton
        $this->app->singleton(PaymentStateMachine::class, function ($app) {
            return new PaymentStateMachine;
        });

        // Register PaymobSignatureService
        $this->app->singleton(PaymobSignatureService::class, function ($app) {
            return new PaymobSignatureService(
                config('payments.gateways.paymob.hmac_secret')
            );
        });

        // Register EasyKashSignatureService
        $this->app->singleton(EasyKashSignatureService::class, function ($app) {
            return new EasyKashSignatureService(
                config('payments.gateways.easykash.secret_key', '')
            );
        });

        // Register AcademyPaymentGatewayFactory
        $this->app->singleton(AcademyPaymentGatewayFactory::class, function ($app) {
            return new AcademyPaymentGatewayFactory(
                $app->make(PaymentGatewayManager::class)
            );
        });

        // Register PaymentMethodService
        $this->app->singleton(PaymentMethodService::class, function ($app) {
            return new PaymentMethodService(
                $app->make(AcademyPaymentGatewayFactory::class)
            );
        });

        // Register PaymentService with dependencies
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(PaymentGatewayManager::class),
                $app->make(PaymentStateMachine::class),
                $app->make(NotificationService::class),
                $app->make(AcademyPaymentGatewayFactory::class),
                $app->make(PaymentMethodService::class)
            );
        });

        // Bind interface to implementation
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);

        // Alias for convenience
        $this->app->alias(PaymentGatewayManager::class, 'payment.gateway');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the payments log channel
        $this->app['config']->set('logging.channels.payments', [
            'driver' => 'daily',
            'path' => storage_path('logs/payments.log'),
            'level' => 'debug',
            'days' => 30,
        ]);
    }
}
