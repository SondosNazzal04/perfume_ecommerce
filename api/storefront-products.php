<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    Response::json(405, ['success' => false, 'message' => 'Method not allowed']);
}

try {
    $pdo = (new Database())->connect();
    $where = ['p.is_active = 1'];
    $params = [];

    $productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (isset($_GET['id']) && $productId === false) {
        Response::json(400, ['success' => false, 'message' => 'Invalid product ID']);
    }
    if ($productId !== false && $productId !== null) {
        $where[] = 'p.id = :product_id';
        $params[':product_id'] = $productId;
    }

    $query = trim((string) ($_GET['q'] ?? ''));
    if ($query !== '') {
        $where[] = '(p.name LIKE :q_name OR b.name LIKE :q_brand OR p.description LIKE :q_description)';
        $params[':q_name'] = '%' . $query . '%';
        $params[':q_brand'] = '%' . $query . '%';
        $params[':q_description'] = '%' . $query . '%';
    }

    $brandId = filter_var($_GET['brand'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($brandId !== false && $brandId !== null) {
        $where[] = 'p.brand_id = :brand_id';
        $params[':brand_id'] = $brandId;
    }

    $categoryId = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (isset($_GET['category_id']) && trim((string) $_GET['category_id']) !== '' && $categoryId === false) {
        Response::json(400, ['success' => false, 'message' => 'Invalid category ID']);
    }
    if ($categoryId !== false && $categoryId !== null) {
        $where[] = 'EXISTS (
            SELECT 1 FROM product_categories pc
            WHERE pc.product_id = p.id AND pc.category_id = :category_id
        )';
        $params[':category_id'] = $categoryId;
    }

    $groupId = filter_var($_GET['group_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (isset($_GET['group_id']) && trim((string) $_GET['group_id']) !== '' && $groupId === false) {
        Response::json(400, ['success' => false, 'message' => 'Invalid category group ID']);
    }
    if ($groupId !== false && $groupId !== null) {
        $where[] = 'EXISTS (
            SELECT 1 FROM product_categories pc
            JOIN categories c ON c.id = pc.category_id
            WHERE pc.product_id = p.id AND c.group_id = :group_id
        )';
        $params[':group_id'] = $groupId;
    }

    $gender = strtolower(trim((string) ($_GET['gender'] ?? '')));
    if (in_array($gender, ['him', 'her', 'unisex'], true)) {
        $genderTerm = $gender === 'him' ? 'men' : ($gender === 'her' ? 'women' : 'unisex');
        $genderAlt = $gender === 'her' ? 'her' : ($gender === 'him' ? 'him' : 'unisex');
        $where[] = '(p.name LIKE :gender_name OR EXISTS (
            SELECT 1 FROM product_categories pc
            JOIN categories c ON c.id = pc.category_id
            WHERE pc.product_id = p.id AND (c.slug LIKE :gender_slug OR c.slug LIKE :gender_alt OR c.name LIKE :gender_label)
        ))';
        $params[':gender_name'] = '%' . $genderTerm . '%';
        $params[':gender_slug'] = '%' . $genderTerm . '%';
        $params[':gender_alt'] = '%' . $genderAlt . '%';
        $params[':gender_label'] = '%' . $genderTerm . '%';
    }

    $category = strtolower(trim((string) ($_GET['category'] ?? '')));
    if ($category !== '') {
        $categoryTerm = $category === 'sets' ? 'set' : $category;
        $where[] = '(EXISTS (
            SELECT 1 FROM product_categories pc
            JOIN categories c ON c.id = pc.category_id
            JOIN category_groups cg ON cg.id = c.group_id
            WHERE pc.product_id = p.id
              AND (c.slug LIKE :category_slug OR c.name LIKE :category_name OR cg.name LIKE :group_name)
        ) OR p.name LIKE :product_category_name OR COALESCE(p.description, "") LIKE :product_category_description)';
        $params[':category_slug'] = '%' . $category . '%';
        $params[':category_name'] = '%' . $categoryTerm . '%';
        $params[':group_name'] = '%' . $categoryTerm . '%';
        $params[':product_category_name'] = '%' . $categoryTerm . '%';
        $params[':product_category_description'] = '%' . $categoryTerm . '%';
    }

    $whereSql = implode(' AND ', $where);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN brands b ON b.id = p.brand_id WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sort = (string) ($_GET['sort'] ?? 'featured');
    $orderBy = match ($sort) {
        'newest' => 'p.created_at DESC, p.id DESC',
        'price-low' => 'COALESCE((SELECT MIN(COALESCE(v.discount_price, v.price)) FROM product_variants v WHERE v.product_id = p.id AND v.stock > 0), 999999) ASC, p.id DESC',
        'price-high' => 'COALESCE((SELECT MIN(COALESCE(v.discount_price, v.price)) FROM product_variants v WHERE v.product_id = p.id AND v.stock > 0), 0) DESC, p.id DESC',
        default => 'p.updated_at DESC, p.id DESC'
    };

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(48, max(1, (int) ($_GET['limit'] ?? 24)));
    $offset = ($page - 1) * $limit;
    $sql = "SELECT p.id,p.brand_id,b.name AS brand_name,p.name,p.slug,p.description,p.created_at,
            (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.is_primary DESC,pi.sort_order ASC,pi.id ASC LIMIT 1) AS image_path,
            (SELECT v.id FROM product_variants v WHERE v.product_id=p.id AND v.stock>0 ORDER BY v.size_ml ASC LIMIT 1) AS variant_id,
            (SELECT v.size_ml FROM product_variants v WHERE v.product_id=p.id AND v.stock>0 ORDER BY v.size_ml ASC LIMIT 1) AS size_ml,
            (SELECT COALESCE(v.discount_price,v.price) FROM product_variants v WHERE v.product_id=p.id AND v.stock>0 ORDER BY v.size_ml ASC LIMIT 1) AS price,
            (SELECT v.price FROM product_variants v WHERE v.product_id=p.id AND v.stock>0 ORDER BY v.size_ml ASC LIMIT 1) AS regular_price
            FROM products p JOIN brands b ON b.id=p.brand_id
            WHERE $whereSql ORDER BY $orderBy LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items !== []) {
        $productIds = array_map(static fn(array $item): int => (int) $item['id'], $items);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $categoryStmt = $pdo->prepare(
            "SELECT pc.product_id, c.id, c.name, c.slug, cg.name AS group_name
             FROM product_categories pc
             JOIN categories c ON c.id = pc.category_id
             JOIN category_groups cg ON cg.id = c.group_id
             WHERE pc.product_id IN ($placeholders)
             ORDER BY cg.name, c.name"
        );
        $categoryStmt->execute($productIds);
        $categoriesByProduct = [];
        foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $categoryRow) {
            $categoriesByProduct[(int) $categoryRow['product_id']][] = [
                'id' => (int) $categoryRow['id'],
                'name' => $categoryRow['name'],
                'slug' => $categoryRow['slug'],
                'group_name' => $categoryRow['group_name']
            ];
        }
        foreach ($items as &$item) {
            $item['categories'] = $categoriesByProduct[(int) $item['id']] ?? [];
        }
        unset($item);
    }

    Response::json(200, [
        'success' => true,
        'count' => count($items),
        'total' => $total,
        'page' => $page,
        'per_page' => $limit,
        'pages' => (int) ceil($total / $limit),
        'data' => $items
    ]);
} catch (Throwable $e) {
    error_log('Storefront product query failed: ' . $e->getMessage());
    Response::json(500, ['success' => false, 'message' => 'Unable to load fragrances right now']);
}
