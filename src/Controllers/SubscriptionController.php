<?php

namespace ZapsterStudios\TeamPay\Controllers;

use TeamPay;
use App\Team;
use Illuminate\Http\Request;
use ZapsterStudios\TeamPay\Events\Subscriptions\SubscriptionCreated;
use ZapsterStudios\TeamPay\Events\Subscriptions\SubscriptionResumed;
use ZapsterStudios\TeamPay\Events\Subscriptions\SubscriptionSwapped;
use ZapsterStudios\TeamPay\Events\Subscriptions\SubscriptionCancelled;

class SubscriptionController extends Controller
{
    /**
     * Subscribe the team to a plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function subscription(Request $request, Team $team)
    {
        abort_unless($request->user()->tokenCan('manage-subscriptions'), 401);
        $this->authorize('update', $team);
        $this->validate($request, [
            'plan' => 'required',
            'nonce' => 'required',
        ]);

        $plan = TeamPay::activePlans()->where('id', $request->plan)->first();
        if (! $plan) {
            return response()->json([
                'plan' => ['Unavailable plan.'],
            ], 422);
        }

        if ($plan->id === TeamPay::freePlan()->id) {
            $team->subscription()->cancel();

            event(new SubscriptionCancelled($team));

            return response()->json([
                'message' => 'Paid subscription cancelled',
            ]);
        }

        if ($team->subscribed()) {
            $subscription = $team->subscription()->swap($plan->id);

            event(new SubscriptionSwapped($team, $subscription));

            return response()->json($subscription);
        }

        $subscription = $team->newSubscription('default', $plan->id)
            ->withCoupon($request->coupon ?? null)
            ->create($request->nonce);

        event(new SubscriptionCreated($team, $subscription));

        return response()->json($subscription);
    }

    /**
     * Cancel a teams subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request, Team $team)
    {
        abort_unless($request->user()->tokenCan('manage-subscriptions'), 401);
        $this->authorize('update', $team);

        $subscription = $team->subscription()->cancel();
        event(new SubscriptionCancelled($team));

        return response()->json($subscription);
    }

    /**
     * Resume a teams subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function resume(Request $request, Team $team)
    {
        abort_unless($request->user()->tokenCan('manage-subscriptions'), 401);
        $this->authorize('update', $team);

        $subscription = $team->subscription()->resume();
        event(new SubscriptionResumed($team));

        return response()->json($subscription);
    }

    /**
     * Return a teams invoices.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function invoices(Request $request, Team $team)
    {
        abort_unless($request->user()->tokenCan('view-invoices'), 401);
        $this->authorize('update', $team);

        $invoices = [];
        if ($team->hasBraintreeId()) {
            $invoices = $team->invoices(true)->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'status' => $invoice->status,
                    'total' => $invoice->total(),
                    'created_at' => $invoice->date()->toDateTimeString(),
                ];
            });
        }

        return response()->json($invoices);
    }

    /**
     * Return a specefic team invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Team  $team
     * @return \Illuminate\Http\Response
     */
    public function invoice(Request $request, Team $team, $invoice)
    {
        abort_unless($request->user()->tokenCan('view-invoices'), 401);
        $this->authorize('update', $team);

        return $team->downloadInvoice($invoice, [
            'vendor'  => config('app.name'),
            'product' => 'Membership Subscription',
        ]);
    }
}
