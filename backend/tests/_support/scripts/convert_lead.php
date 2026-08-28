<?php

/**
 * Standalone helper for the concurrency test (tests/api/LeadConvertConcurrencyTest.php).
 * Runs as a separate OS process so the two `sp_lead_convert` calls genuinely
 * overlap — proving the `SELECT ... FOR UPDATE` lock in §8.2, not just the
 * idempotency guard a same-process sequential call would exercise.
 *
 * argv: host user pass db port leadId dealName
 */
[, $host, $user, $pass, $db, $port, $leadId, $dealName] = $argv;

$mysqli = new mysqli($host, $user, $pass, $db, (int) $port);

$stmt = $mysqli->prepare('CALL sp_lead_convert(?, NULL, NULL, ?, 100.00, 1, @o_company, @o_contact, @o_deal, @o_status, @o_message)');
$stmt->bind_param('is', $leadId, $dealName);
$stmt->execute();
$stmt->close();

while ($mysqli->more_results()) {
    $mysqli->next_result();
}

$result = $mysqli->query('SELECT @o_status AS statusCode, @o_deal AS dealId')->fetch_assoc();

echo json_encode($result);
