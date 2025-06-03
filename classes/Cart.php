<?php
// classes/Cart.php

class Cart {
    private $pdo;
    private $sessionId;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->sessionId = session_id();
    }
    
    public function addItem($categoryId, $matchId, $quantity) {
        try {
            // Check if item already exists in cart
            $stmt = $this->pdo->prepare("
                SELECT id, quantity 
                FROM cart_items 
                WHERE session_id = ? AND category_id = ? AND match_id = ?
            ");
            $stmt->execute([$this->sessionId, $categoryId, $matchId]);
            $existingItem = $stmt->fetch();
            
            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                $stmt = $this->pdo->prepare("
                    UPDATE cart_items 
                    SET quantity = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                return $stmt->execute([$newQuantity, $existingItem['id']]);
            } else {
                // Insert new item
                $stmt = $this->pdo->prepare("
                    INSERT INTO cart_items (session_id, category_id, match_id, quantity, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                return $stmt->execute([$this->sessionId, $categoryId, $matchId, $quantity]);
            }
        } catch (PDOException $e) {
            error_log("Cart addItem error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCategoryDetails($categoryId, $matchId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT tc.*, 
                       (tc.available_capacity - COALESCE(sold.total_sold, 0)) as remaining_tickets
                FROM ticket_categories tc
                LEFT JOIN (
                    SELECT category_id, SUM(quantity) as total_sold
                    FROM bookings b
                    JOIN booking_items bi ON b.id = bi.booking_id
                    WHERE b.status IN ('confirmed', 'paid')
                    GROUP BY category_id
                ) sold ON tc.id = sold.category_id
                WHERE tc.id = ? AND tc.match_id = ?
            ");
            $stmt->execute([$categoryId, $matchId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Cart getCategoryDetails error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getItemCount() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(quantity), 0) as total 
                FROM cart_items 
                WHERE session_id = ?
            ");
            $stmt->execute([$this->sessionId]);
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Cart getItemCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getItems() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ci.*, tc.name, tc.price, tc.description,
                       m.home_team_name, m.away_team_name, m.match_date,
                       (ci.quantity * tc.price) as total_price
                FROM cart_items ci
                JOIN ticket_categories tc ON ci.category_id = tc.id
                JOIN matches m ON ci.match_id = m.id
                WHERE ci.session_id = ?
                ORDER BY ci.created_at ASC
            ");
            $stmt->execute([$this->sessionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Cart getItems error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateItem($itemId, $quantity) {
        try {
            if ($quantity <= 0) {
                return $this->removeItem($itemId);
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE cart_items 
                SET quantity = ?, updated_at = NOW() 
                WHERE id = ? AND session_id = ?
            ");
            return $stmt->execute([$quantity, $itemId, $this->sessionId]);
        } catch (PDOException $e) {
            error_log("Cart updateItem error: " . $e->getMessage());
            return false;
        }
    }
    
    public function removeItem($itemId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM cart_items 
                WHERE id = ? AND session_id = ?
            ");
            return $stmt->execute([$itemId, $this->sessionId]);
        } catch (PDOException $e) {
            error_log("Cart removeItem error: " . $e->getMessage());
            return false;
        }
    }
    
    public function clearCart() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM cart_items 
                WHERE session_id = ?
            ");
            return $stmt->execute([$this->sessionId]);
        } catch (PDOException $e) {
            error_log("Cart clearCart error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTotal() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(ci.quantity * tc.price), 0) as total
                FROM cart_items ci
                JOIN ticket_categories tc ON ci.category_id = tc.id
                WHERE ci.session_id = ?
            ");
            $stmt->execute([$this->sessionId]);
            $result = $stmt->fetch();
            return (float)$result['total'];
        } catch (PDOException $e) {
            error_log("Cart getTotal error: " . $e->getMessage());
            return 0.0;
        }
    }
}
?>