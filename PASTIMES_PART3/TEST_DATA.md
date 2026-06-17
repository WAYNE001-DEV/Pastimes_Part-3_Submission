# 🧪 Test Credentials & Data

## Admin Accounts (5 Total)

All passwords are plain text below — they will be bcrypt-hashed when stored.

| Name | Email | Password | Role | Auto-Created |
|------|-------|----------|------|--------------|
| Super Admin | admin@clothingstore.co.za | admin123 | Full Access | ✅ Yes |
| Store Manager | manager@clothingstore.co.za | manager123 | Inventory + Users | ✅ Yes |
| Support Admin | support@clothingstore.co.za | support123 | Support | ✅ Yes |
| Content Admin | content@clothingstore.co.za | content123 | Content Mgmt | ✅ Yes |
| Finance Admin | finance@clothingstore.co.za | finance123 | Finance | ✅ Yes |

---

## User Accounts (8 Total)

All passwords are plain text below — they will be bcrypt-hashed when stored.

### Active Users ✅

| Name | Email | Password | Role | Province | Status |
|------|-------|----------|------|----------|--------|
| John Doe | j.doe@abc.co.za | password1 | Buyer | Gauteng | Active |
| Jane Smith | j.smith@xyz.co.za | password2 | Buyer | Western Cape | Active |
| Thabo Nkosi | t.nkosi@mail.co.za | password3 | Seller | KwaZulu-Natal | Active |
| Lerato Dlamini | l.dlamini@shop.co.za | password5 | Seller | Limpopo | Active |
| Naledi Khumalo | n.khumalo@wear.co.za | password7 | Seller | North West | Active |

### Pending Users ⏳ (Awaiting Admin Verification)

| Name | Email | Password | Role | Province | Status |
|------|-------|----------|------|----------|--------|
| Ayanda Maseko | a.maseko@web.co.za | password4 | Seller | Gauteng | Pending |
| Sipho Mthembu | s.mthembu@clothe.co.za | password6 | Buyer | Mpumalanga | Pending |
| David van Wyk | d.vanwyk@store.co.za | password8 | Seller | Eastern Cape | Pending |

---

## Sample Products (30 Total)

All products are pre-loaded with the following structure:
- **Seller ID:** Created by active sellers (userID 3, 5, or 7)
- **Price Range:** R90 – R680
- **Conditions:** Mint, Good, Fair, Well-Loved
- **Categories:** Jackets, Tops, Pants, Dresses, Shoes, Accessories, etc.

### Sample Product List

| # | Title | Category | Brand | Price | Condition | Seller |
|---|-------|----------|-------|-------|-----------|--------|
| 1 | Vintage Denim Jacket | Jackets | Levi's | R350 | Good | Thabo Nkosi |
| 2 | Classic White T-Shirt | Tops | Nike | R120 | Mint | Thabo Nkosi |
| 3 | Slim Fit Chinos | Pants | H&M | R180 | Good | Thabo Nkosi |
| 4 | Floral Summer Dress | Dresses | Zara | R260 | Mint | Thabo Nkosi |
| 5 | Adidas Track Jacket | Jackets | Adidas | R200 | Fair | Lerato Dlamini |
| 6 | High-Waist Jeans | Pants | Topshop | R290 | Good | Lerato Dlamini |
| 7 | Knit Pullover Sweater | Tops | Woolworths | R220 | Good | Naledi Khumalo |
| 8 | Canvas Sneakers | Shoes | Converse | R150 | Fair | Naledi Khumalo |
| 9 | Leather Crossbody Bag | Accessories | Fossil | R380 | Good | Thabo Nkosi |
| 10 | Printed Midi Skirt | Skirts | Cotton On | R175 | Mint | Lerato Dlamini |
| ... | ... | ... | ... | ... | ... | ... |
| 30 | Platform Sandals | Shoes | Zara | R210 | Fair | Naledi Khumalo |

**All 30 products are pre-loaded and ready to browse in the shop.**

---

## 🔐 Test Workflows

### 1. Buyer Registration & Shopping
```
1. Go to Register page
2. Select "Shop as Buyer"
3. Fill in details (use unique email)
4. Submit → Status: Pending
5. Admin approves registration
6. Login with new credentials
7. Browse 30 products
8. Add to cart → Checkout
```

### 2. Seller Registration & Listing
```
1. Go to Register page
2. Select "List as Seller"
3. Fill in details (use unique email)
4. Submit → Status: Pending
5. Admin approves seller account
6. Login to dashboard
7. Add new product
8. Product listed in shop
```

### 3. Admin Verification Flow
```
1. Login as admin@clothingstore.co.za / admin123
2. Go to Admin Panel
3. See "Pending Verification" users
4. Click "Verify" to approve → Status: Active
5. Click "Reject" to deny → Status: Inactive
6. Newly approved users can now login
```

### 4. Complete Purchase Flow
```
1. Login as buyer (j.doe@abc.co.za / password1)
2. Browse shop (30 products available)
3. Add 2-3 items to cart
4. Edit quantities in cart sidebar
5. Click "Checkout"
6. Enter delivery address
7. Click "Place Order"
8. See order confirmation with reference number
```

---

## 💡 Tips for Testing

### For Buyer Testing
- Use **John Doe** (j.doe@abc.co.za / password1) — already active
- Already has access to browse and checkout
- Can immediately test shopping flow

### For Seller Testing
- Use **Thabo Nkosi** (t.nkosi@mail.co.za / password3) — active seller
- 4 products already listed by this seller
- Can test product management

### For Admin Testing
- Use **admin@clothingstore.co.za / admin123**
- All functions available (verify users, add products, etc.)
- Can test verification flow with pending users

### For New User Flow
- Register a new account (will be pending)
- Switch to admin account
- Verify the new user
- Login with new account to confirm

---

## 📊 Database Statistics After Setup

After running `createTable.php`:

```
tblUser:      8 rows    (5 active, 3 pending)
tblAdmin:     5 rows    (all active)
tblClothes:   30 rows   (all active)
tblOrderLine: 0 rows    (created when checkout happens)
tblMessage:   0 rows    (empty, ready for messaging)
```

---

## ✨ What's Ready to Test

✅ **User Registration** — New users can register  
✅ **User Login** — 8 pre-loaded test accounts ready  
✅ **Admin Login** — 5 admin accounts with different roles  
✅ **Product Browsing** — 30 items in inventory  
✅ **Shopping Cart** — Add/remove/edit quantities  
✅ **Checkout** — Complete purchase flow  
✅ **Order History** — View past orders in dashboard  
✅ **Admin Functions** — Verify, reject, add users  
✅ **Product Management** — Add/edit/delete items  

---

## 🎯 First Test to Run

```
1. Navigate to createTable.php
2. See all tables created + data seeded
3. Login as admin@clothingstore.co.za / admin123
4. Verify the 3 pending users in admin panel
5. Approve one user
6. Logout, login as the approved user
7. Browse shop (30 products visible)
8. Add item to cart
9. Checkout → See order confirmation
```

---

*All test data auto-created when createTable.php is run*
*Passwords shown here are plain-text — stored as bcrypt in database*
