<!DOCTYPE html>
<html>
<head>
    <title>Online Payment</title>
</head>

<body>

<div class="container">

<h2>Payment Page</h2>

<!-- Task 1: Display food details -->
<div class="order-summary">

<h3>Order Summary</h3>

<div class="order-item">
<span>Fried Rice</span>
<span>$8.00</span>
</div>

<div class="order-item">
<span>Bubble Tea</span>
<span>$5.00</span>
</div>

<div class="order-item total">
<span>Total</span>
<span>$13.00</span>
</div>

</div>

<form id="paymentForm" action="process_payment.php" method="POST">

<!-- Task 2: Payment Method -->
<h3>Select Payment Method</h3>

<label>
<input type="radio" name="payment_method" value="credit" required>
Credit Card
</label>

<label>
<input type="radio" name="payment_method" value="debit">
Debit Card
</label>

<label>
<input type="radio" name="payment_method" value="visa">
Visa
</label>

<!-- Task 3: Card Details -->

<h3>Card Information</h3>

<input type="text" name="card_name" placeholder="Name on Card">

<input type="text" name="card_number" placeholder="Card Number">

<input type="text" name="expiry" placeholder="MM/YY">

<input type="text" name="cvv" placeholder="CVV">

<div id="error" class="error"></div>

<button type="submit">Pay Now</button>

</form>

</div>

<script>

// Task 4: Validate payment input

document.getElementById("paymentForm").addEventListener("submit", function(e){

let name = document.querySelector("[name='card_name']").value;
let number = document.querySelector("[name='card_number']").value;
let expiry = document.querySelector("[name='expiry']").value;
let cvv = document.querySelector("[name='cvv']").value;

let error = "";

if(name === "" || number === "" || expiry === "" || cvv === ""){
error = "Please fill in all payment details.";
}

if(number.length < 16){
error = "Card number must be 16 digits.";
}

if(cvv.length < 3){
error = "CVV must be 3 digits.";
}

if(error !== ""){
e.preventDefault();
document.getElementById("error").innerText = error;
}

});

</script>

</body>
</html>
