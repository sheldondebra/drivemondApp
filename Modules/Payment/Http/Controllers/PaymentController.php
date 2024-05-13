<?php

namespace Modules\Payment\Http\Controllers;

use Stripe\Charge;
use Stripe\Stripe;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Modules\Payment\Models\PaymentDetails;
use Stripe\Exception\InvalidRequestException;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('payment::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payment::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            try {
                Stripe::setApiKey(env('STRIPE_TEST_SK'));

                $charge = Charge::create([
                    'amount' => $request->amount,
                    'currency' => 'usd',
                    'source' => $request->stripeToken,
                    'description' => 'Donation from ' . $request->name,
                ]);

                // Save payment details
                $payment = PaymentDetails::create([
                    'amount' => $request->amount,
                    'payment_method' => 'Stripe', // Stripe is the only payment method
                    'transaction_id' => $charge->id,
                    'status' => 'completed', // Assuming payment is successful
                    'email' => $request->email,
                    'user_id' => $user->id,
                ]);

                // Save donation with payment details
                // $donation = $payment->donation()->create([
                //     'amount' => $request->amount,
                //     'Driver_name' => $request->name,
                //     'email' => $request->email,
                //     'user_id' => $user->id,
                //     // Other fields
                // ]);

                // Return success response or redirect
                return response()->json(['message' => 'Payment successful']);
            } catch (InvalidRequestException $e) {
                // Handle Stripe validation errors
                return response()->json(['error' => $e->getMessage()], 400);
            } catch (\Exception $e) {
                // Handle other exceptions
                return response()->json(['error' => 'An error occurred while processing the donation'], 500);
            }
        } else {
            // Handle case where user is not authenticated
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('payment::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('payment::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
