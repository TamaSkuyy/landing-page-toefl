<?php

namespace App\Http\Controllers;

use App\Analytics\EventType;
use App\Analytics\TrackingService;
use App\Models\Order;
use App\Services\DuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request, DuitkuService $duitku, TrackingService $tracking): JsonResponse
    {
        try {
            $result = $duitku->verifyCallback($request);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Duitku callback signature rejected.', ['order_number' => $request->input('merchantOrderId')]);

            return response()->json(['message' => $exception->getMessage()], 400);
        }

        try {
            $outcome = DB::transaction(function () use ($request, $result, $tracking): string {
                $order = Order::query()->where('order_number', $result['order_number'])->lockForUpdate()->first();
                if (! $order) {
                    return 'missing';
                }
                if ($order->isPaid()) {
                    return 'duplicate';
                }
                if ($result['amount'] !== $order->amount) {
                    throw new InvalidArgumentException('Callback amount does not match the order.');
                }

                if ($result['status'] === 'paid') {
                    $order->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'duitku_reference' => $result['reference'] ?: null,
                        'payment_method' => $result['payment_method'] ?: null,
                        'callback_payload' => $result['payload'],
                    ]);
                    $tracking->track($request, EventType::Payment, [
                        'event_id' => 'payment-'.$order->order_number,
                        'order_number' => $order->order_number,
                        'status' => 'paid',
                        'amount' => $order->amount,
                        'currency' => 'IDR',
                        'landing_source' => $order->landing_source,
                    ], ['email' => $order->email, 'phone' => $order->phone, 'product_name' => config('analytics.product_name')], $order->session_id, $order->visitor_id);

                    return 'paid';
                }

                $order->update(['status' => $result['status'], 'callback_payload' => $result['payload']]);

                return $result['status'];
            });

            return response()->json(['message' => $outcome === 'duplicate' ? 'Already processed' : 'OK']);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Duitku callback rejected.', ['order_number' => $result['order_number'], 'reason' => $exception->getMessage()]);

            return response()->json(['message' => $exception->getMessage()], 400);
        } catch (Throwable $exception) {
            Log::warning('Duitku callback could not be processed.', ['order_number' => $result['order_number']]);

            return response()->json(['message' => 'Unable to process callback.'], 500);
        }
    }
}
