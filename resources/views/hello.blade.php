<button class="btn btn-primary" id="payNowBtn">Pay Now</button>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
document.getElementById("payNowBtn").onclick = function () {

    let billingData = {
        amount: 500,          // Amount entered by user or bill amount
        user_id: "{{ auth()->user()->id }}", 
        receipt_id: "{{ $receipt_id }}" // Billing Receipt ID
    };

    fetch("/create-order", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify(billingData)
    })
    .then(res => res.json())
    .then(data => {

        var options = {
            "key": data.key,
            "amount": data.amount,
            "currency": "INR",
            "name": "ERM",
            "description": "Payment for Receipt #" + billingData.receipt_id,
            "order_id": data.order_id,

            "handler": function (response) {
                verifyPayment(response);
            },

            "theme": {
                "color": "#0d6efd"
            }
        };

        var rzp = new Razorpay(options);
        rzp.open();
    });
};


function verifyPayment(response) {
    fetch("/verify-payment", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify(response)
    })
    .then(res => res.json())
    .then(data => {
        alert("Payment Successful!");
        location.reload();
    })
    .catch(err => {
        alert("Payment Failed!");
    });
}
</script>
