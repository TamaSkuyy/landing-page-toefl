<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Lead;
use App\Models\Order;
use App\Services\DuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, DuitkuService $duitku): JsonResponse
    {
        abort_unless(config('analytics.payment_mode') === 'internal', 422, 'Internal payment is disabled.');
        abort_if((int) config('analytics.product_price') <= 0, 422, 'PRODUCT_PRICE must be configured.');
        $lead = Lead::query()->findOrFail($request->integer('lead_id'));

        $order = DB::transaction(fn () => Order::query()->create([
            'order_number' => 'PBM-'.strtoupper(Str::random(12)),
            'lead_id' => $lead->id,
            'session_id' => $lead->session_id,
            'visitor_id' => $lead->visitor_id,
            'landing_source' => $lead->landing_source,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'amount' => (int) config('analytics.product_price'),
            'status' => 'pending',
        ]));

        $paymentUrl = $duitku->createInvoice($order);

        return response()->json(['order_number' => $order->order_number, 'payment_url' => $paymentUrl], 201);
    }

    public function returnPage(Request $request): Response
    {
        $order = Order::query()->where('order_number', $request->query('order'))->first();

        return Inertia::render('payment/return', ['order' => $order?->only(['order_number', 'name', 'amount', 'status', 'paid_at'])]);
    }
}
