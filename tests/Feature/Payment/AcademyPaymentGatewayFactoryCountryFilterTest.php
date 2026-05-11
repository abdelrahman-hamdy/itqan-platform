<?php

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Services\Payment\AcademyPaymentGatewayFactory;
use App\Services\Payment\DTOs\AcademyPaymentSettings;

/**
 * Unit test for the private `isGatewayAllowedForCountry` policy in
 * AcademyPaymentGatewayFactory. We reach in via reflection because the
 * higher-level public API requires the full gateway-construction stack.
 */
function callIsAllowed(
    PaymentGatewayInterface $gateway,
    ?string $country,
    AcademyPaymentSettings $settings,
): bool {
    $factory = app(AcademyPaymentGatewayFactory::class);
    $ref = new \ReflectionClass($factory);
    $method = $ref->getMethod('isGatewayAllowedForCountry');
    $method->setAccessible(true);

    return (bool) $method->invoke($factory, $gateway, $country, $settings);
}

function makeGateway(string $name, array $baseline): PaymentGatewayInterface
{
    $stub = Mockery::mock(PaymentGatewayInterface::class);
    $stub->shouldReceive('getName')->andReturn($name);
    $stub->shouldReceive('getDisplayName')->andReturn($name);
    $stub->shouldReceive('isConfigured')->andReturn(true);
    $stub->shouldReceive('getSupportedMethods')->andReturn([]);
    $stub->shouldReceive('getSupportedCountries')->andReturn($baseline);
    $stub->shouldReceive('getFlowType')->andReturn(\App\Enums\PaymentFlowType::REDIRECT);
    $stub->shouldReceive('getBaseUrl')->andReturn('');
    $stub->shouldReceive('isSandbox')->andReturn(true);

    return $stub;
}

it('hides country-locked gateway when user country is null', function () {
    $tap = makeGateway('tap', ['SA', 'AE', 'KW']);
    $settings = AcademyPaymentSettings::fromArray([
        'tap' => ['allowed_countries' => ['SA']],
    ]);

    expect(callIsAllowed($tap, null, $settings))->toBeFalse();
});

it('keeps blocklist-only gateway visible when user country is null and baseline is empty', function () {
    $paymob = makeGateway('paymob', []); // empty baseline = no country restriction
    $settings = AcademyPaymentSettings::fromArray([
        'paymob' => ['blocked_countries' => ['SA']],
    ]);

    expect(callIsAllowed($paymob, null, $settings))->toBeTrue();
});

it('hides baseline-restricted gateway when user country is null', function () {
    // Empty allowed/blocked policy, baseline restricts to EG only.
    $easykash = makeGateway('easykash', ['EG']);
    $settings = AcademyPaymentSettings::fromArray([]);

    expect(callIsAllowed($easykash, null, $settings))->toBeFalse();
});

it('hides gateway when user country is outside baseline', function () {
    $easykash = makeGateway('easykash', ['EG']);
    $settings = AcademyPaymentSettings::fromArray([]);

    expect(callIsAllowed($easykash, 'US', $settings))->toBeFalse();
});

it('allows gateway when user country is on the allowed list', function () {
    $tap = makeGateway('tap', ['SA', 'AE']);
    $settings = AcademyPaymentSettings::fromArray([
        'tap' => ['allowed_countries' => ['SA']],
    ]);

    expect(callIsAllowed($tap, 'SA', $settings))->toBeTrue();
    expect(callIsAllowed($tap, 'AE', $settings))->toBeFalse(); // allowed list trumps baseline
});

it('blocks gateway when user country is on the blocklist', function () {
    $paymob = makeGateway('paymob', ['EG', 'SA', 'AE']);
    $settings = AcademyPaymentSettings::fromArray([
        'paymob' => ['blocked_countries' => ['SA']],
    ]);

    expect(callIsAllowed($paymob, 'SA', $settings))->toBeFalse();
    expect(callIsAllowed($paymob, 'EG', $settings))->toBeTrue();
});
