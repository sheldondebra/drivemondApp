@extends('payment::layouts.master')

@section('content')
    <h1>Hello World</h1>

   <p>Module: {!! config('payment.name') !!}</p>
<h1>Test Payment Form</h1>
<form action="{{ route('payment.store',['token'=>bcrypt('payment')]) }}" method="POST">
    @csrf
    <label for="amount">Amount:</label>
    <input type="text" id="amount" name="amount" placeholder="Enter amount in USD" required><br><br>

    <label for="card_number">Card Number:</label>
    <input type="text" id="card_number" name="card_number" placeholder="Enter card number" required><br><br>

    <label for="expiration_date">Expiration Date:</label>
    <input type="text" id="expiration_date" name="expiration_date" placeholder="MM/YYYY" required><br><br>

    <label for="cvc">CVC:</label>
    <input type="text" id="cvc" name="cvc" placeholder="Enter CVC" required><br><br>

    <button type="submit">Pay Now</button>
</form>
@endsection
