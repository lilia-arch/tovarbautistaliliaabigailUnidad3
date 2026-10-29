<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'Conexion.php';

$database = new Conexion();
$db = $database->getConexion();

// Recibir los datos enviados por fetch (JS)
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password)) {
    // Verificar que el usuario no exista primero
    $checkQuery = "SELECT id FROM usuarios WHERE username = :username";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":username", $data->username);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        echo json_encode(["success" => false, "mensaje" => "El usuario ya existe."]);
    } else {
        // Insertar nuevo usuario
        $query = "INSERT INTO usuarios (username, password) VALUES (:username, :password)";
        $stmt = $db->prepare($query);
        
        $username = htmlspecialchars(strip_tags($data->username));
        // Encriptar la contraseña (Buenas prácticas)
        $password = password_hash($data->password, PASSWORD_BCRYPT); 

        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $password);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "mensaje" => "Registro exitoso. Ya puedes iniciar sesión."]);
        } else {
            echo json_encode(["success" => false, "mensaje" => "Error al registrar en la BD."]);
        }
    }
} else {
    echo json_encode(["success" => false, "mensaje" => "Faltan datos."]);
}
?>