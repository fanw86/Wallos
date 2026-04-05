<?php
// This migration adds Guacamole SSO redirect settings and per-subscription remote access metadata.

$subscriptionColumns = [
    'remote_access_enabled' => "ALTER TABLE subscriptions ADD COLUMN remote_access_enabled INTEGER DEFAULT 0",
    'remote_protocol' => "ALTER TABLE subscriptions ADD COLUMN remote_protocol TEXT DEFAULT 'ssh'",
    'remote_host' => "ALTER TABLE subscriptions ADD COLUMN remote_host TEXT DEFAULT ''",
    'remote_port' => "ALTER TABLE subscriptions ADD COLUMN remote_port INTEGER DEFAULT 22",
    'remote_username' => "ALTER TABLE subscriptions ADD COLUMN remote_username TEXT DEFAULT ''",
    'guacamole_connection_identifier' => "ALTER TABLE subscriptions ADD COLUMN guacamole_connection_identifier TEXT DEFAULT ''",
];

foreach ($subscriptionColumns as $columnName => $query) {
    $columnQuery = $db->query("SELECT * FROM pragma_table_info('subscriptions') WHERE name='$columnName'");
    $columnRequired = $columnQuery->fetchArray(SQLITE3_ASSOC) === false;

    if ($columnRequired) {
        $db->exec($query);
    }
}

$tableQuery = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='guacamole_settings'");
$tableExists = $tableQuery->fetchArray(SQLITE3_ASSOC);

if ($tableExists === false) {
    $db->exec("
        CREATE TABLE guacamole_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            enabled INTEGER NOT NULL DEFAULT 0,
            base_url TEXT NOT NULL DEFAULT '',
            launch_url_template TEXT NOT NULL DEFAULT '',
            open_in_new_tab INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
}
