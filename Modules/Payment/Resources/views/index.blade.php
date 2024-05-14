@extends('payment::layouts.master')

@section('content')
    <h1>Hello World</h1>

   <p>Module: {!! config('payment.name') !!}</p>
<h1>Test Payment Form</h1>
<h2>Product: Laptop</h2>
<h3>Price: $5</h3>
<form action="{{ route('create') }}" method="post">
    @csrf
    <input type="hidden" name="price" value="5">
    <input type="hidden" name="product_name" value="Laptop">
    <input type="hidden" name="quantity" value="1">
    <button type="submit">Pay with Stripe</button>
</form>


@endsection
