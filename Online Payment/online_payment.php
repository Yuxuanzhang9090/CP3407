<?php

$foods = [
    ["name" => "Pepperoni Pizza", "price" => 12.50, "qty" => 1],
    ["name" => "Garlic Bread", "price" => 5.00, "qty" => 2]
];

$total = 0;
?>

<h2>Order Summary</h2>

<table border="1">
<tr>
<th>Food</th>
<th>Price</th>
<th>Quantity</th>
<th>Subtotal</th>
</tr>

<?php foreach ($foods as $food): 
$subtotal = $food['price'] * $food['qty'];
$total += $subtotal;
?>

<tr>
<td><?php echo $food['name']; ?></td>
<td>$<?php echo $food['price']; ?></td>
<td><?php echo $food['qty']; ?></td>
<td>$<?php echo $subtotal; ?></td>
</tr>

<?php endforeach; ?>

</table>

<h3>Total Price: $<?php echo $total; ?></h3>

<h2>Select Payment Method</h2>

<form action="process_payment.php" method="POST">

<input type="radio" name="payment_method" value="credit_card" required>
Credit Card

<br>

<input type="radio" name="payment_method" value="debit_card">
Debit Card

<br>

<input type="radio" name="payment_method" value="paypal">
PayPal

<br><br>

<h3>Card Details</h3>

Card Number:<br>
<input type="text" name="card_number"><br><br>

Card Holder Name:<br>
<input type="text" name="card_name"><br><br>

Expiry Date:<br>
<input type="month" name="expiry"><br><br>

CVV:<br>
<input type="text" name="cvv"><br><br>

<button type="submit">Pay Now</button>

</form>
