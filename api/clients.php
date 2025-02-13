<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Obtener cliente específico
                $query = "SELECT * FROM clients WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($client) {
                    echo json_encode(['success' => true, 'client' => $client]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
                }
            } else {
                // Listar todos los clientes
                $query = "SELECT * FROM clients ORDER BY company";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'clients' => $clients]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['id'])) {
                // Actualizar cliente existente
                $query = "UPDATE clients SET 
                         name = ?, company = ?, contact_email = ?,
                         contact_phone = ?, address = ?
                         WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $data['name'],
                    $data['company'],
                    $data['contact_email'],
                    $data['contact_phone'],
                    $data['address'],
                    $data['id']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente actualizado correctamente'
                ]);
            } else {
                // Crear nuevo cliente
                $query = "INSERT INTO clients 
                         (name, company, contact_email, contact_phone, address)
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $data['name'],
                    $data['company'],
                    $data['contact_email'],
                    $data['contact_phone'],
                    $data['address']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente creado correctamente',
                    'client_id' => $db->lastInsertId()
                ]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                // Verificar si tiene hornos asociados
                $query = "SELECT COUNT(*) FROM furnaces WHERE client_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede eliminar el cliente porque tiene hornos asociados'
                    ]);
                    break;
                }

                // Eliminar cliente
                $query = "DELETE FROM clients WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente eliminado correctamente'
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de cliente no proporcionado'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}