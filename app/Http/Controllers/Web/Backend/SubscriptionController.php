<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Product;
use App\Models\Plan;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class SubscriptionController extends Controller
{

    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    //show subscription page
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $plans = Plan::query();

            return DataTables::of($plans)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '
                    <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                        <button type="button" class="btn btn-primary fs-14 text-white editPlan"
                            data-id="' . $row->id . '" title="Edit">
                            <i class="fe fe-edit"></i>
                        </button>
                        <button type="button" onclick="confirmDelete(' . $row->id . ')"
                            class="btn btn-danger fs-14 text-white" title="Delete">
                            <i class="fe fe-trash"></i>
                        </button>
                    </div>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('backend.layouts.subscription.index');
    }

    //store subscription
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|integer|min:0',
                'interval' => 'required|string|in:day,week,month,year',
            ]);

            // Create Stripe Product
            $stripeProduct = Product::create([
                'name' => $request->name,
            ]);

            // Create Stripe Price
            $stripePrice = Price::create([
                'product' => $stripeProduct->id,
                'unit_amount' => $request->price * 100,
                'currency' => 'usd',
                'recurring' => ['interval' => $request->interval],
            ]);

            // Save in DB
            $plan = Plan::create([
                'name'              => $request->name,
                'stripe_product_id' => $stripeProduct->id,
                'stripe_price_id'   => $stripePrice->id,
                'price'             => $request->price,
                'interval'          => $request->interval,
            ]);

            // ✅ If AJAX, return JSON
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 1,
                    'message' => 'Plan created successfully',
                    'data'    => $plan
                ]);
            }

            // fallback (non-ajax)
            return redirect()->route('admin.subscription.index')
                ->with('success', 'Plan created successfully.');
        } catch (Exception $e) {
            Log::error('Plan creation failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Failed to create plan',
                    'error'   => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()
                ->withErrors('Failed to create plan: ' . $e->getMessage());
        }
    }

    //edit subscription
    public function edit($id)
    {
        try {
            $plan = Plan::findOrFail($id);

            return response()->json([
                'status' => 1,
                'message' => 'Plan fetched successfully.',
                'data' => $plan
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to fetch plan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //update subscription
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'interval' => 'required|string|in:day,week,month,year',
        ]);

        try {
            $plan = Plan::findOrFail($id);

            if ($plan->stripe_product_id) {
                $product = Product::retrieve($plan->stripe_product_id);
                $product->name = $request->name;
                $product->save();
            }

            $stripePrice = Price::create([
                'product' => $plan->stripe_product_id,
                'unit_amount' => $request->price * 100,
                'currency' => 'usd',
                'recurring' => ['interval' => $request->interval],
            ]);

            // Update database record
            $plan->name = $request->name;
            $plan->stripe_price_id = $stripePrice->id;
            $plan->price = $request->price;
            $plan->interval = $request->interval;
            $plan->save();

            return redirect()->route('admin.subscription.index')->with('success', 'Plan updated successfully.');
        } catch (Exception $e) {
            Log::error('Plan update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors('Failed to update plan: ' . $e->getMessage());
        }
    }

    //destroy
    public function destroy($id, Request $request)
    {
        try {
            $plan = Plan::findOrFail($id);

            // Deactivate product in Stripe (archive instead of delete)
            if ($plan->stripe_product_id) {
                Product::update($plan->stripe_product_id, ['active' => false]);
            }

            $plan->delete();

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 1,
                    'message' => 'Plan deleted successfully and archived on Stripe.'
                ]);
            }

            return redirect()->route('admin.subscription.index')
                ->with('success', 'Plan deleted successfully and archived on Stripe.');
        } catch (Exception $e) {
            Log::error('Plan deletion failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Failed to delete plan.',
                    'error'   => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withErrors('Failed to delete plan: ' . $e->getMessage());
        }
    }
}
