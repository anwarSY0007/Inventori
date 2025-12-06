<div align="center">

  <img src="https://cdn-icons-png.flaticon.com/512/2897/2897785.png" alt="logo" width="100" height="auto" />
  
  # Aplikasi Inventory & POS
  
  <p>
    Sistem Manajemen Stok, Gudang, dan Kasir Modern berbasis Web.
  </p>

  <p>
    <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel" />
    <img src="https://img.shields.io/badge/React-18.x-61DAFB?style=for-the-badge&logo=react&logoColor=black" alt="React" />
    <img src="https://img.shields.io/badge/Inertia.js-purple?style=for-the-badge&logo=inertia" alt="Inertia" />
    <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css" alt="Tailwind" />
  </p>

  <p>
    <a href="https://github.com/anwarsy0007">
      <img src="https://img.shields.io/badge/Created_by-ANWR-000000?style=for-the-badge&logo=github&logoColor=white" alt="Created by ANWR" />
    </a>
  </p>
</div>

<br />

## 🗺️ Peta Akses & Routing (Route Map)

Berikut adalah struktur navigasi aplikasi berdasarkan hak akses pengguna:

| Fitur / Menu           | Route URL                      | Hak Akses (Role)                                                                                                                          |
| :--------------------- | :----------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------- |
| **🏠 Dashboard**       | `/dashboard`                   | ![](https://img.shields.io/badge/All_Roles-gray)                                                                                          |
| **👥 All Users**       | `/admin/users`                 | ![](https://img.shields.io/badge/Super_Admin-red)                                                                                         |
| **🛍️ All Customers**   | `/admin/customers/all`         | ![](https://img.shields.io/badge/Super_Admin-red)                                                                                         |
| **🏪 Merchants**       | `/admin/merchants`             | ![](https://img.shields.io/badge/Super_Admin-red)                                                                                         |
| **👔 Team Members**    | `/team/members`                | ![](https://img.shields.io/badge/Owner-blue) ![](https://img.shields.io/badge/Admin-green)                                                |
| **🛒 Customers**       | `/team/customers`              | ![](https://img.shields.io/badge/Owner-blue) ![](https://img.shields.io/badge/Admin-green) ![](https://img.shields.io/badge/Staff-orange) |
| **📦 Categories**      | `/admin/categories`            | ![](https://img.shields.io/badge/Admin_Roles-green)                                                                                       |
| **🧴 Products**        | `/admin/products`              | ![](https://img.shields.io/badge/Admin-green) ![](https://img.shields.io/badge/Warehouse-orange)                                          |
| **🏭 Warehouses**      | `/admin/warehouses`            | ![](https://img.shields.io/badge/Admin-green) ![](https://img.shields.io/badge/Warehouse-orange)                                          |
| **📊 Stock Mutations** | `/admin/stock`                 | ![](https://img.shields.io/badge/Admin-green) ![](https://img.shields.io/badge/Warehouse-orange)                                          |
| **🧾 Transactions**    | `/admin/transactions`          | ![](https://img.shields.io/badge/Owner-blue) ![](https://img.shields.io/badge/Admin-green)                                                |
| **💻 POS (Cashier)**   | `/cashier/transactions/create` | ![](https://img.shields.io/badge/Owner-blue) ![](https://img.shields.io/badge/Cashier-yellow)                                             |

<br />

## 🚀 Fitur Utama

- **Multi-Tenancy:** Mendukung banyak Merchant/Toko dalam satu sistem.
- **Role Management:** Kontrol akses granular menggunakan Spatie Permission.
- **Stock Mutation:** Pelacakan stok masuk/keluar yang akurat.
- **POS System:** Transaksi kasir real-time dengan React.

<br />

---

<div align="center">
  <p>
    <b>&copy; 2025 Inventory App</b>. Dikembangkan dengan ❤️ oleh <b>ANWR</b>.
  </p>
</div>
