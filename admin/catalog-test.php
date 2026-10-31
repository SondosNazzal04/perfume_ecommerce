<?php

require_once __DIR__ . '/../includes/session.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Please log in first.');
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied: admins only.');
}

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientIp, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('This temporary test page is available only from this computer.');
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Temporary Catalog Admin Test</title>
    <style>
        :root {
            font-family: Arial, sans-serif;
            color: #20252b;
            background: #f2f4f6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
        }

        header {
            padding: 28px max(20px, calc((100% - 1180px) / 2));
            background: #202a35;
            color: #fff;
        }

        header h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        header p {
            margin: 0;
            color: #d4dbe3;
        }

        main {
            max-width: 1180px;
            margin: 0 auto;
            padding: 20px;
        }

        .notice {
            padding: 12px 15px;
            border: 1px solid #edcb7d;
            border-radius: 8px;
            background: #fff7df;
            color: #684900;
        }

        nav {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 16px 0;
            padding: 10px;
            border: 1px solid #dfe3e8;
            border-radius: 9px;
            background: rgba(255, 255, 255, .96);
        }

        nav a {
            padding: 8px 11px;
            border-radius: 6px;
            background: #edf1f5;
            color: #263b55;
            text-decoration: none;
            font-size: 14px;
        }

        nav a:hover {
            background: #dce5ef;
        }

        .panel {
            scroll-margin-top: 76px;
            margin: 16px 0;
            padding: 22px;
            border: 1px solid #dfe3e8;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(20, 35, 50, .035);
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        h2 {
            margin: 0;
            font-size: 20px;
        }

        .step {
            color: #7b8794;
            font-size: 13px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            min-width: 0;
            margin-bottom: 14px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid #cbd1d8;
            border-radius: 6px;
            background: #fff;
            color: #20252b;
            font: inherit;
        }

        textarea {
            min-height: 76px;
            resize: vertical;
        }

        input[type="checkbox"] {
            width: auto;
            min-height: auto;
        }

        .check-line {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
        }

        button {
            min-height: 37px;
            padding: 8px 12px;
            border: 0;
            border-radius: 6px;
            background: #263b55;
            color: #fff;
            font: inherit;
            cursor: pointer;
        }

        button:hover {
            filter: brightness(1.08);
        }

        button.secondary {
            background: #687383;
        }

        button.danger {
            background: #a73535;
        }

        button.small {
            min-height: 31px;
            padding: 6px 9px;
            font-size: 13px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 4px;
        }

        .form-actions button+button {
            margin-left: 0;
        }

        .status {
            min-height: 21px;
            margin: 12px 0 0;
            font-size: 14px;
        }

        .status.error {
            color: #a73535;
        }

        .status.success {
            color: #247044;
        }

        .table-wrap {
            overflow-x: auto;
            margin-top: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 9px;
            border-bottom: 1px solid #e5e8ec;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f7f8fa;
            white-space: nowrap;
            font-size: 13px;
        }

        td {
            font-size: 14px;
        }

        .empty {
            color: #66717d;
            text-align: center;
        }

        .category-box {
            max-height: 210px;
            overflow: auto;
            padding: 12px;
            border: 1px solid #cbd1d8;
            border-radius: 6px;
        }

        .category-group {
            margin-bottom: 11px;
        }

        .category-group:last-child {
            margin-bottom: 0;
        }

        .category-group strong {
            display: block;
            margin-bottom: 6px;
            color: #445365;
        }

        .category-option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0 14px 7px 0;
            font-weight: 400;
        }

        .hint {
            margin: 6px 0 0;
            color: #66717d;
            font-size: 13px;
        }

        .inline {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .inline select {
            max-width: 380px;
        }

        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #edf1f5;
            color: #435365;
            font-size: 12px;
        }

        @media (max-width: 760px) {
            main {
                padding: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .panel {
                padding: 16px;
            }

            header {
                padding: 22px 16px;
            }

            .inline {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>Catalog Admin · Test Page</h1>
        <p>Manage the catalog in order: brands and category structure, products, then product sizes and stock.</p>
    </header>

    <main>
        <div class="notice"><strong>Temporary local test page.</strong> This is not the finished admin UI. Authentication is not included.</div>

        <nav aria-label="Catalog sections">
            <a href="#brands">1. Brands</a>
            <a href="#groups">2. Category groups</a>
            <a href="#categories">3. Categories</a>
            <a href="#products">4. Products</a>
            <a href="#images">5. Images</a>
            <a href="#variants">6. Variants</a>
        </nav>

        <section class="panel" id="brands">
            <div class="panel-head">
                <h2>Brands</h2><span class="step">Step 1 · Product maker</span>
            </div>
            <form id="brandForm">
                <input type="hidden" id="brandId">
                <div class="form-grid">
                    <div class="field"><label for="brandName">Name</label><input id="brandName" required maxlength="100" placeholder="e.g. Loewe"></div>
                    <div class="field"><label for="brandDescription">Description</label><input id="brandDescription" placeholder="Optional"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" id="saveBrandButton">Add brand</button>
                    <button type="button" class="secondary" id="cancelBrandButton" hidden>Cancel edit</button>
                </div>
            </form>
            <p class="status" id="brandStatus" role="status"></p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="brandRows"></tbody>
                </table>
            </div>
        </section>

        <section class="panel" id="groups">
            <div class="panel-head">
                <h2>Category groups</h2><span class="step">Step 2 · Filter type, e.g. Gender</span>
            </div>
            <form id="groupForm">
                <input type="hidden" id="editGroupId">
                <div class="form-grid">
                    <div class="field"><label for="groupName">Name</label><input id="groupName" required maxlength="100" placeholder="e.g. Gender"></div>
                    <div class="field"><label for="groupSlug">Slug</label><input id="groupSlug" required maxlength="120" placeholder="e.g. gender"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" id="saveGroupButton">Add group</button>
                    <button type="button" class="secondary" id="cancelGroupButton" hidden>Cancel edit</button>
                </div>
            </form>
            <p class="status" id="groupStatus" role="status"></p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="groupRows"></tbody>
                </table>
            </div>
        </section>

        <section class="panel" id="categories">
            <div class="panel-head">
                <h2>Categories</h2><span class="step">Step 3 · Values inside a group</span>
            </div>
            <form id="categoryForm">
                <input type="hidden" id="categoryId">
                <div class="form-grid">
                    <div class="field"><label for="categoryGroupId">Group</label><select id="categoryGroupId" required>
                            <option value="">Loading groups...</option>
                        </select></div>
                    <div class="field"><label for="categoryName">Name</label><input id="categoryName" required maxlength="100" placeholder="e.g. Women"></div>
                    <div class="field"><label for="categorySlug">Slug</label><input id="categorySlug" required maxlength="120" placeholder="e.g. women"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" id="saveCategoryButton">Add category</button>
                    <button type="button" class="secondary" id="cancelCategoryButton" hidden>Cancel edit</button>
                </div>
            </form>
            <p class="status" id="categoryStatus" role="status"></p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Group</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categoryRows"></tbody>
                </table>
            </div>
        </section>

        <section class="panel" id="products">
            <div class="panel-head">
                <h2>Products</h2><span class="step">Step 4 · Brand + categories</span>
            </div>
            <form id="productForm">
                <input type="hidden" id="productId">
                <div class="form-grid">
                    <div class="field"><label for="productBrandId">Brand</label><select id="productBrandId" required>
                            <option value="">Loading brands...</option>
                        </select></div>
                    <div class="field"><label for="productName">Name</label><input id="productName" required maxlength="150" placeholder="e.g. Sample Perfume"></div>
                    <div class="field"><label for="productSlug">Slug</label><input id="productSlug" required maxlength="180" placeholder="e.g. sample-perfume"></div>
                </div>
                <div class="field"><label for="productDescription">Description</label><textarea id="productDescription" placeholder="Optional product description"></textarea></div>
                <div class="field">
                    <label>Categories</label>
                    <div id="productCategoryOptions" class="category-box">Loading categories...</div>
                    <p class="hint">Choose any categories that apply. Leave empty if you are not assigning categories yet.</p>
                </div>
                <label class="check-line" for="productActive"><input id="productActive" type="checkbox" checked> Active product</label>
                <div class="form-actions">
                    <button type="submit" id="saveProductButton">Add product</button>
                    <button type="button" class="secondary" id="cancelProductButton" hidden>Cancel edit</button>
                </div>
            </form>
            <p class="status" id="productStatus" role="status"></p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Categories</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productRows"></tbody>
                </table>
            </div>
        </section>

        <section class="panel" id="images">
            <div class="panel-head">
                <h2>Product images</h2><span class="step">Step 5 · Upload a file or save an image URL</span>
            </div>
            <div class="field">
                <label for="imageProductId">Choose a product</label>
                <select id="imageProductId">
                    <option value="">Loading products...</option>
                </select>
            </div>
            <form id="imageForm">
                <div class="form-grid">
                    <div class="field"><label for="imageFile">Upload image (JPEG, PNG, WebP · up to 5 MB)</label><input id="imageFile" type="file" accept="image/jpeg,image/png,image/webp"></div>
                    <div class="field"><label for="imageUrl">Or image URL (HTTP/HTTPS)</label><input id="imageUrl" type="url" maxlength="2048" placeholder="https://example.com/perfume.jpg"></div>
                    <div class="field"><label for="imageSortOrder">Sort order</label><input id="imageSortOrder" type="number" min="0" step="1" value="0"></div>
                </div>
                <label class="check-line" for="imageIsPrimary"><input id="imageIsPrimary" type="checkbox"> Make this the primary image</label>
                <p class="hint">Provide either a file or a URL. The first image is primary automatically. Deleting an external URL only removes its database entry.</p>
                <div class="form-actions"><button type="submit">Add image</button><button type="button" class="secondary" id="refreshImagesButton">Refresh images</button></div>
            </form>
            <p class="status" id="imageStatus" role="status"></p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>ID</th>
                            <th>Image source</th>
                            <th>Primary</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="imageRows"></tbody>
                </table>
            </div>
        </section>

        <section class="panel" id="variants">
            <div class="panel-head">
                <h2>Product variants</h2><span class="step">Step 6 · Size, price and stock</span>
            </div>
            <div class="field">
                <label for="variantProductId">Choose a product to manage its variants</label>
                <div class="inline">
                    <select id="variantProductId">
                        <option value="">Loading products...</option>
                    </select>
                    <span class="hint">Each product can have multiple sizes. A size cannot repeat for the same product.</span>
                </div>
            </div>
            <form id="variantForm">
                <input type="hidden" id="variantId">
                <div class="form-grid">
                    <div class="field"><label for="variantSize">Size (ml)</label><input id="variantSize" type="number" min="1" max="65535" step="1" required placeholder="50"></div>
                    <div class="field"><label for="variantPrice">Price</label><input id="variantPrice" type="number" min="0.01" step="0.01" required placeholder="85.00"></div>
                    <div class="field"><label for="variantDiscount">Discount price (optional)</label><input id="variantDiscount" type="number" min="0.01" step="0.01" placeholder="Leave blank for none"></div>
                    <div class="field"><label for="variantStock">Stock</label><input id="variantStock" type="number" min="0" step="1" value="0" required></div>
                </div>
                <div class="form-actions">
                    <button type="submit" id="saveVariantButton">Add variant</button>
                    <button type="button" class="secondary" id="cancelVariantButton" hidden>Cancel edit</button>
                </div>
            </form>
            <p class="status" id="variantStatus" role="status"></p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="variantRows"></tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const api = {
            brands: '../api/brand.php',
            groups: '../api/category-groups.php',
            categories: '../api/category.php',
            products: '../api/products.php',
            variants: '../api/product-variant.php',
            images: '../api/product-images.php'
        };

        const state = {
            brands: [],
            groups: [],
            categories: [],
            products: [],
            variants: []
        };
        let brandSlugEdited = false;
        let groupSlugEdited = false;
        let categorySlugEdited = false;
        let productSlugEdited = false;

        const $ = id => document.getElementById(id);

        async function requestJson(url, options = {}) {
            const response = await fetch(url, options);
            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error(`Server returned a non-JSON response (${response.status}).`);
            }
            if (!response.ok || result.success === false) {
                throw new Error(result.message || `Request failed (${response.status}).`);
            }
            return result;
        }

        function status(name, text, type = '') {
            const element = $(`${name}Status`);
            element.textContent = text;
            element.className = `status ${type}`.trim();
        }

        function slugFrom(value) {
            return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase().trim().replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function appendCell(row, value) {
            const cell = document.createElement('td');
            cell.textContent = value ?? '';
            row.appendChild(cell);
            return cell;
        }

        function appendEmptyRow(tbody, colSpan, text) {
            tbody.replaceChildren();
            const row = document.createElement('tr');
            const cell = appendCell(row, text);
            cell.colSpan = colSpan;
            cell.className = 'empty';
            tbody.appendChild(row);
        }

        function makeActionButton(text, className, action) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `small ${className || ''}`.trim();
            button.textContent = text;
            button.addEventListener('click', action);
            return button;
        }

        function renderBrands() {
            const tbody = $('brandRows');
            tbody.replaceChildren();
            if (!state.brands.length) return appendEmptyRow(tbody, 4, 'No brands found.');

            state.brands.forEach(brand => {
                const row = document.createElement('tr');
                appendCell(row, brand.id);
                appendCell(row, brand.name);
                appendCell(row, brand.description || '—');
                const actions = appendCell(row, '');
                actions.append(
                    makeActionButton('Edit', '', () => editBrand(brand)),
                    makeActionButton('Delete', 'danger', () => deleteBrand(brand))
                );
                tbody.appendChild(row);
            });
        }

        function fillBrandSelect() {
            const select = $('productBrandId');
            const previous = select.value;
            select.replaceChildren(new Option('Select a brand', ''));
            state.brands.forEach(brand => select.add(new Option(brand.name, String(brand.id))));
            if (state.brands.some(brand => String(brand.id) === previous)) select.value = previous;
        }

        async function loadBrands() {
            const result = await requestJson(api.brands);
            state.brands = result.data || [];
            renderBrands();
            fillBrandSelect();
        }

        function resetBrandForm() {
            $('brandForm').reset();
            $('brandId').value = '';
            $('saveBrandButton').textContent = 'Add brand';
            $('cancelBrandButton').hidden = true;
            brandSlugEdited = false;
        }

        function editBrand(brand) {
            $('brandId').value = brand.id;
            $('brandName').value = brand.name;
            $('brandDescription').value = brand.description || '';
            $('saveBrandButton').textContent = 'Save brand';
            $('cancelBrandButton').hidden = false;
            status('brand', `Editing brand #${brand.id}.`);
            $('brands').scrollIntoView({
                behavior: 'smooth'
            });
        }

        async function deleteBrand(brand) {
            if (!confirm(`Delete brand "${brand.name}"? Brands used by products cannot be deleted.`)) return;
            try {
                const result = await requestJson(`${api.brands}?id=${encodeURIComponent(brand.id)}`, {
                    method: 'DELETE'
                });
                status('brand', result.message || 'Brand deleted.', 'success');
                await Promise.all([loadBrands(), loadProducts()]);
            } catch (error) {
                status('brand', error.message, 'error');
            }
        }

        $('brandForm').addEventListener('submit', async event => {
            event.preventDefault();
            const id = $('brandId').value;
            const name = $('brandName').value.trim();
            const description = $('brandDescription').value.trim();
            try {
                const result = await requestJson(id ? `${api.brands}?id=${encodeURIComponent(id)}` : api.brands, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        description
                    })
                });
                resetBrandForm();
                await Promise.all([loadBrands(), loadProducts()]);
                status('brand', result.message || 'Brand saved.', 'success');
            } catch (error) {
                status('brand', error.message, 'error');
            }
        });
        $('cancelBrandButton').addEventListener('click', resetBrandForm);

        function renderGroups() {
            const tbody = $('groupRows');
            tbody.replaceChildren();
            if (!state.groups.length) return appendEmptyRow(tbody, 4, 'No category groups found.');

            state.groups.forEach(group => {
                const row = document.createElement('tr');
                appendCell(row, group.id);
                appendCell(row, group.name);
                appendCell(row, group.slug);
                const actions = appendCell(row, '');
                actions.append(
                    makeActionButton('Edit', '', () => editGroup(group)),
                    makeActionButton('Delete', 'danger', () => deleteGroup(group))
                );
                tbody.appendChild(row);
            });
        }

        function fillGroupSelect() {
            const select = $('categoryGroupId');
            const previous = select.value;
            select.replaceChildren(new Option('Select a group', ''));
            state.groups.forEach(group => select.add(new Option(group.name, String(group.id))));
            if (state.groups.some(group => String(group.id) === previous)) select.value = previous;
        }

        async function loadGroups() {
            const result = await requestJson(api.groups);
            state.groups = result.data || [];
            renderGroups();
            fillGroupSelect();
        }

        function resetGroupForm() {
            $('groupForm').reset();
            $('editGroupId').value = '';
            $('saveGroupButton').textContent = 'Add group';
            $('cancelGroupButton').hidden = true;
            groupSlugEdited = false;
        }

        function editGroup(group) {
            $('editGroupId').value = group.id;
            $('groupName').value = group.name;
            $('groupSlug').value = group.slug;
            $('saveGroupButton').textContent = 'Save group';
            $('cancelGroupButton').hidden = false;
            groupSlugEdited = true;
            status('group', `Editing category group #${group.id}.`);
            $('groups').scrollIntoView({
                behavior: 'smooth'
            });
        }

        async function deleteGroup(group) {
            if (!confirm(`Delete group "${group.name}"? Groups containing categories cannot be deleted.`)) return;
            try {
                const result = await requestJson(`${api.groups}?id=${encodeURIComponent(group.id)}`, {
                    method: 'DELETE'
                });
                status('group', result.message || 'Group deleted.', 'success');
                await Promise.all([loadGroups(), loadCategories()]);
            } catch (error) {
                status('group', error.message, 'error');
            }
        }

        $('groupName').addEventListener('input', () => {
            if (!groupSlugEdited) $('groupSlug').value = slugFrom($('groupName').value);
        });
        $('groupSlug').addEventListener('input', () => {
            groupSlugEdited = true;
        });
        $('groupForm').addEventListener('submit', async event => {
            event.preventDefault();
            const id = $('editGroupId').value;
            const name = $('groupName').value.trim();
            const slug = $('groupSlug').value.trim();
            try {
                const result = await requestJson(id ? `${api.groups}?id=${encodeURIComponent(id)}` : api.groups, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        slug
                    })
                });
                resetGroupForm();
                await Promise.all([loadGroups(), loadCategories()]);
                status('group', result.message || 'Group saved.', 'success');
            } catch (error) {
                status('group', error.message, 'error');
            }
        });
        $('cancelGroupButton').addEventListener('click', resetGroupForm);

        function renderCategories() {
            const tbody = $('categoryRows');
            tbody.replaceChildren();
            if (!state.categories.length) return appendEmptyRow(tbody, 5, 'No categories found.');

            state.categories.forEach(category => {
                const row = document.createElement('tr');
                appendCell(row, category.id);
                appendCell(row, category.group_name);
                appendCell(row, category.name);
                appendCell(row, category.slug);
                const actions = appendCell(row, '');
                actions.append(
                    makeActionButton('Edit', '', () => editCategory(category)),
                    makeActionButton('Delete', 'danger', () => deleteCategory(category))
                );
                tbody.appendChild(row);
            });
        }

        function renderProductCategoryOptions(selectedIds = []) {
            const container = $('productCategoryOptions');
            container.replaceChildren();
            if (!state.categories.length) {
                container.textContent = 'No categories found. Add groups and categories first.';
                return;
            }

            const selected = new Set(selectedIds.map(Number));
            const grouped = new Map();
            state.categories.forEach(category => {
                const key = category.group_name || 'Other';
                if (!grouped.has(key)) grouped.set(key, []);
                grouped.get(key).push(category);
            });

            grouped.forEach((items, groupName) => {
                const group = document.createElement('div');
                group.className = 'category-group';
                const heading = document.createElement('strong');
                heading.textContent = groupName;
                group.appendChild(heading);
                items.forEach(category => {
                    const label = document.createElement('label');
                    label.className = 'category-option';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'productCategoryIds';
                    checkbox.value = String(category.id);
                    checkbox.checked = selected.has(Number(category.id));
                    const text = document.createElement('span');
                    text.textContent = category.name;
                    label.append(checkbox, text);
                    group.appendChild(label);
                });
                container.appendChild(group);
            });
        }

        async function loadCategories() {
            const result = await requestJson(api.categories);
            state.categories = result.data || [];
            renderCategories();
            renderProductCategoryOptions(selectedProductCategoryIds());
        }

        function resetCategoryForm() {
            $('categoryForm').reset();
            $('categoryId').value = '';
            $('saveCategoryButton').textContent = 'Add category';
            $('cancelCategoryButton').hidden = true;
            categorySlugEdited = false;
        }

        function editCategory(category) {
            $('categoryId').value = category.id;
            $('categoryGroupId').value = String(category.group_id);
            $('categoryName').value = category.name;
            $('categorySlug').value = category.slug;
            $('saveCategoryButton').textContent = 'Save category';
            $('cancelCategoryButton').hidden = false;
            categorySlugEdited = true;
            status('category', `Editing category #${category.id}.`);
            $('categories').scrollIntoView({
                behavior: 'smooth'
            });
        }

        async function deleteCategory(category) {
            if (!confirm(`Delete category "${category.name}"? Categories linked to products cannot be deleted.`)) return;
            try {
                const result = await requestJson(`${api.categories}?id=${encodeURIComponent(category.id)}`, {
                    method: 'DELETE'
                });
                status('category', result.message || 'Category deleted.', 'success');
                await Promise.all([loadCategories(), loadProducts()]);
            } catch (error) {
                status('category', error.message, 'error');
            }
        }

        $('categoryName').addEventListener('input', () => {
            if (!categorySlugEdited) $('categorySlug').value = slugFrom($('categoryName').value);
        });
        $('categorySlug').addEventListener('input', () => {
            categorySlugEdited = true;
        });
        $('categoryForm').addEventListener('submit', async event => {
            event.preventDefault();
            const id = $('categoryId').value;
            const payload = {
                group_id: Number($('categoryGroupId').value),
                name: $('categoryName').value.trim(),
                slug: $('categorySlug').value.trim()
            };
            if (!payload.group_id) return status('category', 'Choose a category group.', 'error');
            try {
                const result = await requestJson(id ? `${api.categories}?id=${encodeURIComponent(id)}` : api.categories, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                resetCategoryForm();
                await Promise.all([loadCategories(), loadProducts()]);
                status('category', result.message || 'Category saved.', 'success');
            } catch (error) {
                status('category', error.message, 'error');
            }
        });
        $('cancelCategoryButton').addEventListener('click', resetCategoryForm);

        function selectedProductCategoryIds() {
            return Array.from(document.querySelectorAll('input[name="productCategoryIds"]:checked'))
                .map(input => Number(input.value));
        }

        function renderProducts() {
            const tbody = $('productRows');
            tbody.replaceChildren();
            if (!state.products.length) return appendEmptyRow(tbody, 6, 'No products found.');

            const categoryNames = new Map(state.categories.map(category => [
                Number(category.id), `${category.group_name}: ${category.name}`
            ]));

            state.products.forEach(product => {
                const row = document.createElement('tr');
                appendCell(row, product.id);
                appendCell(row, product.name);
                appendCell(row, product.brand_name);
                appendCell(row, (product.category_ids || []).map(id => categoryNames.get(Number(id))).filter(Boolean).join(', ') || '—');
                appendCell(row, Number(product.is_active) === 1 ? 'Yes' : 'No');
                const actions = appendCell(row, '');
                actions.append(
                    makeActionButton('Edit', '', () => editProduct(product)),
                    makeActionButton('Variants', 'secondary', () => manageVariants(product)),
                    makeActionButton('Delete', 'danger', () => deleteProduct(product))
                );
                tbody.appendChild(row);
            });
        }

        function fillVariantProductSelect() {
            const select = $('variantProductId');
            const previous = select.value;
            select.replaceChildren(new Option('Select a product', ''));
            state.products.forEach(product => select.add(new Option(`${product.name} (#${product.id})`, String(product.id))));
            if (state.products.some(product => String(product.id) === previous)) select.value = previous;

            const imageSelect = $('imageProductId');
            const selectedImageProduct = imageSelect.value;
            imageSelect.replaceChildren(new Option('Select a product', ''));
            state.products.forEach(product => imageSelect.add(new Option(`${product.name} (#${product.id})`, String(product.id))));
            if (state.products.some(product => String(product.id) === selectedImageProduct)) imageSelect.value = selectedImageProduct;
        }

        async function loadProducts() {
            const result = await requestJson(api.products);
            state.products = result.data || [];
            renderProducts();
            fillVariantProductSelect();
        }

        function resetProductForm() {
            $('productForm').reset();
            $('productId').value = '';
            $('productActive').checked = true;
            renderProductCategoryOptions();
            $('saveProductButton').textContent = 'Add product';
            $('cancelProductButton').hidden = true;
            productSlugEdited = false;
        }

        function editProduct(product) {
            $('productId').value = product.id;
            $('productBrandId').value = String(product.brand_id);
            $('productName').value = product.name;
            $('productSlug').value = product.slug;
            $('productDescription').value = product.description || '';
            $('productActive').checked = Number(product.is_active) === 1;
            renderProductCategoryOptions(product.category_ids || []);
            $('saveProductButton').textContent = 'Save product';
            $('cancelProductButton').hidden = false;
            productSlugEdited = true;
            status('product', `Editing product #${product.id}.`);
            $('products').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function manageVariants(product) {
            $('variantProductId').value = String(product.id);
            loadVariants();
            $('variants').scrollIntoView({
                behavior: 'smooth'
            });
        }

        async function deleteProduct(product) {
            if (!confirm(`Delete product "${product.name}"? Products linked to variants, orders or other records may be protected.`)) return;
            try {
                const result = await requestJson(`${api.products}?id=${encodeURIComponent(product.id)}`, {
                    method: 'DELETE'
                });
                status('product', result.message || 'Product deleted.', 'success');
                await loadProducts();
            } catch (error) {
                status('product', error.message, 'error');
            }
        }

        $('productName').addEventListener('input', () => {
            if (!productSlugEdited) $('productSlug').value = slugFrom($('productName').value);
        });
        $('productSlug').addEventListener('input', () => {
            productSlugEdited = true;
        });
        $('productForm').addEventListener('submit', async event => {
            event.preventDefault();
            const id = $('productId').value;
            const payload = {
                brand_id: Number($('productBrandId').value),
                name: $('productName').value.trim(),
                slug: $('productSlug').value.trim(),
                description: $('productDescription').value.trim() || null,
                is_active: $('productActive').checked,
                category_ids: selectedProductCategoryIds()
            };
            if (!payload.brand_id) return status('product', 'Choose a brand.', 'error');
            try {
                const result = await requestJson(id ? `${api.products}?id=${encodeURIComponent(id)}` : api.products, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                resetProductForm();
                await loadProducts();
                status('product', result.message || 'Product saved.', 'success');
            } catch (error) {
                status('product', error.message, 'error');
            }
        });
        $('cancelProductButton').addEventListener('click', resetProductForm);

        function renderVariants() {
            const tbody = $('variantRows');
            tbody.replaceChildren();
            if (!state.variants.length) return appendEmptyRow(tbody, 6, 'No variants found for this product.');

            state.variants.forEach(variant => {
                const row = document.createElement('tr');
                appendCell(row, variant.id);
                appendCell(row, `${variant.size_ml} ml`);
                appendCell(row, variant.price);
                appendCell(row, variant.discount_price ?? '—');
                appendCell(row, variant.stock);
                const actions = appendCell(row, '');
                actions.append(
                    makeActionButton('Edit', '', () => editVariant(variant)),
                    makeActionButton('Delete', 'danger', () => deleteVariant(variant))
                );
                tbody.appendChild(row);
            });
        }

        async function loadVariants() {
            const productId = $('variantProductId').value;
            state.variants = [];
            if (!productId) {
                renderVariants();
                return;
            }
            try {
                const result = await requestJson(`${api.variants}?product_id=${encodeURIComponent(productId)}`);
                state.variants = result.data || [];
                renderVariants();
                status('variant', `Loaded variants for product #${productId}.`, 'success');
            } catch (error) {
                renderVariants();
                status('variant', error.message, 'error');
            }
        }

        function resetVariantForm() {
            $('variantForm').reset();
            $('variantId').value = '';
            $('variantStock').value = '0';
            $('saveVariantButton').textContent = 'Add variant';
            $('cancelVariantButton').hidden = true;
        }

        function editVariant(variant) {
            $('variantId').value = variant.id;
            $('variantProductId').value = String(variant.product_id);
            $('variantSize').value = variant.size_ml;
            $('variantPrice').value = variant.price;
            $('variantDiscount').value = variant.discount_price ?? '';
            $('variantStock').value = variant.stock;
            $('saveVariantButton').textContent = 'Save variant';
            $('cancelVariantButton').hidden = false;
            status('variant', `Editing variant #${variant.id}.`);
        }

        async function deleteVariant(variant) {
            if (!confirm(`Delete the ${variant.size_ml} ml variant? Variants used in carts or orders cannot be deleted.`)) return;
            try {
                const result = await requestJson(`${api.variants}?id=${encodeURIComponent(variant.id)}`, {
                    method: 'DELETE'
                });
                resetVariantForm();
                status('variant', result.message || 'Variant deleted.', 'success');
                await loadVariants();
            } catch (error) {
                status('variant', error.message, 'error');
            }
        }

        $('variantProductId').addEventListener('change', () => {
            resetVariantForm();
            loadVariants();
        });
        $('variantForm').addEventListener('submit', async event => {
            event.preventDefault();
            const id = $('variantId').value;
            const productId = Number($('variantProductId').value);
            if (!productId) return status('variant', 'Choose a product first.', 'error');

            const payload = {
                product_id: productId,
                size_ml: Number($('variantSize').value),
                price: Number($('variantPrice').value),
                discount_price: $('variantDiscount').value.trim() === '' ? null : Number($('variantDiscount').value),
                stock: Number($('variantStock').value)
            };

            try {
                const result = await requestJson(id ? `${api.variants}?id=${encodeURIComponent(id)}` : api.variants, {
                    method: id ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                resetVariantForm();
                await loadVariants();
                status('variant', result.message || 'Variant saved.', 'success');
            } catch (error) {
                status('variant', error.message, 'error');
            }
        });
        $('cancelVariantButton').addEventListener('click', resetVariantForm);

        function renderImages(images) {
            const tbody = $('imageRows');
            tbody.replaceChildren();
            if (!images.length) return appendEmptyRow(tbody, 6, 'No images found for this product.');

            images.forEach(image => {
                const row = document.createElement('tr');
                const previewCell = document.createElement('td');
                const preview = document.createElement('img');
                preview.src = image.image_path;
                preview.alt = 'Product image';
                preview.loading = 'lazy';
                preview.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:6px;background:#eee';
                preview.onerror = () => {
                    preview.alt = 'Preview unavailable';
                    preview.removeAttribute('src');
                };
                previewCell.appendChild(preview);
                row.appendChild(previewCell);
                appendCell(row, image.id);
                const sourceCell = appendCell(row, image.image_path);
                sourceCell.style.maxWidth = '300px';
                sourceCell.style.wordBreak = 'break-all';
                appendCell(row, Number(image.is_primary) === 1 ? 'Yes' : 'No');
                appendCell(row, image.sort_order);
                const actions = appendCell(row, '');
                if (Number(image.is_primary) !== 1) {
                    actions.appendChild(makeActionButton('Make primary', 'secondary', () => setPrimaryImage(image)));
                }
                actions.appendChild(makeActionButton('Delete', 'danger', () => deleteImage(image)));
                tbody.appendChild(row);
            });
        }

        async function loadImages() {
            const productId = $('imageProductId').value;
            if (!productId) {
                renderImages([]);
                return;
            }
            try {
                const result = await requestJson(`${api.images}?product_id=${encodeURIComponent(productId)}`);
                renderImages(result.data || []);
                status('image', `Loaded images for product #${productId}.`, 'success');
            } catch (error) {
                renderImages([]);
                status('image', error.message, 'error');
            }
        }

        async function setPrimaryImage(image) {
            try {
                const result = await requestJson(`${api.images}?id=${encodeURIComponent(image.id)}`, {
                    method: 'PUT'
                });
                status('image', result.message || 'Primary image updated.', 'success');
                await loadImages();
            } catch (error) {
                status('image', error.message, 'error');
            }
        }

        async function deleteImage(image) {
            if (!confirm('Delete this image entry? External image files are not deleted.')) return;
            try {
                const result = await requestJson(`${api.images}?id=${encodeURIComponent(image.id)}`, {
                    method: 'DELETE'
                });
                status('image', result.message || 'Image deleted.', 'success');
                await loadImages();
            } catch (error) {
                status('image', error.message, 'error');
            }
        }

        $('imageProductId').addEventListener('change', loadImages);
        $('refreshImagesButton').addEventListener('click', loadImages);
        $('imageForm').addEventListener('submit', async event => {
            event.preventDefault();
            const productId = $('imageProductId').value;
            const file = $('imageFile').files[0];
            const imageUrl = $('imageUrl').value.trim();
            if (!productId) return status('image', 'Choose a product first.', 'error');
            if ((!file && !imageUrl) || (file && imageUrl)) {
                return status('image', 'Choose either an image file or a URL, not both.', 'error');
            }

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('is_primary', $('imageIsPrimary').checked ? 'true' : 'false');
            formData.append('sort_order', $('imageSortOrder').value || '0');
            if (file) formData.append('image', file);
            else formData.append('image_url', imageUrl);

            try {
                const result = await requestJson(api.images, {
                    method: 'POST',
                    body: formData
                });
                $('imageForm').reset();
                $('imageSortOrder').value = '0';
                status('image', result.message || 'Image added.', 'success');
                await loadImages();
            } catch (error) {
                status('image', error.message, 'error');
            }
        });

        async function loadPage() {
            try {
                await Promise.all([loadBrands(), loadGroups()]);
                await loadCategories();
                await loadProducts();
                await loadVariants();
                await loadImages();
            } catch (error) {
                status('product', `Could not load catalog data: ${error.message}`, 'error');
            }
        }

        loadPage();
    </script>
</body>

</html>