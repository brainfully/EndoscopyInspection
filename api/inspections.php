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
                // Obtener inspección específica con todas sus imágenes
                $query = "SELECT i.*, f.name as furnace_name, c.company 
                         FROM inspections i
                         JOIN furnaces f ON i.furnace_id = f.id
                         JOIN clients c ON f.client_id = c.id
                         WHERE i.id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['id']]);
                $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($inspection) {
                    // Obtener imágenes de la inspección
                    $query = "SELECT ii.*, z.name as zone_name 
                             FROM inspection_images ii
                             LEFT JOIN zones z ON ii.zone_id = z.id
                             WHERE ii.inspection_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_GET['id']]);
                    $inspection['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'inspection' => $inspection]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Inspección no encontrada']);
                }
            } elseif (isset($_GET['furnace_id'])) {
                // Listar inspecciones de un horno específico
                $query = "SELECT i.*, 
                         (SELECT COUNT(*) FROM inspection_images WHERE inspection_id = i.id) as image_count
                         FROM inspections i
                         WHERE i.furnace_id = ?
                         ORDER BY i.inspection_date DESC";
                $stmt = $db->prepare($query);
                $stmt->execute([$_GET['furnace_id']]);
                $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'inspections' => $inspections]);
            } else {
                // Listar todas las inspecciones
                $query = "SELECT i.*, f.name as furnace_name, c.company,
                         (SELECT COUNT(*) FROM inspection_images WHERE inspection_id = i.id) as image_count
                         FROM inspections i
                         JOIN furnaces f ON i.furnace_id = f.id
                         JOIN clients c ON f.client_id = c.id
                         ORDER BY i.inspection_date DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'inspections' => $inspections]);
            }
            break;

        case 'POST':
            $db->beginTransaction();
            
            try {
                // Crear nueva inspección
                $query = "INSERT INTO inspections 
                         (furnace_id, inspection_date, inspector, notes, status)
                         VALUES (?, ?, ?, ?, 'completed')";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $_POST['furnace_id'],
                    $_POST['inspection_date'],
                    $_POST['inspector'],
                    $_POST['notes'] ?? null
                ]);
                
                $inspectionId = $db->lastInsertId();

                // Procesar imágenes por zona
                $query = "SELECT id FROM zones WHERE furnace_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_POST['furnace_id']]);
                $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($zones as $zone) {
                    $zoneId = $zone['id'];
                    $imageKey = "zone_{$zoneId}_images";
                    
                    if (isset($_FILES[$imageKey])) {
                        foreach ($_FILES[$imageKey]['tmp_name'] as $index => $tmpName) {
                            $imageFile = [
                                'name' => $_FILES[$imageKey]['name'][$index],
                                'type' => $_FILES[$imageKey]['type'][$index],
                                'tmp_name' => $tmpName,
                                'error' => $_FILES[$imageKey]['error'][$index],
                                'size' => $_FILES[$imageKey]['size'][$index]
                            ];

                            $uploadResult = upload_image($imageFile, '../uploads/inspections/');
                            if ($uploadResult['success']) {
                                // Guardar referencia en la base de datos
                                $query = "INSERT INTO inspection_images 
                                         (inspection_id, zone_id, image_path, notes)
                                         VALUES (?, ?, ?, ?)";
                                $stmt = $db->prepare($query);
                                $stmt->execute([
                                    $inspectionId,
                                    $zoneId,
                                    $uploadResult['filename'],
                                    $_POST["zone_{$zoneId}_notes"] ?? null
                                ]);
                            }
                        }
                    }
                }

                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Inspección creada correctamente',
                    'inspection_id' => $inspectionId
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $db->beginTransaction();
                
                try {
                    // Eliminar imágenes físicas
                    $query = "SELECT image_path FROM inspection_images WHERE inspection_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_GET['id']]);
                    
                    while ($image = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $imagePath = "../uploads/inspections/{$image['image_path']}";
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }

                    // Eliminar registros de imágenes
                    $query = "DELETE FROM inspection_images WHERE inspection_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_GET['id']]);

                    // Eliminar inspección
                    $query = "DELETE FROM inspections WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_GET['id']]);

                    $db->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Inspección eliminada correctamente'
                    ]);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de inspección no proporcionado'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'