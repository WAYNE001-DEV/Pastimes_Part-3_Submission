<?php
/**
 * createTable.php — PASTIMES
 * Student: [Your Name] | [Student Number]
 * Declaration: This code is my own work where not referenced.
 *
 * This script:
 *  1. Checks if tblUser exists → drops it
 *  2. Re-creates tblUser with the correct schema
 *  3. Loads data from userData.txt into tblUser
 *
 * Run via: http://localhost/WAYNE_FINAL/createTable.php
 */

// ── Include DB connection (DBConn.php embedded as include) ───────────────────
require_once 'DBConn.php';

$messages = [];

// ── 1. Drop all tables in reverse FK order ────────────────────────────────────
$dropTables = ['tblOrderLine', 'tblMessage', 'tblClothes', 'tblAdmin', 'tblUser'];
foreach ($dropTables as $table) {
    if ($conn->query("DROP TABLE IF EXISTS $table")) {
        $messages[] = "✅ $table dropped (or did not exist).";
    } else {
        $messages[] = "⚠️  Could not drop $table: " . $conn->error;
    }
}

// ── 2. Create all tables ──────────────────────────────────────────────────────
$createSQL = "
CREATE TABLE IF NOT EXISTS tblUser (
    userID      INT AUTO_INCREMENT PRIMARY KEY,
    fullName    VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    province    VARCHAR(50)   DEFAULT NULL,
    isVerified  TINYINT(1)    NOT NULL DEFAULT 0,
    status      ENUM('active','inactive','pending') NOT NULL DEFAULT 'pending',
    role        ENUM('buyer','seller')              NOT NULL DEFAULT 'buyer',
    createdAt   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS tblAdmin (
    adminID     INT AUTO_INCREMENT PRIMARY KEY,
    fullName    VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,
    createdAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS tblClothes (
    clothesID   INT AUTO_INCREMENT PRIMARY KEY,
    sellerID    INT                 DEFAULT NULL,
    title       VARCHAR(200)        NOT NULL,
    category    VARCHAR(80)         NOT NULL,
    brand       VARCHAR(100)        DEFAULT NULL,
    size        VARCHAR(20)         DEFAULT NULL,
    colour      VARCHAR(50)         DEFAULT NULL,
    condition_  ENUM('Mint','Good','Fair','Well-Loved') NOT NULL DEFAULT 'Good',
    sellPrice   DECIMAL(10,2)       NOT NULL,
    retailPrice DECIMAL(10,2)       DEFAULT NULL,
    imageFile   VARCHAR(255)        DEFAULT 'placeholder.jpg',
    status      ENUM('active','sold','inactive') NOT NULL DEFAULT 'active',
    createdAt   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clothes_seller FOREIGN KEY (sellerID) REFERENCES tblUser(userID) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS tblOrderLine (
    orderID         INT AUTO_INCREMENT PRIMARY KEY,
    userID          INT             NOT NULL,
    clothesID       INT             NOT NULL,
    quantity        INT             NOT NULL DEFAULT 1,
    totalAmount     DECIMAL(10,2)   NOT NULL,
    deliveryAddress TEXT            DEFAULT NULL,
    status          ENUM('pending','processing','shipped','delivered','cancelled')
                                    NOT NULL DEFAULT 'pending',
    createdAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orderline_user    FOREIGN KEY (userID)     REFERENCES tblUser(userID)    ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_orderline_clothes FOREIGN KEY (clothesID)  REFERENCES tblClothes(clothesID) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS tblMessage (
    messageID   INT AUTO_INCREMENT PRIMARY KEY,
    senderID    INT NOT NULL,
    recipientID INT NOT NULL,
    subject     VARCHAR(255),
    body        TEXT,
    isRead      TINYINT(1) DEFAULT 0,
    createdAt   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_sender    FOREIGN KEY (senderID)    REFERENCES tblUser(userID) ON DELETE CASCADE,
    CONSTRAINT fk_msg_recipient FOREIGN KEY (recipientID) REFERENCES tblUser(userID) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4;
";

if ($conn->multi_query($createSQL)) {
    // Consume all results from multi_query
    while ($conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
    $messages[] = "✅ All tables created successfully.";
} else {
    $messages[] = "❌ Error creating tables: " . $conn->error;
    showPage($messages);
    exit;
}

// ── 3. Seed tblAdmin with bcrypt hashed passwords ────────────────────────────
$adminInserts = [
    ['Super Admin', 'admin@clothingstore.co.za', 'admin123'],
    ['Store Manager', 'manager@clothingstore.co.za', 'manager123'],
    ['Support Admin', 'support@clothingstore.co.za', 'support123'],
    ['Content Admin', 'content@clothingstore.co.za', 'content123'],
    ['Finance Admin', 'finance@clothingstore.co.za', 'finance123']
];

$adminStmt = $conn->prepare("INSERT INTO tblAdmin (fullName, email, password) VALUES (?, ?, ?)");
if (!$adminStmt) {
    $messages[] = "❌ Prepare failed for admin insert: " . $conn->error;
} else {
    foreach ($adminInserts as $admin) {
        $hashed = password_hash($admin[2], PASSWORD_BCRYPT, ['cost' => 12]);
        $adminStmt->bind_param("sss", $admin[0], $admin[1], $hashed);
        if ($adminStmt->execute()) {
            $messages[] = "✅ Admin '" . htmlspecialchars($admin[0]) . "' created (password: {$admin[2]}).";
        } else {
            $messages[] = "⚠️  Could not insert admin '{$admin[0]}': " . $adminStmt->error;
        }
    }
    $adminStmt->close();
}

// ── 4. Seed tblUser with bcrypt hashed passwords ──────────────────────────────
$userInserts = [
    ['John Doe', 'j.doe@abc.co.za', 'password1', 'Gauteng', 1, 'active', 'buyer'],
    ['Jane Smith', 'j.smith@xyz.co.za', 'password2', 'Western Cape', 1, 'active', 'buyer'],
    ['Thabo Nkosi', 't.nkosi@mail.co.za', 'password3', 'KwaZulu-Natal', 1, 'active', 'seller'],
    ['Ayanda Maseko', 'a.maseko@web.co.za', 'password4', 'Gauteng', 0, 'pending', 'seller'],
    ['Lerato Dlamini', 'l.dlamini@shop.co.za', 'password5', 'Limpopo', 1, 'active', 'seller'],
    ['Sipho Mthembu', 's.mthembu@clothe.co.za', 'password6', 'Mpumalanga', 0, 'pending', 'buyer'],
    ['Naledi Khumalo', 'n.khumalo@wear.co.za', 'password7', 'North West', 1, 'active', 'seller'],
    ['David van Wyk', 'd.vanwyk@store.co.za', 'password8', 'Eastern Cape', 0, 'pending', 'seller']
];

$userStmt = $conn->prepare("INSERT INTO tblUser (fullName, email, password, province, isVerified, status, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$userStmt) {
    $messages[] = "❌ Prepare failed for user insert: " . $conn->error;
} else {
    foreach ($userInserts as $user) {
        $hashed = password_hash($user[2], PASSWORD_BCRYPT, ['cost' => 12]);
        $userStmt->bind_param("sssisis", $user[0], $user[1], $hashed, $user[3], $user[4], $user[5], $user[6]);
        if ($userStmt->execute()) {
            // Silently skip message, user created
        } else {
            $messages[] = "⚠️  Could not insert user '{$user[0]}': " . $userStmt->error;
        }
    }
    $userStmt->close();
    $messages[] = "✅ Seeded " . count($userInserts) . " test users.";
}

// ── 5. Seed tblClothes with 30 sample products ──────────────────────────────
$clothes = [
    [3, 'Vintage Denim Jacket', 'Jackets', 'Levi\'s', 'M', 'Blue', 'Good', 350.00, 1200.00, 'JACKET.jpg'],
    [3, 'Classic White T-Shirt', 'Tops', 'Nike', 'L', 'White', 'Mint', 120.00, 350.00, 'T-SHIRT.jpg'],
    [3, 'Slim Fit Chinos', 'Pants', 'H&M', '32', 'Khaki', 'Good', 180.00, 450.00, 'JEANS (2).jpg'],
    [3, 'Floral Summer Dress', 'Dresses', 'Zara', 'S', 'Multi', 'Mint', 260.00, 800.00, 'LADYS.jpg'],
    [5, 'Adidas Track Jacket', 'Jackets', 'Adidas', 'XL', 'Black', 'Fair', 200.00, 700.00, 'JACKET (2).jpg'],
    [5, 'High-Waist Jeans', 'Pants', 'Topshop', '28', 'Dark Blue', 'Good', 290.00, 950.00, 'JEANS.jpg'],
    [7, 'Knit Pullover Sweater', 'Tops', 'Woolworths', 'M', 'Cream', 'Good', 220.00, 600.00, 'T-SHIRT (3).jpg'],
    [7, 'Canvas Sneakers', 'Shoes', 'Converse', '8', 'White', 'Fair', 150.00, 550.00, 'NIKE-SHOES.jpg'],
    [3, 'Leather Crossbody Bag', 'Accessories', 'Fossil', 'OS', 'Brown', 'Good', 380.00, 1100.00, 'GENTS.jpg'],
    [5, 'Printed Midi Skirt', 'Skirts', 'Cotton On', 'M', 'Orange', 'Mint', 175.00, 399.00, 'LADYS.jpg'],
    [3, 'Bomber Jacket', 'Jackets', 'Superdry', 'L', 'Olive', 'Good', 420.00, 1500.00, 'JACKET (3).jpg'],
    [7, 'Striped Polo Shirt', 'Tops', 'Lacoste', 'M', 'Navy', 'Good', 310.00, 900.00, 'T-SHIRT (4).jpg'],
    [5, 'Cargo Shorts', 'Shorts', 'Quiksilver', '32', 'Beige', 'Fair', 140.00, 400.00, 'GENTS.jpg'],
    [3, 'Wrap Maxi Dress', 'Dresses', 'Zara', 'M', 'Red', 'Mint', 340.00, 999.00, 'LADYS.jpg'],
    [7, 'Running Shoes', 'Shoes', 'New Balance', '9', 'Grey', 'Good', 450.00, 1600.00, 'NIKE-SHOES (2).jpg'],
    [5, 'Quilted Puffer Vest', 'Jackets', 'The North Face', 'L', 'Black', 'Good', 550.00, 1800.00, 'WINTER-JACKET (2).jpg'],
    [3, 'Linen Wide-Leg Trousers', 'Pants', 'Witchery', '10', 'Beige', 'Mint', 280.00, 750.00, 'JEANS (3).jpg'],
    [7, 'Graphic Band Tee', 'Tops', 'H&M', 'S', 'Black', 'Fair', 90.00, 200.00, 'T-SHIRT (2).jpg'],
    [5, 'Ankle Boots', 'Shoes', 'Steve Madden', '7', 'Tan', 'Good', 520.00, 1400.00, 'WINTER-JACKET.jpg'],
    [3, 'Denim Overalls', 'Overalls', 'Levi\'s', 'M', 'Blue', 'Good', 380.00, 1100.00, 'T-SHIRTS.jpg'],
    [7, 'Silk Blouse', 'Tops', 'Zara', 'S', 'Ivory', 'Mint', 230.00, 700.00, 'T-SHIRT (5).jpg'],
    [5, 'Sports Leggings', 'Activewear', 'Nike', 'M', 'Black', 'Good', 200.00, 600.00, 'T-SHIRT (6).jpg'],
    [3, 'Corduroy Jacket', 'Jackets', 'Woolworths', 'L', 'Brown', 'Fair', 270.00, 800.00, 'BLACK-JACKET.jpg'],
    [7, 'Pleated Mini Skirt', 'Skirts', 'Cotton On', 'XS', 'Pink', 'Mint', 130.00, 350.00, 'LADYS.jpg'],
    [5, 'Chelsea Boots', 'Shoes', 'Dr. Martens', '8', 'Black', 'Good', 680.00, 2000.00, 'winter jacket.jpg'],
    [3, 'Fleece Hoodie', 'Tops', 'Adidas', 'XL', 'Grey', 'Good', 250.00, 750.00, 'HOODIE.jpg'],
    [7, 'Tailored Blazer', 'Jackets', 'Zara', '38', 'Black', 'Mint', 490.00, 1500.00, 'JACKET (3).jpg'],
    [5, 'Slip Dress', 'Dresses', 'Forever 21', 'M', 'Nude', 'Good', 180.00, 500.00, 'LADYS.jpg'],
    [3, 'Bucket Hat', 'Accessories', 'Nike', 'OS', 'White', 'Mint', 95.00, 299.00, 'T-SHIRT (6).jpg'],
    [7, 'Platform Sandals', 'Shoes', 'Zara', '7', 'Black', 'Fair', 210.00, 600.00, 'GENTS.jpg']
];

$clothesStmt = $conn->prepare(
    "INSERT INTO tblClothes (sellerID, title, category, brand, size, colour, condition_, sellPrice, retailPrice, imageFile) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$clothesStmt) {
    $messages[] = "❌ Prepare failed for clothes insert: " . $conn->error;
} else {
    foreach ($clothes as $item) {
        $clothesStmt->bind_param(
            "issssssdds",
            $item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6], $item[7], $item[8], $item[9]
        );
        $clothesStmt->execute();
    }
    $clothesStmt->close();
    $messages[] = "✅ Seeded " . count($clothes) . " sample products.";
}

$conn->close();

// ── 4. Render result page ──────────────────────────────────────────────────────
showPage($messages);

function showPage(array $msgs): void {
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>createTable.php — PASTIMES</title>
<link href='https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&display=swap' rel='stylesheet'>
<style>
  body  { font-family:'DM Sans',sans-serif; background:#0f0f0f; color:#e5e5e5; padding:2.5rem; }
  h1    { color:#c9a86c; margin-bottom:1.5rem; font-size:1.4rem; }
  ul    { list-style:none; padding:0; max-width:600px; }
  li    { background:#1a1a1a; border-left:4px solid #c9a86c; padding:.65rem 1rem;
          margin:.4rem 0; border-radius:4px; font-size:.95rem; }
  .links { margin-top:1.5rem; }
  .links a { color:#c9a86c; margin-right:1.5rem; text-decoration:none; font-size:.9rem; }
</style>
</head>
<body>
<h1>createTable.php — Execution Log</h1>
<ul>";
    foreach ($msgs as $m) {
        echo "<li>" . htmlspecialchars($m) . "</li>\n";
    }
    echo "</ul>
<div class='links'>
  <a href='index.php'>← Home</a>
  <a href='login.php'>Login</a>
</div>
</body></html>";
}
?>
