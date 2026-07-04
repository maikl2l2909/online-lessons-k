<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function createSession(Request $request, Course $course): JsonResponse
    {
        if (! $course->isPublished()) {
            return response()->json(['message' => 'Course not available.'], 404);
        }

        if ($request->user()->hasPurchased($course)) {
            return response()->json(['message' => 'Already purchased.'], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $order = Order::firstOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            [
                'amount_cents' => $course->price_cents,
                'currency' => $course->currency,
                'status' => 'pending',
            ]
        );

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $course->currency,
                    'product_data' => [
                        'name' => $course->title,
                        'description' => Str($course->description)->limit(200)->toString(),
                    ],
                    'unit_amount' => $course->price_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('app.frontend_url').'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.frontend_url').'/courses/'.$course->slug,
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'course_id' => $course->id,
            ],
        ]);

        $order->update(['stripe_session_id' => $session->id]);

        return response()->json([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate(['session_id' => ['required', 'string']]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::retrieve($request->session_id);
        $order = Order::where('stripe_session_id', $session->id)->firstOrFail();

        if ($session->payment_status === 'paid') {
            $order->update([
                'status' => 'paid',
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);
        }

        return response()->json([
            'status' => $order->fresh()->status->value,
            'course' => new CourseResource($order->course),
        ]);
    }
}
