<?php
// index.php

// Include the Stripe PHP library
require 'vendor/autoload.php';

// **Database Connection**
// Database credentials
$host = 'localhost';       // Your database host (usually 'localhost')
$db   = 'stripe4';   // The name of your database
$user = 'root';   // Your database username
$pass = '';   // Your database password
$charset = 'utf8mb4';

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulated prepares
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());
}

// Set your Stripe secret key (replace with your actual secret key)
\Stripe\Stripe::setApiKey('sk_test_sqgB9bTz3DLHGAqrET60cdmM');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_payment_intent'])) {
    // Generate a unique order ID (you can use your own logic here)
    $order_id = uniqid();

    // Define the amount to charge (in cents)
    $amount = 1000; // $10.00
    $currency = 'usd';

    // Insert the order into your database with status 'pending'
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, status, created_at) VALUES (:order_id, :amount, 'pending', NOW())");
    $stmt->execute([
        'order_id' => $order_id,
        'amount'   => $amount,
    ]);

    // Create a PaymentIntent on the server
    try {
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount'               => $amount,
            'currency'             => $currency,
            'payment_method_types' => ['card'],
            'metadata'             => [
                'order_id' => $order_id,
            ],
        ]);

        // Send the client_secret and order_id to the client
        echo json_encode([
            'client_secret' => $paymentIntent->client_secret,
            'order_id'      => $order_id,
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Handle error
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stripe Payment Form</title>
    <!-- Include Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    <style>
    /* Add some basic styling */
    .StripeElement {
        box-sizing: border-box;
        height: 40px;
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background-color: white;
        box-shadow: inset 0 1px 1px rgba(0,0,0,0.075);
        transition: border-color 0.15s ease-in-out,box-shadow 0.15s ease-in-out;
    }

    .StripeElement:focus {
        border-color: #5cb3fd;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    .payment-form {
        max-width: 500px;
        margin: 0 auto;
    }

    #payment-message {
        margin-top: 20px;
        color: green;
    }

    #payment-error {
        margin-top: 20px;
        color: red;
    }
    </style>
</head>
<body>
    <div class="payment-form">
        <h2>Stripe Payment Form</h2>
        <form id="payment-form">
            <div>
                <label for="card-element">Credit or Debit Card</label>
                <div id="card-element" class="StripeElement">
                    <!-- A Stripe Element will be inserted here. -->
                </div>
            </div>
            <button id="submit-button" type="submit">Pay $10.00</button>
            <div id="payment-message" style="display:none;"></div>
            <div id="payment-error" style="display:none;"></div>
        </form>
    </div>

    <script>
        // Create a Stripe client (replace with your actual publishable key)
        var stripe = Stripe('pk_test_UziWAgs2hHaRX9ZSpSHlicCx');

        // Create an instance of Elements
        var elements = stripe.elements();

        // Create an instance of the card Element
        var card = elements.create('card', {
            hidePostalCode: true
        });

        // Add the card Element into the `card-element` <div>
        card.mount('#card-element');

        // Handle form submission
        var form = document.getElementById('payment-form');
        var submitButton = document.getElementById('submit-button');
        var paymentMessage = document.getElementById('payment-message');
        var paymentError = document.getElementById('payment-error');

        form.addEventListener('submit', function(event) {
            event.preventDefault();

            // Disable the submit button to prevent multiple clicks
            submitButton.disabled = true;

            // Create PaymentIntent on the server
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'create_payment_intent=1'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.error) {
                    // Show error to your customer
                    paymentError.textContent = data.error;
                    paymentError.style.display = 'block';
                    submitButton.disabled = false;
                } else {
                    var clientSecret = data.client_secret;
                    var orderId = data.order_id;

                    // Confirm the card payment
                    stripe.confirmCardPayment(clientSecret, {
                        payment_method: {
                            card: card,
                        }
                    }).then(function(result) {
                        if (result.error) {
                            // Show error to your customer
                            paymentError.textContent = result.error.message;
                            paymentError.style.display = 'block';
                            submitButton.disabled = false;
                        } else {
                            if (result.paymentIntent.status === 'succeeded') {
                                // Show a success message to your customer
                                paymentMessage.textContent = 'Payment successful! Order ID: ' + orderId;
                                paymentMessage.style.display = 'block';
                                paymentError.style.display = 'none';
                                // Optionally, redirect to a success page
                            } else {
                                paymentError.textContent = 'Payment processing. Please wait.';
                                paymentError.style.display = 'block';
                                submitButton.disabled = false;
                            }
                        }
                    });
                }
            })
            .catch(function(error) {
                paymentError.textContent = 'An error occurred: ' + error.message;
                paymentError.style.display = 'block';
                submitButton.disabled = false;
            });
        });
    </script>
</body>
</html>
