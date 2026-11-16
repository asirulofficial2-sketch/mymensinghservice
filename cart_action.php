<?php
session_start();

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$id || !$action) {
    header("Location: shop_cart.php");
    exit;
}

if (!isset($_SESSION['shop_cart'])) {
    $_SESSION['shop_cart'] = [];
}

$cart = $_SESSION['shop_cart'];

switch ($action) {
    case 'add':
        if (isset($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }
        $_SESSION['shop_cart'] = $cart;
        header("Location: " . ($_GET['redirect'] ?? 'shop_cart.php'));
        exit;
        
    case 'plus':
        if (isset($cart[$id])) {
            $cart[$id]++;
        }
        break;
        
    case 'minus':
        if (isset($cart[$id])) {
            $cart[$id]--;
            if ($cart[$id] <= 0) {
                unset($cart[$id]);
            }
        }
        break;
        
    case 'remove':
        unset($cart[$id]);
        break;
        
    case 'clear':
        $cart = [];
        break;
}

$_SESSION['shop_cart'] = $cart;
header("Location: shop_cart.php");
exit;
?>