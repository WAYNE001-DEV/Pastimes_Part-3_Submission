# 🛍️ PASTIMES — Online Clothing Store (Part 3 — Final POE)

> A full-stack second-hand clothing marketplace built with PHP, MySQL, and WAMP.  
> Browse, buy, and sell pre-loved clothing with ease.

---

## 🎬 Video Demonstration

> ⚠️ **Video link to be added after recording final Part 3 demonstration.**

---

## 💻 GitHub Repository

[![GitHub](https://img.shields.io/badge/GitHub-Ayanda001%2FWAYNE__FINAL__FIXED-black?style=for-the-badge&logo=github)](https://github.com/Ayanda001/WAYNE_FINAL_FIXED)

**👉 https://github.com/Ayanda001/WAYNE_FINAL_FIXED**

---

## 👨‍💻 Developers

| Name | Student Number | Role |
|------|---------------|------|
| **Ayanda Maseko** | (your student number) | Software Developer |
| **Sabelo Dlomo** | (your student number) | Software Developer |

> WEDE6021 POE — Part 3 Final Submission

---

## 📋 Project Overview

**PASTIMES** is a second-hand clothing e-commerce platform that allows:

- 🛒 **Buyers** to browse listings, add items to cart, edit quantities, and place orders
- 🏷️ **Sellers** to list clothing items with images, prices, and condition ratings
- 🔐 **Admins** to manage users, manage clothing items, and communicate with buyers/sellers

**Tech Stack:** PHP (MySQLi OOP), MySQL, HTML, CSS, JavaScript, WAMP

---

## ✅ Part 3 Features Added

| Feature | File(s) |
|---------|---------|
| ShoppingCart PHP OOP class | `ShoppingCart.php` |
| Cart quantity editing | `shop.php` (qty input in sidebar) |
| Admin CRUD on clothing items | `admin/clothes.php` |
| Admin messaging to users | `admin/message.php` |
| User inbox (messages from admin) | `dashboard.php` |
| Purchase history grand total | `dashboard.php` |
| tblOrderLine table (rubric language) | `myClothingStore.sql`, `loadClothingStore.php` |
| tblMessage table | `myClothingStore.sql`, `loadClothingStore.php` |
| Updated admin sidebar (Clothes + Messages links) | `admin/index.php` |

---

## ⚙️ Setup Instructions (WAMP)

### Step 1 — Install WAMP
Download and install WAMP from [wampserver.com](https://www.wampserver.com).  
Make sure the **tray icon is green** before proceeding.

### Step 2 — Copy the Project
```
C:\wamp64\www\PASTIMES_PART3\
```

### Step 3 — Import the Database
1. Open `http://localhost/phpmyadmin`
2. Click **Import** → Choose `myClothingStore.sql` → Click **Go**

> ✅ This creates the `ClothingStore` database with all tables including the new Part 3 tables.

### Step 4 — Check Database Connection
Open `DBConn.php` and confirm:
```php
$host   = 'localhost';
$dbname = 'ClothingStore';
$user   = 'root';
$pass   = '';
```

### Step 5 — Open the Site
```
http://localhost/PASTIMES_PART3/
```

---

## 🔑 Login Credentials

### 🛒 Buyer Accounts (Active)
| Full Name | Email | Password |
|-----------|-------|----------|
| John Doe | j.doe@abc.co.za | password1 |
| Jane Smith | j.smith@xyz.co.za | password2 |

### 🏷️ Seller Accounts (Active)
| Full Name | Email | Password |
|-----------|-------|----------|
| Thabo Nkosi | t.nkosi@mail.co.za | password3 |
| Lerato Dlamini | l.dlamini@shop.co.za | password5 |
| Naledi Khumalo | n.khumalo@wear.co.za | password7 |

### 🔐 Admin Accounts
| Email | Password |
|-------|----------|
| admin@clothingstore.co.za | admin123 |
| manager@clothingstore.co.za | manager123 |

**Admin Panel URL:** `http://localhost/PASTIMES_PART3/admin/login.php`  
**Admin Invite Code:** `Ayanda_8`

---

## 🗂️ All PHP Files & Purpose

| File | Purpose |
|------|---------|
| `index.php` | Home/startup page — eShop type and goals stated |
| `register.php` | User registration with MD5 hashing, buyer/seller choice |
| `login.php` | Login with MD5 hash check, sticky form, session |
| `logout.php` | Destroys session and redirects |
| `shop.php` | Products grid, Add to Cart, View Price popup, cart sidebar with qty edit |
| `checkout.php` | Delivery form, calls ShoppingCart::Checkout(), shows order ref |
| `dashboard.php` | Account info, order history with grand total, messages inbox |
| `seller-products.php` | Seller uploads new clothing listings |
| `DBConn.php` | MySQLi database connection (getConnection function) |
| `ShoppingCart.php` | **NEW** OOP class: AddItem, RemoveItem, UpdateQty, EmptyCart, Checkout, ShowCart, GetTotal, Login, ProcessInput |
| `createTable.php` | Drops/recreates tblUser from userData.txt |
| `loadClothingStore.php` | Full DB schema reset including Part 3 tables |
| `myClothingStore.sql` | DDL export: 6 tables including tblOrderLine and tblMessage |
| `userData.txt` | 5 fictitious user entries for createTable.php |
| `admin/login.php` | Admin-only login with separate adminID session |
| `admin/logout.php` | Admin logout |
| `admin/register.php` | Admin registration |
| `admin/index.php` | Admin panel: verify, add, update, delete users |
| `admin/clothes.php` | **NEW** Admin CRUD for tblClothes: Add, Edit, Delete clothing items |
| `admin/message.php` | **NEW** Admin sends messages to buyers/sellers |

---

## 🗃️ Database Structure

```
ClothingStore
├── tblUser       — Buyers and Sellers
├── tblAdmin      — Admin accounts
├── tblClothes    — Product listings
├── tblOrderLine  — Orders placed at checkout (Part 3 — rubric compliant name)
└── tblMessage    — Admin-to-user messages (Part 3)
```

---

## 🔧 Special Setup Scripts

### Full Rebuild (drops all, re-seeds)
```
http://localhost/PASTIMES_PART3/loadClothingStore.php?token=setup_DR2025
```

### Rebuild Users Only
```
http://localhost/PASTIMES_PART3/createTable.php
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank white page | WAMP tray icon must be green |
| "Connection failed" | Check DBConn.php — password empty for default WAMP |
| "Table tblOrderLine doesn't exist" | Re-import myClothingStore.sql (includes Part 3 tables) |
| Images not showing | Ensure images/ folder is in place |
| Pending users can't log in | Admin must click Verify in admin panel |

---

## 📄 License

Built for educational purposes — WEDE6021 POE Part 3.  
© 2026 Ayanda Maseko & Sabelo Dlomo
