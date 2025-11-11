<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    /**
     * Process payment with the appropriate gateway
     */
    public function processPayment(Payment $payment, array $paymentData): array
    {
        try {
            switch ($payment->payment_gateway) {
                case 'paymob':
                    return $this->processPaymobPayment($payment, $paymentData);
                case 'tapay':
                    return $this->processTapayPayment($payment, $paymentData);
                case 'moyasar':
                    return $this->processMoyasarPayment($payment, $paymentData);
                case 'stc_pay':
                    return $this->processStcPayPayment($payment, $paymentData);
                default:
                    return $this->processMockPayment($payment, $paymentData);
            }
        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'gateway' => $payment->payment_gateway,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'حدث خطأ أثناء معالجة الدفع: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process payment with Paymob (MENA region)
     */
    private function processPaymobPayment(Payment $payment, array $paymentData): array
    {
        // Paymob integration placeholder
        // This would be implemented with actual Paymob API calls
        
        $isSandbox = config('app.env') !== 'production';
        
        if ($isSandbox) {
            // Simulate sandbox payment processing
            $isSuccess = rand(1, 10) > 2; // 80% success rate for testing
            
            if ($isSuccess) {
                return [
                    'success' => true,
                    'data' => [
                        'transaction_id' => 'PMB_TXN_' . time() . '_' . rand(1000, 9999),
                        'receipt_number' => 'PMB_REC_' . $payment->id . '_' . time(),
                        'gateway_response' => 'Paymob sandbox payment processed successfully',
                        'authorization_code' => 'AUTH_' . rand(100000, 999999),
                        'amount_cents' => $payment->amount * 100, // Paymob uses cents
                        'currency' => $payment->currency,
                        'gateway_data' => [
                            'gateway' => 'paymob',
                            'environment' => 'sandbox',
                            'payment_method' => $paymentData['payment_method'] ?? 'card'
                        ]
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في عملية الدفع - محاولة تجريبية'
                ];
            }
        }
        
        // Real Paymob integration would go here
        /*
        $paymobApiKey = config('services.paymob.api_key');
        $paymobIntegrationId = config('services.paymob.integration_id');
        
        // Step 1: Authentication
        $authResponse = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => $paymobApiKey
        ]);
        
        if (!$authResponse->successful()) {
            throw new \Exception('Paymob authentication failed');
        }
        
        $authToken = $authResponse->json('token');
        
        // Step 2: Create order
        $orderResponse = Http::withToken($authToken)->post('https://accept.paymob.com/api/ecommerce/orders', [
            'delivery_needed' => false,
            'amount_cents' => $payment->amount * 100,
            'currency' => $payment->currency,
            'merchant_order_id' => $payment->id,
            'items' => []
        ]);
        
        if (!$orderResponse->successful()) {
            throw new \Exception('Failed to create Paymob order');
        }
        
        $orderId = $orderResponse->json('id');
        
        // Step 3: Payment key
        $paymentKeyResponse = Http::withToken($authToken)->post('https://accept.paymob.com/api/acceptance/payment_keys', [
            'amount_cents' => $payment->amount * 100,
            'expiration' => 3600,
            'order_id' => $orderId,
            'billing_data' => [
                'email' => $payment->user->email,
                'first_name' => $payment->user->first_name ?? 'Student',
                'last_name' => $payment->user->last_name ?? 'User',
                'phone_number' => $payment->user->phone ?? '+966500000000',
                'country' => 'SA',
                'city' => 'Riyadh',
                'state' => 'Riyadh',
                'apartment' => 'NA',
                'floor' => 'NA',
                'street' => 'NA',
                'building' => 'NA',
                'shipping_method' => 'NA',
                'postal_code' => 'NA'
            ],
            'currency' => $payment->currency,
            'integration_id' => $paymobIntegrationId
        ]);
        
        if (!$paymentKeyResponse->successful()) {
            throw new \Exception('Failed to create Paymob payment key');
        }
        
        $paymentKey = $paymentKeyResponse->json('token');
        
        return [
            'success' => true,
            'requires_redirect' => true,
            'redirect_url' => "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentKey}",
            'data' => [
                'payment_key' => $paymentKey,
                'order_id' => $orderId,
                'transaction_id' => 'PMB_' . $orderId
            ]
        ];
        */
        
        return [
            'success' => false,
            'error' => 'Paymob integration not yet implemented'
        ];
    }

    /**
     * Process payment with Tapay (GCC region)
     */
    private function processTapayPayment(Payment $payment, array $paymentData): array
    {
        // Tapay integration placeholder
        // This would be implemented with actual Tapay API calls
        
        $isSandbox = config('app.env') !== 'production';
        
        if ($isSandbox) {
            // Simulate sandbox payment processing
            $isSuccess = rand(1, 10) > 2; // 80% success rate for testing
            
            if ($isSuccess) {
                return [
                    'success' => true,
                    'data' => [
                        'transaction_id' => 'TAP_TXN_' . time() . '_' . rand(1000, 9999),
                        'receipt_number' => 'TAP_REC_' . $payment->id . '_' . time(),
                        'gateway_response' => 'Tapay sandbox payment processed successfully',
                        'reference_number' => 'REF_' . rand(100000, 999999),
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'gateway_data' => [
                            'gateway' => 'tapay',
                            'environment' => 'sandbox',
                            'payment_method' => $paymentData['payment_method'] ?? 'card'
                        ]
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في عملية الدفع - محاولة تجريبية'
                ];
            }
        }
        
        // Real Tapay integration would go here
        /*
        $tapayApiKey = config('services.tapay.api_key');
        $tapayMerchantId = config('services.tapay.merchant_id');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tapayApiKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.tap.company/v2/charges', [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'threeDSecure' => true,
            'save_card' => false,
            'description' => 'Quran Subscription Payment',
            'statement_descriptor' => 'Itqan Academy',
            'metadata' => [
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
                'user_id' => $payment->user_id
            ],
            'reference' => [
                'transaction' => 'txn_' . $payment->id,
                'order' => 'ord_' . $payment->subscription_id
            ],
            'receipt' => [
                'email' => true,
                'sms' => true
            ],
            'customer' => [
                'first_name' => $payment->user->first_name ?? 'Student',
                'last_name' => $payment->user->last_name ?? 'User',
                'email' => $payment->user->email,
                'phone' => [
                    'country_code' => '966',
                    'number' => ltrim($payment->user->phone ?? '500000000', '+966')
                ]
            ],
            'source' => [
                'id' => $paymentData['card_token'] ?? 'src_card'
            ],
            'redirect' => [
                'url' => route('payment.callback', ['payment' => $payment->id])
            ]
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Tapay payment request failed: ' . $response->body());
        }
        
        $responseData = $response->json();
        
        return [
            'success' => true,
            'data' => [
                'transaction_id' => $responseData['id'],
                'receipt_number' => $responseData['reference']['receipt'] ?? null,
                'gateway_response' => $responseData,
                'redirect_url' => $responseData['transaction']['url'] ?? null
            ]
        ];
        */
        
        return [
            'success' => false,
            'error' => 'Tapay integration not yet implemented'
        ];
    }

    /**
     * Process payment with Moyasar
     */
    private function processMoyasarPayment(Payment $payment, array $paymentData): array
    {
        // Mock Moyasar payment for development
        $isSuccess = rand(1, 10) > 1; // 90% success rate
        
        if ($isSuccess) {
            return [
                'success' => true,
                'data' => [
                    'transaction_id' => 'MYS_TXN_' . time() . '_' . rand(1000, 9999),
                    'receipt_number' => 'MYS_REC_' . $payment->id . '_' . time(),
                    'gateway_response' => 'Moyasar payment processed successfully',
                    'authorization_code' => 'AUTH_' . rand(100000, 999999),
                    'gateway_data' => [
                        'gateway' => 'moyasar',
                        'payment_method' => $paymentData['payment_method'] ?? 'card'
                    ]
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Payment declined by bank'
            ];
        }
    }

    /**
     * Process STC Pay payment
     */
    private function processStcPayPayment(Payment $payment, array $paymentData): array
    {
        // Mock STC Pay payment for development
        $isSuccess = rand(1, 10) > 1; // 90% success rate
        
        if ($isSuccess) {
            return [
                'success' => true,
                'data' => [
                    'transaction_id' => 'STC_TXN_' . time() . '_' . rand(1000, 9999),
                    'receipt_number' => 'STC_REC_' . $payment->id . '_' . time(),
                    'gateway_response' => 'STC Pay payment processed successfully',
                    'stc_reference' => 'STC_' . rand(100000, 999999),
                    'gateway_data' => [
                        'gateway' => 'stc_pay',
                        'payment_method' => 'stc_pay'
                    ]
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => 'STC Pay transaction failed'
            ];
        }
    }

    /**
     * Process mock payment for testing
     */
    private function processMockPayment(Payment $payment, array $paymentData): array
    {
        // Always succeed for testing purposes
        return [
            'success' => true,
            'data' => [
                'transaction_id' => 'MOCK_TXN_' . time() . '_' . rand(1000, 9999),
                'receipt_number' => 'MOCK_REC_' . $payment->id . '_' . time(),
                'gateway_response' => 'Mock payment processed successfully for testing',
                'gateway_data' => [
                    'gateway' => 'mock',
                    'payment_method' => $paymentData['payment_method'] ?? 'card',
                    'environment' => 'testing'
                ]
            ]
        ];
    }

    /**
     * Get available payment methods for academy
     */
    public function getAvailablePaymentMethods($academy): array
    {
        $methods = [
            'credit_card' => [
                'name' => 'بطاقة ائتمانية',
                'icon' => 'ri-bank-card-line',
                'gateway' => 'moyasar'
            ],
            'mada' => [
                'name' => 'مدى',
                'icon' => 'ri-bank-card-2-line',
                'gateway' => 'moyasar'
            ],
            'stc_pay' => [
                'name' => 'STC Pay',
                'icon' => 'ri-smartphone-line',
                'gateway' => 'stc_pay'
            ]
        ];

        // Add regional gateways based on academy settings
        if ($academy && $academy->region === 'mena') {
            $methods['paymob'] = [
                'name' => 'PayMob',
                'icon' => 'ri-bank-line',
                'gateway' => 'paymob'
            ];
        }

        if ($academy && $academy->region === 'gcc') {
            $methods['tapay'] = [
                'name' => 'Tap Payments',
                'icon' => 'ri-bank-line', 
                'gateway' => 'tapay'
            ];
        }

        return $methods;
    }

    /**
     * Calculate fees for payment method
     */
    public function calculateFees(float $amount, string $paymentMethod): array
    {
        $fees = [
            'credit_card' => 0.025, // 2.5%
            'mada' => 0.015,       // 1.5%
            'stc_pay' => 0.02,     // 2%
            'paymob' => 0.028,     // 2.8%
            'tapay' => 0.024       // 2.4%
        ];

        $feeRate = $fees[$paymentMethod] ?? 0.025;
        $feeAmount = round($amount * $feeRate, 2);
        
        return [
            'fee_rate' => $feeRate,
            'fee_amount' => $feeAmount,
            'total_with_fees' => $amount + $feeAmount
        ];
    }
}