<?php
// notification.php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); // Suppress deprecated and notice warnings

require 'vendor/autoload.php'; // Include the Stripe PHP library
require 'database.php'; // Include your database connection file

use Stripe\Stripe;
use Stripe\Webhook;

// Set your Stripe secret key (replace with your actual secret key)
Stripe::setApiKey('sk_test_sqgB9bTz3DLHGAqrET60cdmM'');

// Replace with your webhook endpoint's secret from Stripe Dashboard
$endpoint_secret = 'whsec_C6Q71FQuk6jRFeBRYxJhHG3Sd7H8osCB';

$payload = @file_get_contents('php://input');
$sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : null;

if ($sig_header === null) {
    // Log the issue and exit if the signature header is missing
    file_put_contents('stripe_webhook.log', "Missing signature header." . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit();
}

// Log payload and signature for debugging
file_put_contents('stripe_webhook.log', "Payload: " . $payload . PHP_EOL, FILE_APPEND);
file_put_contents('stripe_webhook.log', "Signature: " . $sig_header . PHP_EOL, FILE_APPEND);

try {
    // Verify the event by checking the signature
    $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    file_put_contents('stripe_webhook.log', "Signature verification passed." . PHP_EOL, FILE_APPEND);
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    file_put_contents('stripe_webhook.log', "Invalid payload: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    file_put_contents('stripe_webhook.log', "Invalid signature: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event->type) {
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;

        // Retrieve the order_id from the metadata
        $order_id = $paymentIntent->metadata->order_id;
        file_put_contents('stripe_webhook.log', "Payment succeeded for Order ID: " . $order_id . PHP_EOL, FILE_APPEND);

        // Update your order status in the database to 'paid'
        $stmt = $pdo->prepare("UPDATE payments SET status = 'paid', updated_at = NOW() WHERE order_id = :order_id");
        if ($stmt->execute(['order_id' => $order_id])) {
            file_put_contents('stripe_webhook.log', "Order status updated to paid for order_id: " . $order_id . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents('stripe_webhook.log', "Failed to update order status for order_id: " . $order_id . PHP_EOL, FILE_APPEND);
        }
        break;

    case 'payment_intent.payment_failed':
        $paymentIntent = $event->data->object;
        $error = $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->message : 'Unknown error';
        $order_id = $paymentIntent->metadata->order_id;
        file_put_contents('stripe_webhook.log', "Payment failed for Order ID: " . $order_id . ". Error: " . $error . PHP_EOL, FILE_APPEND);

        // Update your order status in the database to 'failed'
        $stmt = $pdo->prepare("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE order_id = :order_id");
        if ($stmt->execute(['order_id' => $order_id])) {
            file_put_contents('stripe_webhook.log', "Order status updated to failed for order_id: " . $order_id . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents('stripe_webhook.log', "Failed to update order status for order_id: " . $order_id . PHP_EOL, FILE_APPEND);
        }
        break;

    default:
        file_put_contents('stripe_webhook.log', "Received unknown event type: " . $event->type . PHP_EOL, FILE_APPEND);
}

http_response_code(200); // Acknowledge receipt of the event
?>
