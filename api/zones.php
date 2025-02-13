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
                // Obtener zona específica
                $query = "SELECT * FROM zones WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                $zone = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($zone) {
                    echo json_encode(['success' => true, 'zone' => $zone]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Zona no encontrada']);
                }
            } elseif (isset($_GET['furnace_id'])) {
                // Obtener todas las zonas de un horno
                $query = "SELECT * FROM zones WHERE furnace_id = ? ORDER BY name";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['furnace_id']]);
                $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'zones' => $zones]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Se requiere furnace_id o id de zona'
                ]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['id'])) {
                // Actualizar zona existente
                $query = "UPDATE zones SET 
                         name = ?, 
                         description = ?,
                         position_x = ?,
                         position_y = ?
                         WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $data['name'],
                    $data['description'] ?? null,
                    $data['position_x'],
                    $data['position_y'],
                    $data['id']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Zona actualizada correctamente'
                ]);
            } else {
                // Crear nueva zona
                $query = "INSERT INTO zones 
                         (furnace_id, name, description, position_x, position_y)
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $data['furnace_id'],
                    $data['name'],
                    $data['description'] ?? null,
                    $data['position_x'],
                    $data['position_y']
                ]);
                
                $zoneId = $db->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Zona creada correctamente',
                    'zone_id' => $zoneId
                ]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                // Verificar si tiene imágenes asociadas
                $query = "SELECT COUNT(*) FROM inspection_images WHERE zone_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede eliminar la zona porque tiene imágenes asociadas'
                    ]);
                    break;
                }

                // Eliminar zona
                $query = "DELETE FROM zones WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Zona eliminada correctamente'
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de zona no proporcionado'
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