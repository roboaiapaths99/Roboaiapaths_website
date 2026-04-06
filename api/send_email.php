<?php
require_once 'config.php';

function sendOrderEmail($order)
{
    $to = ADMIN_EMAILS;
    $subject = "New Order Received - " . $order['txnid'];

    $cart = json_decode($order['cart'], true);
    $items_html = "";
    if (is_array($cart)) {
        foreach ($cart as $item) {
            $items_html .= "<li>{$item['name']} - Qty: {$item['quantity']} - Price: Rs. {$item['price']}</li>";
        }
    }

    $message = "
    <html>
    <head>
    <title>New Order - " . $order['txnid'] . "</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background: #006eff; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
    </style>
    </head>
    <body>
    <div class='container'>
        <div class='header'>
            <h2>New Order Received!</h2>
        </div>
        <div class='content'>
            <p><strong>Transaction ID:</strong> {$order['txnid']}</p>
            <p><strong>Name:</strong> {$order['firstname']}</p>
            <p><strong>Email:</strong> {$order['email']}</p>
            <p><strong>Phone:</strong> {$order['phone']}</p>
            <p><strong>Total Amount:</strong> Rs. {$order['amount']}</p>
            <p><strong>Payment Method:</strong> {$order['payment_method']}</p>
            <p><strong>Address:</strong> {$order['address']}, {$order['city']}, {$order['state']} - {$order['zip']}</p>
            
            <table style='width:100%; margin-top:10px; border-collapse:collapse;'>
                <tr style='background:#f0f4ff;'>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>Subtotal</strong></td>
                    <td style='padding:8px; border:1px solid #ddd;'>Rs. " . ($order['subtotal'] ?? $order['amount']) . "</td>
                </tr>" .
                (isset($order['coupon']) && $order['coupon'] ? "
                <tr style='background:#fff3cd;'>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>Coupon ({$order['coupon']})</strong></td>
                    <td style='padding:8px; border:1px solid #ddd; color:#dc3545;'>- Rs. " . ($order['discount'] ?? 0) . "</td>
                </tr>" : "") . "
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>GST (18%)</strong></td>
                    <td style='padding:8px; border:1px solid #ddd;'>Rs. " . ($order['gst'] ?? 0) . "</td>
                </tr>
                <tr style='background:#d4edda;'>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>Final Amount Paid</strong></td>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>Rs. {$order['amount']}</strong></td>
                </tr>
            </table>

            <h3>Order Items:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    ";

    if (is_array($cart)) {
        foreach ($cart as $item) {
            $message .= "<tr>
                                <td>{$item['name']}</td>
                                <td>{$item['quantity']}</td>
                                <td>{$item['price']}</td>
                            </tr>";
        }
    }

    $message .= "
                </tbody>
            </table>
        </div>
        <div class='footer'>
            <p>This is an automated email from RoboAIAPaths.</p>
        </div>
    </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: orders@" . $_SERVER['HTTP_HOST'] . "\r\n";

    @mail($to, $subject, $message, $headers);

    // Also send an email to the user optionally
    $user_subject = "Your Order Confirmation - RoboAIAPaths";
    $user_message = str_replace("New Order Received!", "Thank you for your order!", $message);
    @mail($order['email'], $user_subject, $user_message, $headers);
}
?>