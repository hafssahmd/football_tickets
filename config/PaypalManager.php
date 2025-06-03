<?php
require_once '../config/paypal_advanced.php';
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

class PayPalManager {
    private $client;
    private $db;
    
    public function __construct() {
        $this->client = PayPalConfiguration::client();
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Créer une commande PayPal
     */
    public function createOrder($cartItems, $userId) {
        try {
            // Calculer le total et valider le stock
            $orderData = $this->prepareOrderData($cartItems, $userId);
            if (!$orderData) {
                throw new Exception("Impossible de préparer la commande");
            }
            
            // Créer la commande PayPal
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = $this->buildPayPalOrderBody($orderData);
            
            $response = $this->client->execute($request);
            
            // Sauvegarder la commande en base
            $orderId = $this->saveOrderToDatabase($orderData, $response->result->id);
            
            return [
                'success' => true,
                'paypal_order_id' => $response->result->id,
                'local_order_id' => $orderId,
                'approval_url' => $this->getApprovalUrl($response->result->links)
            ];
            
        } catch (Exception $e) {
            error_log("PayPal Order Creation Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Capturer le paiement après approbation
     */
    public function captureOrder($paypalOrderId, $localOrderId) {
        try {
            // Vérifier que la commande existe et est valide
            $orderData = $this->getLocalOrder($localOrderId);
            if (!$orderData || $orderData['status'] !== 'pending') {
                throw new Exception("Commande invalide ou déjà traitée");
            }
            
            // Vérifier le stock avant capture
            if (!$this->verifyStockAvailability($localOrderId)) {
                throw new Exception("Stock insuffisant pour cette commande");
            }
            
            // Capturer le paiement
            $request = new OrdersCaptureRequest($paypalOrderId);
            $response = $this->client->execute($request);
            
            if ($response->result->status === 'COMPLETED') {
                // Mettre à jour la commande
                $this->updateOrderStatus($localOrderId, 'paid', $response->result);
                
                // Décrémenter le stock
                $this->updateTicketStock($localOrderId);
                
                // Vider le panier
                $this->clearUserCart($orderData['user_id']);
                
                // Envoyer l'email de confirmation
                $this->sendConfirmationEmail($localOrderId);
                
                return [
                    'success' => true,
                    'order_id' => $localOrderId,
                    'transaction_id' => $response->result->id
                ];
            } else {
                throw new Exception("Paiement non complété : " . $response->result->status);
            }
            
        } catch (Exception $e) {
            error_log("PayPal Capture Error: " . $e->getMessage());
            $this->updateOrderStatus($localOrderId, 'cancelled');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Préparer les données de commande
     */
    private function prepareOrderData($cartItems, $userId) {
        $totalAmount = 0;
        $orderItems = [];
        
        foreach ($cartItems as $item) {
            // Vérifier le stock disponible
            $stmt = $this->db->prepare("
                SELECT tc.*, m.home_team_id, m.away_team_id, m.match_date,
                       ht.name as home_team, at.name as away_team
                FROM ticket_categories tc
                JOIN matches m ON tc.match_id = m.id
                JOIN teams ht ON m.home_team_id = ht.id
                JOIN teams at ON m.away_team_id = at.id
                WHERE tc.id = ? AND tc.available_capacity >= ?
            ");
            $stmt->execute([$item['ticket_category_id'], $item['quantity']]);
            $ticketCategory = $stmt->fetch();
            
            if (!$ticketCategory) {
                throw new Exception("Stock insuffisant pour: " . $item['ticket_category_id']);
            }
            
            $itemTotal = $ticketCategory['price'] * $item['quantity'];
            $totalAmount += $itemTotal;
            
            $orderItems[] = [
                'ticket_category_id' => $item['ticket_category_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $ticketCategory['price'],
                'total_price' => $itemTotal,
                'match_info' => $ticketCategory['home_team'] . ' vs ' . $ticketCategory['away_team'],
                'category_name' => $ticketCategory['name'],
                'match_date' => $ticketCategory['match_date']
            ];
        }
        
        return [
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'items' => $orderItems,
            'currency' => 'MAD'
        ];
    }
    
    /**
     * Construire le body de la commande PayPal
     */
    private function buildPayPalOrderBody($orderData) {
        $items = [];
        
        foreach ($orderData['items'] as $item) {
            $items[] = [
                'name' => $item['match_info'] . ' - ' . $item['category_name'],
                'description' => 'Billet pour le match du ' . date('d/m/Y H:i', strtotime($item['match_date'])),
                'unit_amount' => [
                    'currency_code' => $orderData['currency'],
                    'value' => number_format($item['unit_price'], 2, '.', '')
                ],
                'quantity' => (string)$item['quantity'],
                'category' => 'DIGITAL_GOODS'
            ];
        }
        
        return [
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => PayPalConfiguration::getReturnUrl(),
                'cancel_url' => PayPalConfiguration::getCancelUrl(),
                'brand_name' => APP_NAME,
                'locale' => 'fr-MA',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW'
            ],
            'purchase_units' => [
                [
                    'reference_id' => 'FOOTBALLTICKETS_' . time(),
                    'description' => 'Billets de football - ' . APP_NAME,
                    'amount' => [
                        'currency_code' => $orderData['currency'],
                        'value' => number_format($orderData['total_amount'], 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $orderData['currency'],
                                'value' => number_format($orderData['total_amount'], 2, '.', '')
                            ]
                        ]
                    ],
                    'items' => $items
                ]
            ]
        ];
    }
    
    /**
     * Sauvegarder la commande en base de données
     */
    private function saveOrderToDatabase($orderData, $paypalOrderId) {
        try {
            $this->db->beginTransaction();
            
            // Générer un numéro de commande unique
            $orderNumber = 'CMD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Insérer la commande principale
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, paypal_order_id)
                VALUES (?, ?, ?, 'pending', 'paypal', ?)
            ");
            $stmt->execute([
                $orderData['user_id'],
                $orderNumber,
                $orderData['total_amount'],
                $paypalOrderId
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Insérer les articles de la commande
            $stmt = $this->db->prepare("
                INSERT INTO order_items (order_id, ticket_category_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($orderData['items'] as $item) {
                $stmt->execute([
                    $orderId,
                    $item['ticket_category_id'],
                    $item['quantity'],
                    $item['unit_price']
                ]);
            }
            
            $this->db->commit();
            return $orderId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    // Autres méthodes utilitaires...
    private function getApprovalUrl($links) {
        foreach ($links as $link) {
            if ($link->rel === 'approve') {
                return $link->href;
            }
        }
        return null;
    }
    
    private function verifyStockAvailability($orderId) {
        $stmt = $this->db->prepare("
            SELECT oi.quantity, tc.available_capacity
            FROM order_items oi
            JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            if ($item['available_capacity'] < $item['quantity']) {
                return false;
            }
        }
        
        return true;
    }
    
    private function updateTicketStock($orderId) {
        $stmt = $this->db->prepare("
            UPDATE ticket_categories tc
            JOIN order_items oi ON tc.id = oi.ticket_category_id
            SET tc.available_capacity = tc.available_capacity - oi.quantity
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
    }
}
?>