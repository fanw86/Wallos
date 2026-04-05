<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/guacamole.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);
$subscriptionId = isset($data['id']) ? (int) $data['id'] : 0;

if ($subscriptionId <= 0) {
    die(json_encode([
        "success" => false,
        "message" => translate('error', $i18n)
    ]));
}

$query = "SELECT id, name, remote_access_enabled, remote_protocol, remote_host, remote_port, remote_username, guacamole_connection_identifier
    FROM subscriptions
    WHERE id = :subscriptionId AND user_id = :userId";
$stmt = $db->prepare($query);
$stmt->bindValue(':subscriptionId', $subscriptionId, SQLITE3_INTEGER);
$stmt->bindValue(':userId', $userId, SQLITE3_INTEGER);
$result = $stmt->execute();
$subscription = $result->fetchArray(SQLITE3_ASSOC);

if ($subscription === false || (int) $subscription['remote_access_enabled'] !== 1) {
    die(json_encode([
        "success" => false,
        "message" => translate('guacamole_remote_access_not_configured', $i18n)
    ]));
}

$settings = getGuacamoleSettings($db);
$launchUrl = buildGuacamoleLaunchUrl($subscription, $settings);

if ($launchUrl === null) {
    die(json_encode([
        "success" => false,
        "message" => translate('guacamole_launch_not_available', $i18n)
    ]));
}

die(json_encode([
    "success" => true,
    "url" => $launchUrl,
    "open_in_new_tab" => (int) ($settings['open_in_new_tab'] ?? 1),
]));
