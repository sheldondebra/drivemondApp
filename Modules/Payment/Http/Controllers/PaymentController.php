<?php

namespace Modules\Payment\Http\Controllers;

use Stripe\Charge;
use Stripe\Stripe;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\PaymentDetails;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
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
    public function create(Request $request)
    {

        if (auth()->check()) {
            $user = Auth::user();

            try {
                $stripe = new \Stripe\StripeClient(config('stripe.secret'));

                $response = $stripe->checkout->sessions->create([
                    'line_items' => [
                        [
                            'price_data' => [
                                'currency' => 'usd',
                                'product_data' => [
                                    'name' => "Drive Deposit",
                                ],
                                'unit_amount' => $request->price * 100,
                            ],
                            'quantity' => 1,
                        ],
                    ],
                    'mode' => 'payment',
                    'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}&uid=' . auth()->id(),
                    'cancel_url' => route('cancel'),
                ]);


                if (isset($response->id) && $response->id != '') {
                    return redirect($response->url);
                } else {
                    return redirect()->route('cancel');
                }
            } catch (InvalidRequestException $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        } else {
            return response()->json(['error' => 'User not authenticated '], 401);
        }
    }

    public function success(Request $request)
    {
        // dd($request->session_id);
        if (isset($request->session_id)) {

            try{
                $stripe = new \Stripe\StripeClient(config('stripe.secret'));
                $response = $stripe->checkout->sessions->retrieve($request->session_id);

                $payment = PaymentDetails::create([
                    'amount'=>($response->amount_total)/100,
                    'transaction_id' => $response->id,
                    'user_id' => $request->query('uid'),
                    'email' => $response->customer_details->email,
                    'status' => $response->status,
                    'payment_method' => "Stripe",
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment is successful.',
                ], 200);
            }
            catch (\Exception $e) {
                return response()->json(["error"=> $e->getMessage()], 400);
            }

        }
        else {
            return redirect()->route('cancel');
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function cancel(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Payment cancelled by the user.',
        ], 400);
    }

    /**
     * Show the specified resource.
     */
    public function total_amount($id)
    {

        if(auth()->check()){
            try{
                $userPayments = PaymentDetails::where('user_id', $id)->sum('amount');

                if ($userPayments === 0 || $userPayments == null) {
                    return response()->json([
                        'success' => true,
                        'data' => 0,
                        'message' => 'No payments found for the user.',
                    ], 200);
                }
                return response()->json([
                    'success' => true,
                    'data' => $userPayments,
                    'message' => 'Payments found for the user.',
                ], 200);


            }
            catch(\Exception $e){
                return response()->json(["error" => $e->getMessage()], 400);
            }
        }
        else{
            return response()->json(['error' => 'User not authenticated '], 401);
        }
    }


}
