<?php
// Safe script to ensure core roles exist: admin, agent, client
// Run manually: php Database/ensure_roles.php
require_once __DIR__ . '/connection.php';

$roles = [
    ['role_name' => 'admin', 'description' => 'Platform administrator with full access'],
    ['role_name' => 'agent', 'description' => 'Agent account to manage listings and inquiries'],
    ['role_name' => 'client', 'description' => 'Client account for property browsing and inquiries']
];

foreach ($roles as $r) {
    $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = ? LIMIT 1");
    $stmt->bind_param('s', $r['role_name']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $ins = $conn->prepare("INSERT INTO roles (role_name, description, permissions, is_active) VALUES (?, ?, ?, 1)");
        $empty_json = json_encode(new stdClass());
        $ins->bind_param('sss', $r['role_name'], $r['description'], $empty_json);
        if ($ins->execute()) {
            echo "Inserted role: " . $r['role_name'] . PHP_EOL;
        } else {
            echo "Failed to insert role: " . $r['role_name'] . " - " . $ins->error . PHP_EOL;
        }
        $ins->close();
    } else {
        echo "Role exists: " . $r['role_name'] . PHP_EOL;
    }
    $stmt->close();
}

echo "Done.\n";
