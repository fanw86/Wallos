<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint_admin.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);

$enabled = isset($data['enabled']) ? (int) $data['enabled'] : 0;
$baseUrl = isset($data['baseUrl']) ? trim($data['baseUrl']) : '';
$launchUrlTemplate = isset($data['launchUrlTemplate']) ? trim($data['launchUrlTemplate']) : '';
$openInNewTab = isset($data['openInNewTab']) ? (int) $data['openInNewTab'] : 1;

if ($enabled === 1) {
    if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
        die(json_encode([
            "success" => false,
            "message" => translate('guacamole_invalid_base_url', $i18n)
        ]));
    }

    if ($launchUrlTemplate === '') {
        die(json_encode([
            "success" => false,
            "message" => translate('guacamole_invalid_launch_template', $i18n)
        ]));
    }
}

$checkStmt = $db->prepare('SELECT COUNT(*) as count FROM guacamole_settings WHERE id = 1');
$result = $checkStmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] > 0) {
    $stmt = $db->prepare('UPDATE guacamole_settings SET
            enabled = :enabled,
            base_url = :baseUrl,
            launch_url_template = :launchUrlTemplate,
            open_in_new_tab = :openInNewTab,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = 1');
} else {
    $stmt = $db->prepare('INSERT INTO guacamole_settings (
            id, enabled, base_url, launch_url_template, open_in_new_tab
        ) VALUES (
            1, :enabled, :baseUrl, :launchUrlTemplate, :openInNewTab
        )');
}

$stmt->bindParam(':enabled', $enabled, SQLITE3_INTEGER);
$stmt->bindParam(':baseUrl', $baseUrl, SQLITE3_TEXT);
$stmt->bindParam(':launchUrlTemplate', $launchUrlTemplate, SQLITE3_TEXT);
$stmt->bindParam(':openInNewTab', $openInNewTab, SQLITE3_INTEGER);
$stmt->execute();

die(json_encode([
    "success" => true,
    "message" => translate('success', $i18n)
]));
