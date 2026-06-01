<?php
session_start();
include_once "config.php";
include_once "conn.php";

if (empty($_GET['ref'])) {
    header('Location: ' . BASE_URL);
    exit();
}

$reference_number = $_GET['ref'];
$_SESSION['reference_number'] = $reference_number;

// Fetch the record from the database
$stmt = $conn->prepare("SELECT * FROM membership_requests WHERE reference_number = ?");
$stmt->bind_param("s", $reference_number);
$stmt->execute();
$result = $stmt->get_result();
$form_data = $result->fetch_assoc();
$stmt->close();


if($form_data === null) {
    header('Location: ' . BASE_URL);
    exit();
}

if($form_data['payment_status'] === 'Paid') {
    header('Location: ' . BASE_URL . '/thankyou.php?ref=' . urlencode($reference_number) . '&payment_id=' . urlencode($form_data['payment_id']));
    exit();
}

function generateHash($params, $salt)
{
    // Extract parameters or use empty string if not provided
    $key = $params['key'];
    $txnid = $params['txnid'];
    $amount = $params['amount'];
    $productinfo = $params['productinfo'];
    $firstname = $params['firstname'];
    $email = $params['email'];
    $udf1 = isset($params['udf1']) ? $params['udf1'] : '';
    $udf2 = isset($params['udf2']) ? $params['udf2'] : '';
    $udf3 = isset($params['udf3']) ? $params['udf3'] : '';
    $udf4 = isset($params['udf4']) ? $params['udf4'] : '';
    $udf5 = isset($params['udf5']) ? $params['udf5'] : '';

    // Construct hash string with exact parameter sequence
    $hashString = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' .
        $firstname . '|' . $email . '|' . $udf1 . '|' . $udf2 . '|' .
        $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $salt;

    // Generate hash and convert to lowercase
    return strtolower(hash('sha512', $hashString));
}

$params = [
    'key' => PAYU_KEY,
    'txnid' => substr(hash('sha256', mt_rand() . microtime()), 0, 20),
    'amount' => $form_data['amount'],
    'productinfo' => 'Mmbership Fee',
    'firstname' => $form_data['name'],
    'email' => $form_data['email'],
    'phone' => $form_data['mobile'],
    'udf1' => $form_data['reference_number'],
    // udf2, udf3, udf4, udf5 not provided - will be empty strings
];
$salt = PAYU_SALT;

$hash = generateHash($params, $salt);
// echo 'Generated Hash: ' . $hash;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <style>
        #paymentOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-box {
            background: #ffffff;
            padding: 25px 35px;
            border-radius: 8px;
            text-align: center;
            font-family: Arial, sans-serif;
            width: 300px;
        }

        .payment-box p {
            margin: 15px 0 5px;
            font-size: 16px;
            font-weight: 600;
        }

        .payment-box small {
            color: #666;
        }

        .spinner {
            width: 45px;
            height: 45px;
            border: 4px solid #ddd;
            border-top: 4px solid #3399cc;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

</head>

<body onload="document.forms.payu.submit()">
    <!-- Payment Processing Overlay -->
    <div id="paymentOverlay" style="display:none;">
        <div class="payment-box">
            <div class="spinner"></div>
            <p>Please wait… Opening secure payment window</p>
            <small>Do not refresh or close this page</small>
        </div>
    </div>
    <form name="payu" method="post" action="https://test.payu.in/_payment">
        <input type="hidden" name="key" value="<?php echo $params['key']; ?>">
        <input type="hidden" name="txnid" value="<?php echo $params['txnid']; ?>">
        <input type="hidden" name="amount" value="<?php echo $params['amount']; ?>">
        <input type="hidden" name="productinfo" value="<?php echo $params['productinfo']; ?>">
        <input type="hidden" name="firstname" value="<?php echo $params['firstname']; ?>">
        <input type="hidden" name="email" value="<?php echo $params['email']; ?>">
        <input type="hidden" name="phone" value="<?php echo $params['phone']; ?>">
        <input type="hidden" name="udf1" value="<?php echo $params['udf1']; ?>">
        <input type="hidden" name="surl" value="<?php echo BASE_URL ?>/success.php">
        <input type="hidden" name="furl" value="<?php echo BASE_URL ?>/fail.php">
        <input type="hidden" name="hash" value="<?php echo $hash; ?>">
        <input type="submit" value="Submit" style="opacity: 0.01;" />
    </form>
    <script>
        document.getElementById('paymentOverlay').style.display = 'flex';
    </script>
</body>

</html>