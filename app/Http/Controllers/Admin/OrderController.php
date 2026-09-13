<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\EventType;
use App\Analytics\TrackingService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request) {
                $search = '%'.$request->string('search')->limit(100).'%';
                $query->where('order_number', 'like', $search)->orWhere('name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('phone', 'like', $search);
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()->paginate(20)->withQueryString();

        return Inertia::render('admin/orders/index', ['orders' => $orders, 'filters' => $request->only(['search', 'status'])]);
    }

    public function updateStatus(Request $request, Order $order, TrackingService $tracking): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['pending', 'paid', 'failed'])]]);
        if ($validated['status'] === 'paid') {
            return $this->markAsPaid($request, $order, $tracking);
        }
        $order->update(['status' => $validated['status']]);

        return response()->json(['success' => true]);
    }

    public function markAsPaid(Request $request, Order $order, TrackingService $tracking): JsonResponse
    {
        if ($order->isPaid()) {
            return response()->json(['success' => true, 'message' => 'Already paid']);
        }
        $order->update(['status' => 'paid', 'paid_at' => now()]);
        $tracking->track($request, EventType::Payment, ['event_id' => 'payment-'.$order->order_number, 'order_number' => $order->order_number, 'status' => 'paid', 'amount' => $order->amount, 'currency' => 'IDR', 'landing_source' => $order->landing_source], ['email' => $order->email, 'phone' => $order->phone], $order->session_id, $order->visitor_id);

        return response()->json(['success' => true]);
    }
}
