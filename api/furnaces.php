<?php


$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                if (isset($_GET['action']) && $_GET['action'] === 'layout') {
                    // Obtener layout del horno
                    $query = "SELECT layout_image FROM furnaces WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_GET['id']]);
                    $layout = $stmt->fetchColumn();
                    
                    echo json_encode([
                        'success' => true,
                        'layout_url' => $layout ? "uploads/layouts/{$layout}" : null
                    ]);
                } else {
                    // Obtener horno específico con info del cliente
                    $query = "SELECT f.*, c.company 
                             FROM furnaces f
                             LEFT JOIN clients c ON f.client_id = c.id
                             WHERE f.id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_GET['id']]);
                    $furnace = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($furnace) {
                        echo json_encode(['success' => true, 'furnace' => $furnace]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Horno no encontrado']);
                    }
                }
            } else {
                // Listar hornos (con filtro opcional por cliente)
                $query = "SELECT f.*, c.company 
                         FROM furnaces f
                         LEFT JOIN clients c ON f.client_id = c.id";
                
                if (isset($_GET['client_id'])) {
                    $query .= " WHERE f.client_id = ?";
                    $params = [$_GET['client_id']];
                } else {
                    $params = [];
                }
                
                $query .= " ORDER BY c.company, f.name";
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $furnaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'furnaces' => $furnaces]);
            }
            break;

        case 'POST':
            if (isset($_FILES['layout_image'])) {
                $uploadResult = upload_image($_FILES['layout_image'], '../uploads/layouts/');
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $layoutImage = $uploadResult['filename'];
            } else {
                $layoutImage = null;
            }

            if (isset($_POST['id'])) {
                // Actualizar horno existente
                $query = "UPDATE furnaces SET 
                         name = ?, client_id = ?, location = ?";
                $params = [
                    $_POST['name'],
                    $_POST['client_id'],
                    $_POST['location']
                ];

                if ($layoutImage) {
                    $query .= ", layout_image = ?";
                    $params[] = $layoutImage;
                }

                $query .= " WHERE id = ?";
                $params[] = $_POST['id'];

                $stmt = $db->prepare($query);
                $stmt->execute($params);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Horno actualizado correctamente'
                ]);
            } else {
                // Crear nuevo horno
                $query = "INSERT INTO furnaces 
                         (name, client_id, location, layout_image)
                         VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $_POST['name'],
                    $_POST['client_id'],
                    $_POST['location'],
                    $layoutImage
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Horno creado correctamente',
                    'furnace_id' => $db->lastInsertId()
                ]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                // Verificar si tiene inspecciones asociadas
                $query = "SELECT COUNT(*) FROM inspections WHERE furnace_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede eliminar el horno porque tiene inspecciones asociadas'
                    ]);
                    break;
                }

                // Eliminar zonas asociadas
                $query = "DELETE FROM zones WHERE furnace_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);

                // Eliminar horno
                $query = "DELETE FROM furnaces WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Horno eliminado correctamente'
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de horno no proporcionado'
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