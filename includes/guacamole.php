<?php

function getDefaultGuacamoleSettings()
{
    return [
        'enabled' => 0,
        'base_url' => '',
        'launch_url_template' => '',
        'open_in_new_tab' => 1,
    ];
}

function getGuacamoleSettings($db)
{
    $defaultSettings = getDefaultGuacamoleSettings();

    $tableExists = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='guacamole_settings'");
    if (!$tableExists) {
        return $defaultSettings;
    }

    $stmt = $db->prepare('SELECT * FROM guacamole_settings WHERE id = 1');
    $result = $stmt->execute();
    $settings = $result->fetchArray(SQLITE3_ASSOC);

    if ($settings === false) {
        return $defaultSettings;
    }

    return array_merge($defaultSettings, $settings);
}

function getDefaultRemotePort($protocol)
{
    switch ($protocol) {
        case 'rdp':
            return 3389;
        case 'vnc':
            return 5900;
        case 'telnet':
            return 23;
        case 'ssh':
        default:
            return 22;
    }
}

function buildGuacamoleLaunchUrl($subscription, $settings)
{
    if (!isset($settings['enabled']) || (int) $settings['enabled'] !== 1) {
        return null;
    }

    $baseUrl = trim((string) ($settings['base_url'] ?? ''));
    if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
        return null;
    }

    $template = trim((string) ($settings['launch_url_template'] ?? ''));
    if ($template === '') {
        return rtrim($baseUrl, '/');
    }

    $protocol = strtolower(trim((string) ($subscription['remote_protocol'] ?? 'ssh')));
    $host = trim((string) ($subscription['remote_host'] ?? ''));
    $port = (int) ($subscription['remote_port'] ?? 0);
    $username = trim((string) ($subscription['remote_username'] ?? ''));
    $connectionIdentifier = trim((string) ($subscription['guacamole_connection_identifier'] ?? ''));
    $subscriptionId = (int) ($subscription['id'] ?? 0);
    $subscriptionName = trim((string) ($subscription['name'] ?? ''));

    if ($port <= 0) {
        $port = getDefaultRemotePort($protocol);
    }

    $authority = $host;
    if ($username !== '') {
        $authority = $username . '@' . $authority;
    }
    if ($host !== '') {
        $authority .= ':' . $port;
    }

    $quickconnectUri = '';
    if ($host !== '') {
        $quickconnectUri = $protocol . '://' . $authority;
    }

    $baseUrl = rtrim($baseUrl, '/');
    $replacements = [
        '{base_url}' => $baseUrl,
        '{protocol}' => $protocol,
        '{protocol_encoded}' => rawurlencode($protocol),
        '{host}' => $host,
        '{host_encoded}' => rawurlencode($host),
        '{port}' => (string) $port,
        '{port_encoded}' => rawurlencode((string) $port),
        '{username}' => $username,
        '{username_encoded}' => rawurlencode($username),
        '{connection_identifier}' => $connectionIdentifier,
        '{connection_identifier_encoded}' => rawurlencode($connectionIdentifier),
        '{subscription_id}' => (string) $subscriptionId,
        '{subscription_id_encoded}' => rawurlencode((string) $subscriptionId),
        '{subscription_name}' => $subscriptionName,
        '{subscription_name_encoded}' => rawurlencode($subscriptionName),
        '{quickconnect_uri}' => $quickconnectUri,
        '{quickconnect_uri_encoded}' => rawurlencode($quickconnectUri),
    ];

    $resolvedUrl = strtr($template, $replacements);

    if (strpos($resolvedUrl, '{') !== false || strpos($resolvedUrl, '}') !== false) {
        return null;
    }

    if (strpos($resolvedUrl, '#') === 0) {
        $resolvedUrl = $baseUrl . '/' . ltrim($resolvedUrl, '/');
    } elseif (strpos($resolvedUrl, '/#') === 0) {
        $resolvedUrl = $baseUrl . $resolvedUrl;
    } elseif (filter_var($resolvedUrl, FILTER_VALIDATE_URL) === false) {
        $resolvedUrl = $baseUrl . '/' . ltrim($resolvedUrl, '/');
    }

    if (filter_var($resolvedUrl, FILTER_VALIDATE_URL) === false) {
        return null;
    }

    $baseHost = parse_url($baseUrl, PHP_URL_HOST);
    $resolvedHost = parse_url($resolvedUrl, PHP_URL_HOST);
    if ($baseHost === false || $resolvedHost === false || $baseHost !== $resolvedHost) {
        return null;
    }

    return $resolvedUrl;
}
