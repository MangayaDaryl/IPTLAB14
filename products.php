<?php
require 'vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Product;
use Stripe\Price;
use Stripe\Invoice;
use Stripe\InvoiceItem;


Stripe::setApiKey('sk_test_51QKHJ9KiBQXzCVaOD4FDB7FweSD9sK0WrTNe6F6RkhYGZkWgJM5sJFyMr09A6RvHnrxdd4jfmh61UKD9QznEyydJ00hNANPyQ1');

$customers = [];
$products = [];
$errorMessage = "";

try {

    $customers = Customer::all(['limit' => 10])->data;

   
    $products = Product::all(['limit' => 10])->data;
} catch (Exception $e) {
    $errorMessage = "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'];
    $selectedProducts = $_POST['products'] ?? [];

    try {
      
        foreach ($selectedProducts as $productId) {
            $prices = Price::all(['product' => $productId])->data;

      
            $oneTimePrice = null;
            foreach ($prices as $price) {
                if ($price->type === 'one_time') {
                    $oneTimePrice = $price;
                    break;
                }
            }

            if ($oneTimePrice) {
                InvoiceItem::create([
                    'customer' => $customerId,
                    'price' => $oneTimePrice->id,
                ]);
            }
        }

  
        $invoice = Invoice::create([
            'customer' => $customerId,
        ]);
        $finalizedInvoice = $invoice->finalizeInvoice();

       
        $paymentLink = 'https://buy.stripe.com/test_6oE8xL9Zs93B9nG4gg';

   
        header("Location: " . $paymentLink);
        exit();
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        form {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }
        select, input[type="checkbox"] {
            margin: 10px 0;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            max-width: 600px;
            margin: 20px auto;
            padding: 10px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h1>Generate Invoice</h1>

    <?php if ($errorMessage): ?>
        <div class="message error">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="customer">Select Customer:</label>
        <select name="customer_id" id="customer" required>
            <?php foreach ($customers as $customer): ?>
                <option value="<?php echo $customer->id; ?>">
                    <?php echo htmlspecialchars($customer->name ?? $customer->email); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Select Products:</label>
        <?php foreach ($products as $product): ?>
            <div>
                <input type="checkbox" name="products[]" value="<?php echo $product->id; ?>">
                <?php echo htmlspecialchars($product->name); ?>
            </div>
        <?php endforeach; ?>

        <button type="submit">Generate Invoice</button>
    </form>
</body>
</html>
