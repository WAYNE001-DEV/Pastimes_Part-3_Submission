<?php
/**
 * ShoppingCart.php
 * OOP ShoppingCart class — WEDE6021 Part 3 requirement.
 * Encapsulates all cart logic previously spread across shop.php and checkout.php.
 *
 * Usage:
 *   session_start();
 *   require_once 'DBConn.php';
 *   require_once 'ShoppingCart.php';
 *   $cart = new ShoppingCart($conn);
 *   $cart->ProcessInput($_POST, $_GET);
 */

class ShoppingCart {

    private array $items = [];
    private mysqli $conn;

    // ── Constructor ────────────────────────────────────────────────────────────
    public function __construct(mysqli $conn) {
        $this->conn  = $conn;
        $this->items = $_SESSION['cart'] ?? [];
    }

    // ── AddItem: fetch from DB and add/increment ───────────────────────────────
    public function AddItem(int $clothesID): void {
        $s = $this->conn->prepare(
            "SELECT clothesID, title, sellPrice FROM tblClothes
             WHERE clothesID = ? AND status = 'active'"
        );
        if (!$s) return;
        $s->bind_param("i", $clothesID);
        $s->execute();
        $item = $s->get_result()->fetch_assoc();
        $s->close();

        if (!$item) return;

        $id = $item['clothesID'];
        if (isset($this->items[$id])) {
            $this->items[$id]['qty']++;
        } else {
            $this->items[$id] = [
                'clothesID' => $id,
                'title'     => $item['title'],
                'price'     => (float) $item['sellPrice'],
                'qty'       => 1,
            ];
        }
        $this->saveSession();
    }

    // ── RemoveItem ─────────────────────────────────────────────────────────────
    public function RemoveItem(int $clothesID): void {
        unset($this->items[$clothesID]);
        $this->saveSession();
    }

    // ── UpdateQty: change quantity of an existing cart item ───────────────────
    public function UpdateQty(int $clothesID, int $qty): void {
        if ($qty < 1) {
            $this->RemoveItem($clothesID);
            return;
        }
        if (isset($this->items[$clothesID])) {
            $this->items[$clothesID]['qty'] = $qty;
            $this->saveSession();
        }
    }

    // ── EmptyCart ──────────────────────────────────────────────────────────────
    public function EmptyCart(): void {
        $this->items = [];
        $this->saveSession();
    }

    // ── ShowCart: return items array ──────────────────────────────────────────
    public function ShowCart(): array {
        return $this->items;
    }

    // ── GetTotal: sum of price * qty for all items ────────────────────────────
    public function GetTotal(): float {
        return array_sum(
            array_map(fn($i) => $i['price'] * $i['qty'], $this->items)
        );
    }

    // ── Login: verify user credentials (used before checkout redirect) ─────────
    public function Login(string $email, string $password): bool {
        $s = $this->conn->prepare(
            "SELECT userID, fullName, role, password FROM tblUser
             WHERE email = ? AND status = 'active' AND isVerified = 1"
        );
        if (!$s) return false;
        $s->bind_param("s", $email);
        $s->execute();
        $user = $s->get_result()->fetch_assoc();
        $s->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['userID']   = $user['userID'];
            $_SESSION['fullName'] = $user['fullName'];
            $_SESSION['role']     = $user['role'];
            return true;
        }
        return false;
    }

    // ── Checkout: write orders to DB, decrement stock, return refs ────────────
    public function Checkout(int $userID, string $address): array {
        if (empty($this->items)) {
            return ['success' => false, 'error' => 'Cart is empty.'];
        }

        // Insert into tblOrderLine (one row per cart item)
        $insertStmt = $this->conn->prepare(
            "INSERT INTO tblOrderLine (userID, clothesID, quantity, totalAmount, deliveryAddress, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        );
        $updateStmt = $this->conn->prepare(
            "UPDATE tblClothes SET status = 'sold' WHERE clothesID = ?"
        );

        if (!$insertStmt || !$updateStmt) {
            return ['success' => false, 'error' => 'Database error.'];
        }

        $this->conn->begin_transaction();
        try {
            $lastID = null;
            foreach ($this->items as $item) {
                $id     = (int)   $item['clothesID'];
                $qty    = (int)   $item['qty'];
                $total  = (float) ($item['price'] * $qty);

                $insertStmt->bind_param("iiids", $userID, $id, $qty, $total, $address);
                $insertStmt->execute();
                $lastID = $this->conn->insert_id;

                $updateStmt->bind_param("i", $id);
                $updateStmt->execute();
            }
            $this->conn->commit();
            $insertStmt->close();
            $updateStmt->close();

            $orderRef   = 'ORD-' . str_pad((string)$lastID, 6, '0', STR_PAD_LEFT);
            $sessionRef = strtoupper(substr(session_id(), 0, 8));

            $this->EmptyCart();

            return [
                'success'    => true,
                'orderRef'   => $orderRef,
                'sessionRef' => $sessionRef,
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => 'Order failed. Please try again.'];
        }
    }

    // ── ProcessInput: handle all POST/GET cart actions ────────────────────────
    public function ProcessInput(array $post, array $get): void {
        // Add to cart
        if (isset($post['addToCart'], $post['clothesID'])) {
            $this->AddItem((int) $post['clothesID']);
            header("Location: shop.php");
            exit;
        }

        // Remove from cart
        if (isset($get['remove'])) {
            $this->RemoveItem((int) $get['remove']);
            header("Location: shop.php");
            exit;
        }

        // Update quantity
        if (isset($post['updateID'], $post['newQty'])) {
            $this->UpdateQty((int) $post['updateID'], (int) $post['newQty']);
            header("Location: shop.php");
            exit;
        }
    }

    // ── Private: persist items to session ─────────────────────────────────────
    private function saveSession(): void {
        $_SESSION['cart'] = $this->items;
    }
}
