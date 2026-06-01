<?php
session_start();
include_once "conn.php";

$reference_number = $_GET['ref'] ?? null;
if (isset($_SESSION['reference_number'])) {
    $reference_number = $_SESSION['reference_number'];
    $payment_status = 'failed';

    // Update payment status in the database
    $stmt = $conn->prepare("UPDATE membership_requests SET payment_status = ? WHERE reference_number = ?");
    $stmt->bind_param("ss", $payment_status, $reference_number);
    $stmt->execute();
    $stmt->close();
    unset($_SESSION['reference_number']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Transaction Cancelled</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .message-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .icon-failed {
            font-size: 6rem;
            color: #dc3545; /* Red color for error */
        }
        .message-container h1 {
            font-weight: 700;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #dc3545; /* Optional: Match heading to icon */
        }
        .message-container .lead {
            font-size: 1.2rem;
            color: #6c757d;
        }
        .info-section {
            margin: 30px 0;
            padding: 20px;
            background-color: #fff5f5; /* Light red background */
            border-left: 5px solid #dc3545;
            border-radius: 5px;
            text-align: left;
        }
        .info-section p {
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .info-section strong {
            color: #333;
        }
        .btn-pill {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .btn-retry {
            background-color: #94dc35ff;
            color: white;
        }
        .btn-retry:hover {
            background-color: #187800ff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="message-container">
            <div class="icon-failed">
                <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                </svg>
            </div>
            
            <h1>Payment Failed!</h1>
            <p class="lead">We could not process your transaction.</p>
            
            <div class="info-section">
                <p>The transaction was declined or cancelled. No charges have been deducted from your account.</p>
                <hr>
                <p><strong>Reference Number:</strong> <?php echo isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : 'N/A'; ?></p>
                <p><strong>Reason:</strong> <?php echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Transaction Cancelled by User'; ?></p>
            </div>

            <?php if($reference_number) { ?>
            <a href="<?php echo BASE_URL . '/checkout.php?ref=' . $reference_number; ?>" class="btn btn-retry btn-pill">Try Again</a>
            <?php } ?>
            
            <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary btn-pill">Go Back to Home</a>
        </div>
    </div>
</body>
</html>