<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Brand.php';
require_once __DIR__ . '/../core/Response.php';

$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    if (!isset($_SESSION['user_id'])) {
        Response::json(401, [
            'success' => false,
            'message' => 'Unauthorized'
        ]);
    }

    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        Response::json(403, [
            'success' => false,
            'message' => 'Access denied: admins only'
        ]);
    }
}

if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');

    Response::json(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

try {
    $database = new Database();
    $pdo = $database->connect();
} catch (Throwable $e) {
    error_log('Brand database connection failed: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'Database connection error'
    ]);
}

try {
    $brand = new Brand($pdo);

    // Validate IDs for single-brand requests.
    $id = null;

    if (
        ($method === 'GET' && isset($_GET['id'])) ||
        in_array($method, ['PUT', 'DELETE'], true)
    ) {
        $id = filter_var(
            $_GET['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false || $id === null) {
            Response::json(400, [
                'success' => false,
                'message' => 'Valid brand ID is required'
            ]);
        }
    }

    // POST and PUT use the same fields and validation.
    if (in_array($method, ['POST', 'PUT'], true)) {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body)) {
            Response::json(400, [
                'success' => false,
                'message' => 'Invalid JSON body'
            ]);
        }

        $name = $body['name'] ?? '';
        $description = $body['description'] ?? '';

        if (!is_string($name) || !is_string($description)) {
            Response::json(422, [
                'success' => false,
                'message' => 'Name and description must be strings'
            ]);
        }

        $name = trim($name);
        $description = trim($description);

        if ($name === '') {
            Response::json(422, [
                'success' => false,
                'message' => 'Brand name is required'
            ]);
        }

        if (mb_strlen($name, 'UTF-8') > 100) {
            Response::json(422, [
                'success' => false,
                'message' => 'Brand name must not exceed 100 characters'
            ]);
        }

        $description = $description === '' ? null : $description;
    }

    switch ($method) {
        case 'GET':
            if ($id !== null) {
                $brandData = $brand->getById($id);

                if ($brandData === null) {
                    Response::json(404, [
                        'success' => false,
                        'message' => 'Brand not found'
                    ]);
                }

                Response::json(200, [
                    'success' => true,
                    'data' => $brandData
                ]);
            }

            $brands = $brand->getAll();

            Response::json(200, [
                'success' => true,
                'count' => count($brands),
                'data' => $brands
            ]);

        case 'POST':
            if (!$brand->addBrand($name, $description)) {
                Response::json(500, [
                    'success' => false,
                    'message' => 'Failed to create brand'
                ]);
            }

            Response::json(201, [
                'success' => true,
                'message' => 'Brand created successfully'
            ]);

        case 'PUT':
            $existingBrand = $brand->getById($id);

            if ($existingBrand === null) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Brand not found'
                ]);
            }

            // Preserve the existing logo when updating name/description.
            $updated = $brand->updateBrand(
                $id,
                $name,
                $description,
                $existingBrand['logo']
            );

            if (!$updated) {
                Response::json(500, [
                    'success' => false,
                    'message' => 'Failed to update brand'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Brand updated successfully'
            ]);

        case 'DELETE':
            if ($brand->getById($id) === null) {
                Response::json(404, [
                    'success' => false,
                    'message' => 'Brand not found'
                ]);
            }

            if (!$brand->deleteBrand($id)) {
                Response::json(500, [
                    'success' => false,
                    'message' => 'Failed to delete brand'
                ]);
            }

            Response::json(200, [
                'success' => true,
                'message' => 'Brand deleted successfully'
            ]);
    }
} catch (PDOException $e) {
    error_log('Brand database error: ' . $e->getMessage());

    $errorCode = (int) ($e->errorInfo[1] ?? 0);

    if ($errorCode === 1062) {
        Response::json(409, [
            'success' => false,
            'message' => 'Brand name already exists'
        ]);
    }

    if ($errorCode === 1451) {
        Response::json(409, [
            'success' => false,
            'message' => 'Cannot delete a brand linked to products'
        ]);
    }

    Response::json(500, [
        'success' => false,
        'message' => 'A database error occurred'
    ]);
} catch (Throwable $e) {
    error_log('Brand API error: ' . $e->getMessage());

    Response::json(500, [
        'success' => false,
        'message' => 'An internal server error occurred'
    ]);
}