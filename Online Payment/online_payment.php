<!DOCTYPE html>
<html>
<head>
    <title>NomNom - Payment</title>

    <!-- Bootstrap -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2 class="text-center mb-4">Payment</h2>

    <form id="paymentForm" action="process_payment.php" method="POST">

        <!-- Order Summary -->
        <div class="mb-4">
            <h4>Order Summary</h4>

            <div class="d-flex justify-content-between">
                <span>Fried Rice</span>
                <span>$8.00</span>
            </div>

            <div class="d-flex justify-content-between">
                <span>Bubble Tea</span>
                <span>$5.00</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span>$13.00</span>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="mb-3">
            <label class="form-label">Payment Method</label><br>

            <input type="radio" name="payment_method" value="credit" required> Credit Card<br>
            <input type="radio" name="payment_method" value="debit"> Debit Card<br>
            <input type="radio" name="payment_method" value="visa"> Visa
        </div>

        <!-- Card Details -->
        <div class="mb-3">
            <label class="form-label">Name on Card</label>
            <input type="text" class="form-control" name="card_name" placeholder="Enter name">
        </div>

        <div class="mb-3">
            <label class="form-label">Card Number</label>
            <input type="text" class="form-control" name="card_number" placeholder="1234 5678 9012 3456">
        </div>

        <div class="row">
            <div class="col">
                <label class="form-label">Expiry</label>
                <input type="text" class="form-control" name="expiry" placeholder="MM/YY">
            </div>

            <div class="col">
                <label class="form-label">CVV</label>
                <input type="text" class="form-control" name="cvv" placeholder="123">
            </div>
        </div>

        <div id="error" class="text-danger mt-2"></div>

        <button type="submit" class="btn btn-primary w-100 mt-3">Pay Now</button>

    </form>

</div>


<!-- Bootstrap JS (same as teammate) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Validation (same logic, cleaner)

document.getElementById("paymentForm").addEventListener("submit", function(e){

    let name = document.querySelector("[name='card_name']").value.trim();
    let number = document.querySelector("[name='card_number']").value.trim();
    let expiry = document.querySelector("[name='expiry']").value.trim();
    let cvv = document.querySelector("[name='cvv']").value.trim();

    let error = "";

    if(!name || !number || !expiry || !cvv){
        error = "Please fill in all payment details.";
    }
    else if(number.length < 16){
        error = "Card number must be 16 digits.";
    }
    else if(cvv.length < 3){
        error = "CVV must be at least 3 digits.";
    }

    if(error){
        e.preventDefault();
        document.getElementById("error").innerText = error;
    }

});
</script>

</body>
</html>
