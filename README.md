# 📦 Warehouse API Documentation

## Base URL

```
/api/warehouses
```

---

## 📋 Standard CRUD Operations

### 1. Get All Warehouses

**GET** `/api/warehouses`

**Response Success (200):**

```json
{
    "success": true,
    "message": "Warehouses retrieved successfully",
    "data": [
        {
            "id": "uuid",
            "slug": "warehouse-jakarta",
            "name": "Warehouse Jakarta",
            "phone": "081234567890",
            "alamat": "Jl. Sudirman No. 123",
            "description": "Gudang utama Jakarta",
            "thumbnail": "http://localhost/storage/warehouses/image.jpg",
            "created_at": "2025-11-28 10:00:00",
            "updated_at": "2025-11-28 10:00:00",
            "total_products": 5
        }
    ]
}
```

---

### 2. Get Warehouse Detail

**GET** `/api/warehouses/{slug}`

**Response Success (200):**

```json
{
    "success": true,
    "message": "Warehouse detail retrieved",
    "data": {
        "id": "uuid",
        "slug": "warehouse-jakarta",
        "name": "Warehouse Jakarta",
        "phone": "081234567890",
        "alamat": "Jl. Sudirman No. 123",
        "description": "Gudang utama Jakarta",
        "thumbnail": "http://localhost/storage/warehouses/image.jpg",
        "created_at": "2025-11-28 10:00:00",
        "updated_at": "2025-11-28 10:00:00",
        "products": [
            {
                "id": "product-uuid",
                "slug": "product-slug",
                "name": "Product Name",
                "thumbnail": "url",
                "price": 150000,
                "stock": 100,
                "category": {
                    "id": "category-uuid",
                    "slug": "category-slug",
                    "name": "Category Name"
                }
            }
        ],
        "total_products": 1
    }
}
```

**Response Error (404):**

```json
{
    "success": false,
    "message": "Warehouse not found",
    "data": null
}
```

---

### 3. Create Warehouse

**POST** `/api/warehouses`

**Request Body (multipart/form-data):**

```json
{
    "name": "Warehouse Surabaya",
    "phone": "081234567890",
    "alamat": "Jl. Tunjungan No. 456",
    "description": "Gudang cabang Surabaya",
    "thumbnail": "file.jpg",
    "products": [
        {
            "product_id": "product-uuid-1",
            "stock": 100
        },
        {
            "product_id": "product-uuid-2",
            "stock": 50
        }
    ]
}
```

**Validation Rules:**

- `name`: required, string, max 255 chars
- `phone`: required, string, max 20 chars
- `alamat`: required, string
- `description`: optional, string
- `thumbnail`: optional, image (jpg,jpeg,png,webp), max 2MB
- `products`: optional, array
- `products.*.product_id`: required if products exists, UUID exists in products
  table
- `products.*.stock`: required if products exists, integer, min 0

**Response Success (201):**

```json
{
    "success": true,
    "message": "Warehouse created successfully",
    "data": {
        "id": "uuid",
        "slug": "warehouse-surabaya",
        "name": "Warehouse Surabaya",
        "phone": "081234567890",
        "alamat": "Jl. Tunjungan No. 456",
        "description": "Gudang cabang Surabaya",
        "thumbnail": "url",
        "created_at": "2025-11-28 10:00:00",
        "updated_at": "2025-11-28 10:00:00"
    }
}
```

---

### 4. Update Warehouse

**PUT/PATCH** `/api/warehouses/{slug}`

**Request Body (multipart/form-data):**

```json
{
    "name": "Warehouse Surabaya Updated",
    "phone": "081234567899",
    "alamat": "Jl. Tunjungan No. 789",
    "description": "Updated description",
    "thumbnail": "new-file.jpg",
    "products": [
        {
            "product_id": "product-uuid-1",
            "stock": 150
        }
    ]
}
```

**Note:** Semua field optional (menggunakan `sometimes` validation)

**Response Success (200):**

```json
{
    "success": true,
    "message": "Warehouse updated successfully",
    "data": { ... }
}
```

---

### 5. Delete Warehouse

**DELETE** `/api/warehouses/{slug}`

**Response Success (200):**

```json
{
    "success": true,
    "message": "Warehouse deleted successfully",
    "data": null
}
```

**Note:**

- Soft delete (data tidak benar-benar dihapus)
- Thumbnail akan dihapus dari storage
- Semua relasi products akan di-detach

---

## 🔗 Product Management Operations

### 6. Add Products to Warehouse

**POST** `/api/warehouses/{slug}/products`

**Request Body:**

```json
{
    "products": [
        {
            "product_id": "product-uuid-1",
            "stock": 100
        },
        {
            "product_id": "product-uuid-2",
            "stock": 50
        }
    ]
}
```

**Validation Rules:**

- `products`: required, array, min 1 item
- `products.*.product_id`: required, UUID exists in products table
- `products.*.stock`: required, integer, min 0

**Response Success (200):**

```json
{
    "success": true,
    "message": "Products added to warehouse successfully",
    "data": {
        "id": "uuid",
        "slug": "warehouse-jakarta",
        "products": [...]
    }
}
```

---

### 7. Update Product Stock

**PUT** `/api/warehouses/{slug}/products/{productId}/stock`

**Request Body:**

```json
{
    "stock": 200
}
```

**Response Success (200):**

```json
{
    "success": true,
    "message": "Product stock updated successfully",
    "data": { ... }
}
```

---

### 8. Remove Products from Warehouse

**DELETE** `/api/warehouses/{slug}/products`

**Request Body (optional):**

```json
{
    "product_ids": ["product-uuid-1", "product-uuid-2"]
}
```

**Note:**

- Jika `product_ids` kosong/null: semua products akan di-remove
- Jika `product_ids` diisi: hanya products dengan ID tersebut yang di-remove

**Response Success (200):**

```json
{
    "success": true,
    "message": "Products removed from warehouse successfully",
    "data": { ... }
}
```

---

## 🔧 Usage Examples (cURL)

### Create Warehouse with Products

```bash
curl -X POST http://localhost/api/warehouses \
  -H "Content-Type: multipart/form-data" \
  -F "name=Warehouse Jakarta" \
  -F "phone=081234567890" \
  -F "alamat=Jl. Sudirman No. 123" \
  -F "description=Gudang utama" \
  -F "thumbnail=@/path/to/image.jpg" \
  -F "products[0][product_id]=product-uuid-1" \
  -F "products[0][stock]=100" \
  -F "products[1][product_id]=product-uuid-2" \
  -F "products[1][stock]=50"
```

### Update Product Stock

```bash
curl -X PUT http://localhost/api/warehouses/warehouse-jakarta/products/product-uuid-1/stock \
  -H "Content-Type: application/json" \
  -d '{"stock": 200}'
```

### Add Products

```bash
curl -X POST http://localhost/api/warehouses/warehouse-jakarta/products \
  -H "Content-Type: application/json" \
  -d '{
    "products": [
      {"product_id": "product-uuid-3", "stock": 75}
    ]
  }'
```

---

## ⚠️ Error Responses

### Validation Error (422)

```json
{
    "message": "The name field is required.",
    "errors": {
        "name": ["Nama warehouse wajib diisi"]
    }
}
```

### Not Found (404)

```json
{
    "success": false,
    "message": "Warehouse not found",
    "data": null
}
```

### Server Error (500)

```json
{
    "success": false,
    "message": "Failed to create warehouse",
    "data": null
}
```

---

## 📊 Database Schema

### warehouses table

```sql
- id (uuid, primary)
- slug (string, unique)
- name (string, indexed)
- phone (string, indexed)
- alamat (text)
- description (text, nullable)
- thumbnail (string, nullable)
- deleted_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

### warehouse_product pivot table

```sql
- warehouse_id (uuid, foreign)
- product_id (uuid, foreign)
- stock (integer)
- created_at (timestamp)
- updated_at (timestamp)
```

# 🔄 Merchant Product API - Complete Documentation

## Overview

API ini mengelola pergerakan produk antara **Warehouse** dan **Merchant**, serta
transfer antar merchant.

**Key Features:**

- ✅ Assign product dari warehouse ke merchant (stock warehouse berkurang)
- ✅ Return product dari merchant ke warehouse (stock merchant berkurang)
- ✅ Transfer product antar merchant
- ✅ Manual product management (attach/detach/update stock)
- ✅ Stock movement history tracking

---

## 📋 API Endpoints

### 1. Manual Product Management

#### 1.1 Get All Products in Merchant

**GET** `/api/merchants/{slug}/products`

**Response:**

```json
{
    "success": true,
    "message": "Merchant products retrieved successfully",
    "data": {
        "merchant": {
            "id": "merchant-uuid",
            "slug": "toko-elektronik",
            "name": "Toko Elektronik",
            "keeper": {
                "id": "user-uuid",
                "name": "John Doe"
            }
        },
        "products": [
            {
                "id": "product-uuid",
                "slug": "laptop-gaming",
                "name": "Laptop Gaming",
                "thumbnail": "url",
                "price": 15000000,
                "stock": 50,
                "category": {
                    "id": "cat-uuid",
                    "slug": "electronics",
                    "name": "Electronics"
                }
            }
        ],
        "total_products": 1
    }
}
```

---

#### 1.2 Attach Products (Manual - Tanpa Warehouse)

**POST** `/api/merchants/{slug}/products`

**Request Body:**

```json
{
    "products": [
        {
            "product_id": "product-uuid-1",
            "stock": 100
        },
        {
            "product_id": "product-uuid-2",
            "stock": 50
        }
    ]
}
```

**Use Case:** Menambah produk manual ke merchant tanpa mempengaruhi warehouse
stock.

---

#### 1.3 Update Product Stock (Manual)

**PUT/PATCH** `/api/merchants/{slug}/products/{productId}/stock`

**Request Body:**

```json
{
    "stock": 200
}
```

**Use Case:** Adjust stock manual (misalnya ada kerusakan, kehilangan, dll)

---

#### 1.4 Detach Product

**DELETE** `/api/merchants/{slug}/products/{productId}`

**Use Case:** Hapus produk dari merchant

---

### 2. Warehouse Integration

#### 2.1 Assign Product dari Warehouse ke Merchant

**POST** `/api/merchant-products/assign-from-warehouse`

**Description:**

- Memindahkan produk dari warehouse ke merchant
- Stock di warehouse **berkurang**
- Stock di merchant **bertambah**
- Jika produk sudah ada di merchant, stock akan ditambahkan

**Request Body:**

```json
{
    "warehouse_id": "warehouse-uuid",
    "merchant_id": "merchant-uuid",
    "product_id": "product-uuid",
    "stock": 50
}
```

**Validation Rules:**

- `warehouse_id`: required, exists in warehouses table
- `merchant_id`: required, exists in merchants table
- `product_id`: required, exists in products table
- `stock`: required, integer, min 1

**Response Success (200):**

```json
{
    "success": true,
    "message": "Product assigned from warehouse to merchant successfully",
    "data": {
        "id": "merchant-uuid",
        "slug": "toko-elektronik",
        "name": "Toko Elektronik",
        "products": [
            {
                "id": "product-uuid",
                "name": "Laptop Gaming",
                "stock": 50,
                "category": {...}
            }
        ]
    }
}
```

**Response Error - Insufficient Stock (422):**

```json
{
    "success": false,
    "message": "The given data was invalid",
    "data": {
        "stock": ["Stok di warehouse tidak mencukupi. Tersedia: 30"]
    }
}
```

**Response Error - Product Not in Warehouse (422):**

```json
{
    "success": false,
    "message": "The given data was invalid",
    "data": {
        "product_id": ["Produk tidak ditemukan di warehouse ini"]
    }
}
```

---

#### 2.2 Return Product dari Merchant ke Warehouse

**POST** `/api/merchant-products/return-to-warehouse`

**Description:**

- Mengembalikan produk dari merchant ke warehouse
- Stock di merchant **berkurang** (atau product di-detach jika stock jadi 0)
- Stock di warehouse **bertambah**

**Request Body:**

```json
{
    "warehouse_id": "warehouse-uuid",
    "merchant_id": "merchant-uuid",
    "product_id": "product-uuid",
    "stock": 20
}
```

**Validation Rules:**

- `warehouse_id`: required, exists
- `merchant_id`: required, exists
- `product_id`: required, exists
- `stock`: required, integer, min 1

**Response Success (200):**

```json
{
    "success": true,
    "message": "Product returned from merchant to warehouse successfully",
    "data": {
        "id": "merchant-uuid",
        "slug": "toko-elektronik",
        "name": "Toko Elektronik",
        "products": [...]
    }
}
```

**Response Error - Insufficient Stock (422):**

```json
{
    "success": false,
    "message": "The given data was invalid",
    "data": {
        "stock": ["Stok di merchant tidak mencukupi. Tersedia: 10"]
    }
}
```

---

#### 2.3 Transfer Product Antar Merchant

**POST** `/api/merchant-products/transfer`

**Description:**

- Transfer produk dari merchant A ke merchant B
- Stock di merchant source **berkurang**
- Stock di merchant target **bertambah**

**Request Body:**

```json
{
    "source_merchant_id": "merchant-uuid-1",
    "target_merchant_id": "merchant-uuid-2",
    "product_id": "product-uuid",
    "stock": 30
}
```

**Validation Rules:**

- `source_merchant_id`: required, exists
- `target_merchant_id`: required, exists, different from source
- `product_id`: required, exists
- `stock`: required, integer, min 1

**Response Success (200):**

```json
{
    "success": true,
    "message": "Product transferred between merchants successfully",
    "data": {
        "source_merchant": {
            "id": "merchant-uuid-1",
            "name": "Toko A",
            "products": [...]
        },
        "target_merchant": {
            "id": "merchant-uuid-2",
            "name": "Toko B",
            "products": [...]
        }
    }
}
```

---

#### 2.4 Get Stock Movement History

**GET** `/api/merchant-products/{merchantId}/products/{productId}/movements`

**Description:** Melihat riwayat pergerakan stock produk di merchant

**Response:**

```json
{
    "success": true,
    "message": "Stock movement history retrieved successfully",
    "data": {
        "merchant": {
            "id": "merchant-uuid",
            "name": "Toko Elektronik"
        },
        "product": {
            "id": "product-uuid",
            "name": "Laptop Gaming",
            "current_stock": 50
        },
        "movements": []
    }
}
```

---

## 🔧 Usage Examples (cURL)

### Example 1: Assign Product dari Warehouse

```bash
curl -X POST http://localhost/api/merchant-products/assign-from-warehouse \
  -H "Content-Type: application/json" \
  -d '{
    "warehouse_id": "550e8400-e29b-41d4-a716-446655440001",
    "merchant_id": "550e8400-e29b-41d4-a716-446655440002",
    "product_id": "550e8400-e29b-41d4-a716-446655440003",
    "stock": 50
  }'
```

### Example 2: Return Product ke Warehouse

```bash
curl -X POST http://localhost/api/merchant-products/return-to-warehouse \
  -H "Content-Type: application/json" \
  -d '{
    "warehouse_id": "550e8400-e29b-41d4-a716-446655440001",
    "merchant_id": "550e8400-e29b-41d4-a716-446655440002",
    "product_id": "550e8400-e29b-41d4-a716-446655440003",
    "stock": 20
  }'
```

### Example 3: Transfer Antar Merchant

```bash
curl -X POST http://localhost/api/merchant-products/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "source_merchant_id": "550e8400-e29b-41d4-a716-446655440002",
    "target_merchant_id": "550e8400-e29b-41d4-a716-446655440004",
    "product_id": "550e8400-e29b-41d4-a716-446655440003",
    "stock": 30
  }'
```

### Example 4: Get Products in Merchant

```bash
curl -X GET http://localhost/api/merchants/toko-elektronik/products
```

---

## 📊 Business Logic Flow

### Flow 1: Assign Product (Warehouse → Merchant)

```
1. Validasi warehouse_id exists
2. Validasi product ada di warehouse
3. Cek stock warehouse >= requested stock
4. Validasi merchant_id exists
5. Cek apakah product sudah ada di merchant:
   - Jika ya: tambahkan stock
   - Jika tidak: attach product baru
6. Kurangi stock di warehouse
7. Return merchant dengan data terbaru
```

### Flow 2: Return Product (Merchant → Warehouse)

```
1. Validasi merchant_id exists
2. Validasi product ada di merchant
3. Cek stock merchant >= requested stock
4. Validasi warehouse_id exists
5. Kurangi stock di merchant:
   - Jika stock = 0: detach product
   - Jika stock > 0: update stock
6. Tambah stock di warehouse:
   - Jika product ada: tambahkan stock
   - Jika tidak ada: attach product baru
7. Return merchant dengan data terbaru
```

### Flow 3: Transfer Product (Merchant A → Merchant B)

```
1. Validasi source_merchant_id exists
2. Validasi product ada di source merchant
3. Cek stock source >= requested stock
4. Validasi target_merchant_id exists & different
5. Kurangi stock di source merchant
6. Tambah stock di target merchant
7. Return both merchants dengan data terbaru
```

---

## 🎯 Use Cases

### Use Case 1: Restocking Toko dari Gudang

**Scenario:** Toko membutuhkan tambahan stock dari warehouse pusat

```bash
POST /api/merchant-products/assign-from-warehouse
{
    "warehouse_id": "warehouse-pusat",
    "merchant_id": "toko-cabang-1",
    "product_id": "laptop-gaming",
    "stock": 50
}
```

**Result:**

- Warehouse Pusat: stock -50
- Toko Cabang 1: stock +50

---

### Use Case 2: Return Produk Tidak Laku

**Scenario:** Toko mengembalikan produk yang tidak laku ke warehouse

```bash
POST /api/merchant-products/return-to-warehouse
{
    "warehouse_id": "warehouse-pusat",
    "merchant_id": "toko-cabang-1",
    "product_id": "laptop-gaming",
    "stock": 20
}
```

**Result:**

- Toko Cabang 1: stock -20
- Warehouse Pusat: stock +20

---

### Use Case 3: Transfer Stock Antar Toko

**Scenario:** Toko A punya stock berlebih, Toko B butuh stock

```bash
POST /api/merchant-products/transfer
{
    "source_merchant_id": "toko-cabang-1",
    "target_merchant_id": "toko-cabang-2",
    "product_id": "laptop-gaming",
    "stock": 30
}
```

**Result:**

- Toko Cabang 1: stock -30
- Toko Cabang 2: stock +30
- Warehouse tidak terpengaruh

---

## ⚠️ Important Notes

### 1. Stock Validation

- Semua operasi **WAJIB** validasi stock availability
- Tidak boleh transfer/assign lebih dari stock yang tersedia
- Error message jelas menunjukkan stock yang tersedia

### 2. Database Transactions

- Semua operasi dibungkus dalam **DB::transaction()**
- Jika ada error, semua perubahan di-rollback
- Data consistency terjaga

### 3. Automatic Stock Adjustment

- Jika stock merchant jadi 0 setelah return/transfer → product auto detach
- Jika product sudah ada saat assign → stock auto ditambahkan
- Tidak perlu manual detach/attach

### 4. Cascade Operations

- Delete merchant → auto detach semua products
- Delete product → auto hapus dari semua merchant_product
- Delete warehouse → auto hapus dari semua warehouse_product

---

## 🚀 Advanced Features (Future Enhancement)

### 1. Stock Movement Logs

```php
// Create table: stock_movements
- id
- merchant_id
- warehouse_id (nullable)
- product_id
- type (assign/return/transfer/adjustment)
- quantity
- from (warehouse_id or merchant_id)
- to (merchant_id)
- created_at
```

### 2. Stock Alerts

- Low stock notification
- Overstock warning
- Automatic reorder points

### 3. Batch Operations

- Bulk assign multiple products
- Bulk transfer dengan satu request

### 4. Stock Reconciliation

- Compare physical count vs system
- Adjustment history

---

## ✅ Complete Routes Summary

```php
// Manual Operations (Merchants)
GET    /api/merchants/{slug}/products
POST   /api/merchants/{slug}/products
PUT    /api/merchants/{slug}/products/{productId}/stock
DELETE /api/merchants/{slug}/products/{productId}

// Warehouse Integration (Merchant Products)
POST   /api/merchant-products/assign-from-warehouse
POST   /api/merchant-products/return-to-warehouse
POST   /api/merchant-products/transfer
GET    /api/merchant-products/{merchantId}/products/{productId}/movements
```

---

## 🎯 Key Takeaways

1. **Assign** = Warehouse → Merchant (warehouse stock ↓, merchant stock ↑)
2. **Return** = Merchant → Warehouse (merchant stock ↓, warehouse stock ↑)
3. **Transfer** = Merchant A → Merchant B (warehouse not affected)
4. All operations wrapped in **transactions** for data consistency
5. Comprehensive **validation** & error handling
6. Clean **separation of concerns** (Service/Repository/Controller)
