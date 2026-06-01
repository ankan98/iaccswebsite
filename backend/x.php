<?php
include_once "conn.php";
include_once "config.php";
include_once 'membership-card.php';

$reference_number = $_GET['ref'] ?? '100120260551491';
$stmt = $conn->prepare("SELECT * FROM membership_requests WHERE reference_number = ?");
$stmt->bind_param("s", $reference_number);
$stmt->execute();
$result = $stmt->get_result();
$form_data = $result->fetch_assoc();
$stmt->close();

send_verification_complete_email($form_data);