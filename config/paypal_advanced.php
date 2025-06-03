<?php
require_once '../vendor/autoload.php';
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

class PayPalConfiguration {
    private static $client;
    
    public static function client() {
        if (!self::$client) {
            self::$client = self::buildClient();
        }
        return self::$client;
    }
    
    private static function buildClient() {
        $environment = self::environment();
        return new PayPalHttpClient($environment);
    }
    
    private static function environment() {
        $clientId = self::getClientId();
        $clientSecret = self::getClientSecret();
        
        if (APP_ENV === 'production') {
            return new ProductionEnvironment($clientId, $clientSecret);
        } else {
            return new SandboxEnvironment($clientId, $clientSecret);
        }
    }
    
    private static function getClientId() {
        if (APP_ENV === 'production') {
            return 'YOUR_PRODUCTION_CLIENT_ID';
        }
        return 'YOUR_SANDBOX_CLIENT_ID';
    }
    
    private static function getClientSecret() {
        if (APP_ENV === 'production') {
            return 'YOUR_PRODUCTION_CLIENT_SECRET';
        }
        return 'YOUR_SANDBOX_CLIENT_SECRET';
    }
    
    // Configuration des webhooks
    public static function getWebhookId() {
        if (APP_ENV === 'production') {
            return 'YOUR_PRODUCTION_WEBHOOK_ID';
        }
        return 'YOUR_SANDBOX_WEBHOOK_ID';
    }
    
    // URLs de retour
    public static function getReturnUrl() {
        return BASE_URL . 'payment/success.php';
    }
    
    public static function getCancelUrl() {
        return BASE_URL . 'payment/cancel.php';
    }
}
?>