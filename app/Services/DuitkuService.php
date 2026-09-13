<?php

namespace App\Services;

use App\Models\Order;
use Duitku\Config;
use Duitku\Pop;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class DuitkuService
{
    private ?Config $client = null;

    public function createInvoice(Order $order): string
    {
        if (! filled(config('duitku.api_key')) || ! filled(config('duitku.merchant_code'))) {
            throw new RuntimeException('Duitku credentials are not configured.');
        }

        $response = Pop::createInvoice([
            'paymentAmount' => $order->amount,
            'merchantOrderId' => $order->order_number,
            'productDetails' => (string) config('analytics.product_name', config('app.name')),
            'email' => $order->email ?: 'customer@example.invalid',
            'callbackUrl' => route('payment.callback'),
            'returnUrl' => route('payment.return', ['order' => $order->order_number]),
            'expiryPeriod' => (int) config('duitku.expiry_period'),
        ], $this->client());

        $data = json_decode($response, true);
        if (! is_array($data) || empty($data['paymentUrl'])) {
            throw new RuntimeException('Duitku did not return a payment URL.');
        }

        return (string) $data['paymentUrl'];
    }

    public function verifyCallback(Request $request): array
    {
        $merchantCode = (string) $request->input('merchantCode');
        $amount = (string) $request->input('amount');
        $orderNumber = (string) $request->input('merchantOrderId');
        $signature = (string) $request->input('signature');
        $expected = md5($merchantCode.$amount.$orderNumber.(string) config('duitku.api_key'));

        if (! hash_equals((string) config('duitku.merchant_code'), $merchantCode)) {
            throw new InvalidArgumentException('Invalid Duitku merchant code.');
        }
        if (! hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Invalid Duitku signature.');
        }

        return [
            'status' => match ((string) $request->input('resultCode')) {
                '00' => 'paid', '01' => 'failed', default => 'pending'
            },
            'order_number' => $orderNumber,
            'amount' => (int) $amount,
            'reference' => (string) $request->input('reference', ''),
            'payment_method' => (string) $request->input('paymentCode', ''),
            'payload' => $request->except(['signature']),
        ];
    }

    private function client(): Config
    {
        return $this->client ??= new Config(
            (string) config('duitku.api_key'),
            (string) config('duitku.merchant_code'),
            config('duitku.environment') !== 'production',
            true,
            false,
        );
    }
}
