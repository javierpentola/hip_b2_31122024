<?php
// functions.php

function logActivity($pdo, $admin_id, $action) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (:admin_id, :action)");
        $stmt->execute([
            'admin_id' => $admin_id,
            'action' => $action
        ]);
    } catch (PDOException $e) {
        // Manejar el error si es necesario (opcional)
    }
}
?>
