
# Stripe Payment Integration with PHP

This repository demonstrates how to integrate a credit card payment form using Stripe's API with PHP. It includes a basic setup with an `index.php` file containing the payment form and processing logic, and a `notification.php` file to handle webhook events for payment status updates.

## Table of Contents

- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Stripe Webhook Setup](#stripe-webhook-setup)
- [Usage](#usage)
- [Testing](#testing)
- [Security Considerations](#security-considerations)
- [License](#license)

## Introduction

This project provides a simple example of how to:

- Create a credit card payment form using Stripe Elements.
- Process payments on the server side with Stripe's PHP library.
- Handle payment status updates using Stripe webhooks.
- Update payment records in a MySQL database.

## Prerequisites

- PHP 7.2 or higher with the following extensions:
  - `curl`
  - `mbstring`
  - `openssl`
  - `pdo_mysql`
- Composer (for managing PHP dependencies)
- MySQL database
- Stripe account (for API keys and webhook setup)
- Web server (e.g., Apache or Nginx)

## Installation

1. **Clone the Repository**

   ```bash
   git clone https://github.com/Alucard0x1/stripe-payment-integration.git
   cd stripe-payment-integration
   ```

2. **Install Dependencies**

   Install the Stripe PHP library using Composer:

   ```bash
   composer require stripe/stripe-php
   ```

3. **Set Up the Project Files**

   Ensure you have the following files in your project directory:

   - `index.php` – Main file containing the payment form and processing logic.
   - `notification.php` – Script to handle Stripe webhook events.
   - `database.php` – Database connection script.
   - `composer.json` and `composer.lock` – Composer files for dependencies.

## Configuration

### 1. Stripe API Keys

Obtain your Stripe API keys from your Stripe Dashboard:

- **Publishable Key** (`pk_test_...` or `pk_live_...`)
- **Secret Key** (`sk_test_...` or `sk_live_...`)

#### Update `index.php`

In the PHP section at the top of the file, set your Secret Key:

```php
\Stripe\Stripe::setApiKey('sk_test_YOUR_SECRET_KEY');
```

In the JavaScript section, set your Publishable Key:

```javascript
var stripe = Stripe('pk_test_YOUR_PUBLISHABLE_KEY');
```

#### Update `notification.php`

Set your Secret Key:

```php
Stripe::setApiKey('sk_test_YOUR_SECRET_KEY');
```

### 2. Database Configuration

Update `database.php` with your database credentials:

```php
<?php
$host = 'localhost';
$db   = 'your_database';
$user = 'your_username';
$pass = 'your_password';
$charset = 'utf8mb4';
```

## Database Setup

Create a MySQL database and execute the following SQL statements to create the `payments` table:

```sql
CREATE TABLE payments (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(255) NOT NULL,
    amount INT(11) NOT NULL,
    status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_order_id ON payments (order_id);
```

## Stripe Webhook Setup

1. **Set Up Webhook Endpoint**

   - Navigate to **Developers > Webhooks** in your Stripe Dashboard.
   - Click **Add endpoint** and enter the URL to your `notification.php` script (e.g., `https://yourdomain.com/notification.php`).
   - Select the following events to listen for:
     - `payment_intent.succeeded`
     - `payment_intent.payment_failed`
   - Click **Add endpoint**.

2. **Obtain Webhook Signing Secret**

   - After adding the endpoint, click on it to view details.
   - Reveal the **Signing secret** (starts with `whsec_...`).

3. **Update `notification.php`**

   Set the `$endpoint_secret` variable with your webhook signing secret:

   ```php
   $endpoint_secret = 'whsec_YOUR_WEBHOOK_SECRET';
   ```

## Usage

1. **Start Your Web Server**

   Ensure your web server is running and serving your project directory.

2. **Access the Payment Form**

   Open your web browser and navigate to `index.php` (e.g., `http://localhost/stripe-payment-integration/index.php`).

3. **Submit a Payment**

   - Fill in the payment form with test card details.
   - Click **Pay $10.00** to submit the payment.

4. **Check Payment Status**

   - Upon successful payment, a success message with the Order ID will be displayed.
   - The `payments` table in your database will be updated with the payment status.

## Testing

### Test Card Numbers

Use Stripe's test card numbers to simulate payments:

- **Successful Payment:** `4242 4242 4242 4242`
- **Authentication Required:** `4000 0025 0000 3155`
- **Declined Payment:** `4000 0000 0000 9995`

Expiration date can be any future date, and CVC can be any 3-digit number.

### Steps to Test

1. **Successful Payment**

   - Use the successful payment test card.
   - Verify that the payment succeeds.
   - Check that the `payments` table has the status `'paid'`.

2. **Failed Payment**

   - Use the declined payment test card.
   - Verify that the payment fails.
   - Check that the `payments` table has the status `'failed'`.

3. **Webhook Event Handling**

   - Ensure that `notification.php` correctly handles webhook events.
   - Check the `stripe_webhook.log` file for any errors or logs.

## Security Considerations

- **Secure API Keys**

  - Never expose your Secret Key (`sk_test_...`) in client-side code or public repositories.
  - Store sensitive credentials securely, preferably using environment variables.

- **Use HTTPS**

  - Always serve your payment pages over HTTPS to encrypt data transmission.

- **Validate and Sanitize Inputs**

  - Use prepared statements (as shown) to prevent SQL injection attacks.
  - Validate and sanitize any user input.

- **PCI Compliance**

  - Ensure compliance with PCI DSS when handling payment information.
  - Use Stripe Elements to reduce the scope of your PCI compliance obligations.

- **Error Handling**

  - Implement robust error handling.
  - Avoid displaying sensitive error information to end-users.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

**GitHub Repository:** [Alucard0x1/stripe-payment-integration](https://github.com/Alucard0x1/stripe-payment-integration)

Feel free to contribute, report issues, or submit pull requests.
