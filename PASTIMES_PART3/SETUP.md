# PASTIMES Setup Guide

## ✅ Critical Fixes Applied

All 4 critical issues have been fixed:

1. ✅ **Database & Tables** — `createTable.php` now creates all tables (tblUser, tblAdmin, tblClothes, tblOrderLine, tblMessage)
2. ✅ **Admin Password Hashing** — Now uses bcrypt instead of MD5
3. ✅ **Test Data** — 8 users, 5 admins, and 30 products are auto-seeded
4. ✅ **Schema Completeness** — All foreign keys and constraints are in place

---

## 🚀 Quick Start (3 Steps)

### Step 1: Create Database
Open phpMyAdmin or MySQL console and run:
```sql
CREATE DATABASE ClothingStore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Initialize Tables & Data
Navigate to: **http://localhost/PASTIMES_Part3_Submission/PASTIMES_PART3/createTable.php**

You should see:
```
✅ tblOrderLine dropped
✅ tblMessage dropped
✅ tblClothes dropped
✅ tblAdmin dropped
✅ tblUser dropped
✅ All tables created successfully
✅ Admin 'Super Admin' created (password: admin123)
...
✅ Seeded 8 test users
✅ Seeded 30 sample products
```

### Step 3: Login & Test
- **User Login**: http://localhost/PASTIMES_Part3_Submission/PASTIMES_PART3/login.php
- **Admin Panel**: http://localhost/PASTIMES_Part3_Submission/PASTIMES_PART3/admin/login.php

---

## 📝 Test Credentials

### Admin Accounts
| Email | Password | Notes |
|-------|----------|-------|
| admin@clothingstore.co.za | admin123 | Super Admin — Full access |
| manager@clothingstore.co.za | manager123 | Store Manager |

### User Accounts (Buyer & Seller)
| Email | Password | Role | Status |
|-------|----------|------|--------|
| j.doe@abc.co.za | password1 | Buyer | Active ✅ |
| j.smith@xyz.co.za | password2 | Buyer | Active ✅ |
| t.nkosi@mail.co.za | password3 | Seller | Active ✅ |
| a.maseko@web.co.za | password4 | Seller | Pending ⏳ |
| l.dlamini@shop.co.za | password5 | Seller | Active ✅ |
| n.khumalo@wear.co.za | password7 | Seller | Active ✅ |

---

## 🧪 Testing Checklist

### User Flows
- [ ] **Register** → New user registration with role selection
- [ ] **Login** → User can login with valid credentials (password1, etc.)
- [ ] **Shop** → Browse 30 pre-loaded products with images
- [ ] **Add to Cart** → Add items to cart and edit quantities
- [ ] **Checkout** → Checkout as authenticated user (creates order)
- [ ] **Dashboard** → View user account details and past orders

### Admin Flows
- [ ] **Admin Login** → Login with admin@clothingstore.co.za / admin123
- [ ] **Verify Users** → Approve pending user registrations
- [ ] **Manage Products** → Add/edit/delete products from inventory
- [ ] **View Orders** → See all customer orders and delivery addresses

---

## 📊 Database Structure

### Tables Created
- **tblUser** — Customers (buyers & sellers)
- **tblAdmin** — Admin accounts
- **tblClothes** — Product inventory (30 samples pre-loaded)
- **tblOrderLine** — Customer orders (via checkout)
- **tblMessage** — Messaging system (optional)

### Sample Data
- **8 Test Users** — Mix of active/pending buyers and sellers
- **5 Admin Accounts** — Pre-configured with different roles
- **30 Products** — Pre-loaded clothing items with images, prices, conditions

---

## 🔒 Security Notes

✅ **Passwords are now bcrypt-hashed** (cost=12)
✅ **Prepared statements** prevent SQL injection
✅ **Email validation** on registration
✅ **Account verification** required before login
✅ **Foreign key constraints** protect data integrity

---

## ⚠️ Remaining Issues (Not Critical)

See `FAILURE_ANALYSIS_REPORT.md` for:
- Session timeout implementation
- CSRF protection on forms
- Login rate limiting
- Image file organization

These are **NOT blocking** — the application will run correctly now.

---

## 📞 Troubleshooting

**Q: "Connection failed: Unknown database 'ClothingStore'"**  
A: Run Step 1 above — create the database first.

**Q: "Table 'ClothingStore.tblClothes' doesn't exist"**  
A: Run Step 2 — execute createTable.php.

**Q: Admin login fails**  
A: Use the credentials in the table above (admin123, manager123, etc.).

**Q: No test users showing**  
A: Re-run createTable.php to re-seed the data.

---

*Setup Complete! The application is now ready to run.* ✨
