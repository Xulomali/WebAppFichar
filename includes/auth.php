<?php
function verificar_usuario($email, $password) {
    global $pdo;
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return null;
}
?>
<!-- Realizado por Xulomali_HD (Antonio.R) -->