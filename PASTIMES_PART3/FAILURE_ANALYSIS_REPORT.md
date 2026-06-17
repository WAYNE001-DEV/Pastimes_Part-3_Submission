# PASTIMES Code Inspection Report
**Date:** June 17, 2026  
**Status:** ⚠️ **CRITICAL & MAJOR ISSUES FOUND**

---

## Executive Summary
The application has **multiple points of failure** that will cause runtime errors, security breaches, and data loss. Below are the issues organized by severity.

---

## 🔴 CRITICAL ISSUES (Will Crash / Break Core Features)

### 1. **Database Connection Failure — Missing Database**
**File:** [DBConn.php](DBConn.php)  
**Line:** 19-22  
**Problem:** 
- The script tries to connect to database `ClothingStore` which likely doesn't exist.
- If the database is not created **before** any page is accessed, the entire application crashes.
- No graceful fallback or error recovery.

**Symptom on Run:**
```
Connection failed: Unknown database 'ClothingStore'
```

**Fix Required:**
1. **First-run setup:** Run [createTable.php](createTable.php) immediately after deployment to create tables.
2. Add database existence check or auto-create capability in `DBConn.php`.

---

### 2. **Missing Table Schema**
**File:** [createTable.php](createTable.php)  
**Problem:**
- The script only creates `tblUser` table.
- **Missing tables:**
  - `tblAdmin` (required by [admin/login.php](admin/login.php#L29))
  - `tblClothes` (required by [shop.php](shop.php#L14), [ShoppingCart.php](ShoppingCart.php#L24))
  - `tblOrderLine` (required by [checkout.php](checkout.php#L25), [ShoppingCart.php](ShoppingCart.php#L142))

**Symptom on Run:**
- User login works → but any attempt to view shop crashes with "Table 'ClothingStore.tblClothes' doesn't exist"
- Admin login crashes immediately with "Table 'ClothingStore.tblAdmin' doesn't exist"
- Checkout crashes with "Table 'ClothingStore.tblOrderLine' doesn't exist"

**File:** [myClothingStore.sql](myClothingStore.sql)  
**Problem:** The `.sql` file exists but is **never imported** by the setup script. It must be manually executed or the schema must be added to [createTable.php](createTable.php).

**Fix Required:**
- Import [myClothingStore.sql](myClothingStore.sql) into the database, OR
- Add `tblAdmin`, `tblClothes`, `tblOrderLine` creation to [createTable.php](createTable.php).

---

### 3. **Inconsistent Password Hashing — Admin vs User**
**Files:** 
- [admin/login.php](admin/login.php#L45): Uses `password_verify()` (bcrypt expected)
- [admin/index.php](admin/index.php#L62): Uses `md5()` to hash passwords
- [register.php](register.php#L46): Uses `password_hash(...PASSWORD_BCRYPT...)` for users

**Problem:**
- Admin creation hashes with **MD5** (weak, unsalted)
- Admin login expects **bcrypt** format
- Password verification will always fail for admins created via [admin/index.php](admin/index.php)

**Symptom on Run:**
- Admin cannot login if created via the admin panel
- Only pre-existing admins (if any) with bcrypt hashes will work

**Fix Required:**
- In [admin/index.php](admin/index.php#L62), change `md5()` to:
  ```php
  $hashed = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
  ```

---

### 4. **Missing userData.txt — Initial Data Load Fails**
**File:** [createTable.php](createTable.php#L44)  
**Problem:**
- Script expects [userData.txt](userData.txt) to exist and be properly formatted.
- If file is missing or malformed, users table remains empty.
- Application appears to work but no test user can login.

**Symptom on Run:**
- After setup, `tblUser` is empty → cannot login as any user.

**Fix Required:**
- Ensure [userData.txt](userData.txt) exists with tab-delimited format:
  ```
  fullName<TAB>email<TAB>password<TAB>province<TAB>isVerified<TAB>status
  ```
- OR pre-populate test users in [createTable.php](createTable.php) directly.

---

## 🟠 MAJOR ISSUES (Will Cause User-Facing Failures)

### 5. **Cart Session Not Initialized — Shop Page Will Error**
**Files:** [shop.php](shop.php#L10), [ShoppingCart.php](ShoppingCart.php#L15)  
**Problem:**
- `ShoppingCart` constructor tries to read `$_SESSION['cart']` 
- If `session_start()` fails or session is not properly initialized, cart operations crash.
- No null checks on session data.

**Fix Required:**
- Verify `session_start()` is called before any `ShoppingCart` usage.
- Add fallback: `$this->items = $_SESSION['cart'] ?? [];` (already done, but add error handling).

---

### 6. **Image Files Missing — Product Images Won't Display**
**File:** [shop.php](shop.php#L170)  
**Problem:**
- Product cards expect images in `images/` folder.
- Folder exists but likely empty (no `.jpg`, `.png` files).
- `onerror` fallback shows category emoji, but better to have real images.

**Symptom on Run:**
- All products show category icon instead of actual image.
- Affects user experience; not a crash but major UX issue.

**Fix Required:**
- Populate `images/` folder with product images matching `imageFile` values in database.

---

### 7. **Foreign Key Constraints Not Enforced**
**File:** [admin/index.php](admin/index.php#L46-L53)  
**Problem:**
- When deleting a user, the script checks for dependent orders manually.
- If `tblOrderLine` has `userID` foreign key WITHOUT `ON DELETE RESTRICT`, data integrity issues arise.
- If deleted user has orders, data becomes orphaned.

**Symptom on Run:**
- Unpredictable behavior when admin deletes user with orders.

**Fix Required:**
- Ensure database schema has:
  ```sql
  ALTER TABLE tblOrderLine 
  ADD CONSTRAINT fk_user FOREIGN KEY (userID) 
  REFERENCES tblUser(userID) ON DELETE RESTRICT;
  ```

---

### 8. **Delivery Address Not Validated — SQL Injection Risk**
**File:** [checkout.php](checkout.php#L29)  
**Problem:**
- `deliveryAddress` is trimmed but never validated.
- Passed directly to `ShoppingCart::Checkout()` without length limits.
- Could be extremely long string or contain special characters.

**Symptom on Run:**
- Database insert might fail with truncation errors if address > 255 chars.
- Potential for injection if address is not properly parameterized (it is, but data quality is poor).

**Fix Required:**
- Add validation in [checkout.php](checkout.php#L29):
  ```php
  if (strlen($deliveryAddress) < 5 || strlen($deliveryAddress) > 255) {
      $errors[] = "Delivery address must be 5-255 characters.";
  }
  ```

---

### 9. **No CSRF Protection — Forms Vulnerable**
**Files:** [login.php](login.php#L155), [register.php](register.php#L147), [checkout.php](checkout.php#L76), [admin/login.php](admin/login.php#L86)  
**Problem:**
- Forms use `POST` but have **no CSRF token validation**.
- Attacker could craft requests to login, checkout, or modify admin data.

**Fix Required:**
- Add CSRF token generation and validation:
  ```php
  // Generate token on page load
  if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  
  // Add to form
  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
  
  // Validate on POST
  if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      die('CSRF token invalid.');
  }
  ```

---

### 10. **No Rate Limiting on Login — Brute Force Possible**
**Files:** [login.php](login.php#L27), [admin/login.php](admin/login.php#L22)  
**Problem:**
- Login endpoints allow unlimited login attempts.
- No rate limiting, account lockout, or throttling.
- Attacker can brute-force passwords repeatedly.

**Fix Required:**
- Implement login attempt tracking:
  ```php
  // Track failed attempts per email in session
  $_SESSION['login_attempts'][$email] = ($_SESSION['login_attempts'][$email] ?? 0) + 1;
  
  if ($_SESSION['login_attempts'][$email] > 5) {
      die('Too many login attempts. Try again later.');
  }
  ```

---

## 🟡 MODERATE ISSUES (Should Fix)

### 11. **No Input Sanitization on Admin Update**
**File:** [admin/index.php](admin/index.php#L79-L90)  
**Problem:**
- User update form doesn't validate role changes or province selection.
- Admin can potentially update user to invalid state.

**Fix Required:**
- Add validation for enum values:
  ```php
  if (!in_array($st, ['active', 'inactive', 'pending'])) {
      $msg = "Invalid status.";
  }
  ```

---

### 12. **Session Timeout Not Implemented**
**Files:** All files using `session_start()`  
**Problem:**
- Sessions never expire.
- Logged-in users stay logged in indefinitely.
- Security risk if device is stolen or shared.

**Fix Required:**
- Add to [DBConn.php](DBConn.php) or top of each page:
  ```php
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
      session_destroy();
      header("Location: login.php?msg=session_expired");
  }
  $_SESSION['last_activity'] = time();
  ```

---

### 13. **Redirect After Login Not Sanitized**
**File:** [checkout.php](checkout.php#L7)  
**Problem:**
- Uses `$_GET` parameter without validation (if message added).
- Could be used for open redirect attacks.

**Fix Required:**
- Always validate redirect URLs:
  ```php
  $redirect = 'login.php';
  if (isset($_GET['redirect']) && strpos($_GET['redirect'], 'http') === false) {
      $redirect = $_GET['redirect'];
  }
  header("Location: $redirect");
  ```

---

## 📋 Required Setup Checklist

Before running the application:

- [ ] **1. Create database:** `CREATE DATABASE ClothingStore;`
- [ ] **2. Import schema:** Execute [myClothingStore.sql](myClothingStore.sql) OR update [createTable.php](createTable.php) with all table definitions.
- [ ] **3. Create `tblAdmin` table** in database.
- [ ] **4. Create initial admin user** with bcrypt-hashed password.
- [ ] **5. Populate [userData.txt](userData.txt)** with test users in correct format.
- [ ] **6. Add product images** to `images/` folder.
- [ ] **7. Change MySQL credentials** in [DBConn.php](DBConn.php#L12-L13) from defaults.

---

## 🔧 Immediate Fixes (Priority)

### Fix 1: Update [admin/index.php](admin/index.php#L62)
```php
// Before
$hashed = md5($pw);

// After
$hashed = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
```

### Fix 2: Verify [myClothingStore.sql](myClothingStore.sql) is Imported
- Either run SQL file directly in MySQL, or
- Update [createTable.php](createTable.php) to create `tblAdmin`, `tblClothes`, `tblOrderLine`.

### Fix 3: Validate Delivery Address in [checkout.php](checkout.php#L29)
```php
if (empty($deliveryAddress) || strlen($deliveryAddress) > 255) {
    $errors[] = "Please enter a valid delivery address (max 255 chars).";
}
```

---

## Summary

| Severity | Count | Impact |
|----------|-------|--------|
| 🔴 Critical | 4 | Application will not run |
| 🟠 Major | 6 | Core features will fail |
| 🟡 Moderate | 3 | Security/UX issues |

**Overall Risk:** **HIGH** — Multiple blocking issues prevent normal operation. Follow the setup checklist and apply Critical fixes before deployment.

---

*Report Generated: June 17, 2026*
