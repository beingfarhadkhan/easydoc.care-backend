<!DOCTYPE html>
<html>
<head>
    <title>Buy Plan</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif

<button id="payBtn">Buy Plan ₹{{ $amount }}</button>

<form id="payment-form" action="{{ url('/payment-success') }}" method="POST">
    @csrf
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
</form>

<script>
var options = {
    "key": "{{ $razorpayKey }}",
    "amount": "{{ $amount * 100 }}",
    "currency": "INR",
    "name": "EasyDoc - Starter Plan",
    "description": "Buy Subscription",
    "order_id": "{{ $orderId }}",
    "handler": function (response){
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
        document.getElementById('razorpay_signature').value = response.razorpay_signature;
        document.getElementById('payment-form').submit();
    }
};

document.getElementById('payBtn').onclick = function(){
    var rzp = new Razorpay(options);
    rzp.open();
};
</script>

</body>
</html>
