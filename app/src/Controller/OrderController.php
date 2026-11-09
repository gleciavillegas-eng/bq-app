<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    public const DELIVERY_FEE = 50.00;

    /* =========================================================
       STEP 1: ORDER NOW
    ========================================================= */

    #[Route('/order-now', name: 'app_order_now')]
    public function orderNow(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $session = $request->getSession();
        $post = $request->request->all();

        $productId = (int) ($request->query->get('product') ?? ($post['product'] ?? 0));
        $product = $productId > 0 ? $em->find(Product::class, $productId) : null;

        if (!$product || $product->getStatus() !== 'Available') {
            return $this->redirectToRoute('app_menu');
        }

        $cart = $session->get('pending_cart', []);
        $saved = $session->get('pending_customer', []);

        $values = [
            'customer_name' => trim((string) ($post['customer_name'] ?? $saved['customer_name'] ?? $user->getName())),
            'email' => trim((string) ($post['email'] ?? $saved['email'] ?? $user->getEmail())),
            'phone' => trim((string) ($post['phone'] ?? $saved['phone'] ?? $user->getPhone())),
            'address' => trim((string) ($post['address'] ?? $saved['address'] ?? '')),
            'order_type' => (string) ($post['order_type'] ?? $saved['order_type'] ?? 'delivery'),
            'payment_method' => (string) ($post['payment_method'] ?? $saved['payment_method'] ?? 'Cash on Delivery'),
            'notes' => trim((string) ($post['notes'] ?? $saved['notes'] ?? '')),
        ];

        $quantity = max(1, min(50, (int) ($post['quantity'] ?? 1)));
        $errors = [];

        if ($request->isMethod('POST')) {
            if ($values['customer_name'] === '') {
                $errors[] = 'Please enter your full name.';
            }

            if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if ($values['phone'] === '') {
                $errors[] = 'Please enter your phone number.';
            }

            if (!in_array($values['order_type'], ['delivery', 'pickup'], true)) {
                $errors[] = 'Please select Delivery or Pickup.';
            }

            if ($values['order_type'] === 'delivery' && $values['address'] === '') {
                $errors[] = 'Please enter your delivery address.';
            }

            if (!in_array($values['payment_method'], ['Cash on Delivery', 'Cash on Pickup'], true)) {
                $errors[] = 'Please select a valid payment method.';
            }

            if (!$errors) {
                $key = (string) $product->getId();

                if (isset($cart[$key])) {
                    $cart[$key]['quantity'] += $quantity;
                } else {
                    $cart[$key] = [
                        'product_id' => $product->getId(),
                        'product_name' => $product->getName(),
                        'product_description' => (string) $product->getDescription(),
                        'product_price' => $product->getPrice(),
                        'product_image' => (string) $product->getImage(),
                        'quantity' => $quantity,
                    ];
                }

                $session->set('pending_cart', $cart);
                $session->set('pending_customer', $values);

                // "Add Another Order": balik sa menu nga naka-save na ang gipili
                if (isset($post['add_another'])) {
                    $this->addFlash('success', $product->getName() . ' was added to your order. Choose another item.');

                    return $this->redirectToRoute('app_menu');
                }

                return $this->redirectToRoute('app_order_confirm');
            }
        }

        $cartSubtotal = 0.0;
        foreach ($cart as $item) {
            $cartSubtotal += (float) $item['product_price'] * (int) $item['quantity'];
        }

        return $this->render('order/order_now.html.twig', [
            'product' => $product,
            'values' => $values,
            'quantity' => $quantity,
            'errors' => $errors,
            'cart' => $cart,
            'cart_subtotal' => $cartSubtotal,
            'delivery_fee' => self::DELIVERY_FEE,
        ]);
    }

    /* =========================================================
       STEP 2: REVIEW + PLACE ORDER
    ========================================================= */

    #[Route('/order-confirm', name: 'app_order_confirm')]
    public function confirm(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $session = $request->getSession();

        $cart = $session->get('pending_cart', []);
        $customer = $session->get('pending_customer', []);

        if (!$cart || !$customer) {
            return $this->redirectToRoute('app_menu');
        }

        $customerName = trim((string) ($customer['customer_name'] ?? $user->getName()));
        $email = trim((string) ($customer['email'] ?? $user->getEmail()));
        $phone = trim((string) ($customer['phone'] ?? $user->getPhone()));
        $address = trim((string) ($customer['address'] ?? ''));
        $paymentMethod = (string) ($customer['payment_method'] ?? 'Cash on Delivery');
        $notes = trim((string) ($customer['notes'] ?? ''));
        $orderType = ($customer['order_type'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery';

        $subtotal = 0.0;
        foreach ($cart as $item) {
            $subtotal += (float) $item['product_price'] * max(1, (int) $item['quantity']);
        }

        $deliveryFee = $orderType === 'delivery' ? self::DELIVERY_FEE : 0.0;
        $total = $subtotal + $deliveryFee;

        $error = '';

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('place_order', (string) $request->request->get('_token'))) {
                $error = 'Invalid security token. Please try again.';
            } elseif ($customerName === '') {
                $error = 'Customer name is required.';
            } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please provide a valid email address.';
            } elseif ($phone === '') {
                $error = 'Phone number is required.';
            } elseif ($orderType === 'delivery' && $address === '') {
                $error = 'Delivery address is required.';
            } else {
                try {
                    $order = new Order();
                    $order->setUser($user)
                        ->setCustomerName($customerName)
                        ->setEmail($email)
                        ->setPhone($phone)
                        ->setAddress($address)
                        ->setOrderType($orderType)
                        ->setPaymentMethod($paymentMethod)
                        ->setNotes($notes !== '' ? $notes : null)
                        ->setStatus('Pending');

                    $orderSubtotal = 0.0;

                    foreach ($cart as $key => $item) {
                        // Gamiton ang presyo karon sa database
                        $product = $em->find(Product::class, (int) $key);

                        if (!$product || $product->getStatus() !== 'Available') {
                            throw new \RuntimeException(
                                ($item['product_name'] ?? 'An item') . ' is no longer available. Please remove it from your order.'
                            );
                        }

                        $quantity = max(1, (int) $item['quantity']);
                        $unitPrice = (float) $product->getPrice();
                        $lineTotal = $unitPrice * $quantity;
                        $orderSubtotal += $lineTotal;

                        $orderItem = new OrderItem();
                        $orderItem->setProduct($product)
                            ->setProductKey((string) $product->getId())
                            ->setProductName($product->getName())
                            ->setUnitPrice(number_format($unitPrice, 2, '.', ''))
                            ->setQuantity($quantity)
                            ->setLineTotal(number_format($lineTotal, 2, '.', ''));

                        $order->addItem($orderItem);
                    }

                    $order->setSubtotal(number_format($orderSubtotal, 2, '.', ''))
                        ->setDeliveryFee(number_format($deliveryFee, 2, '.', ''))
                        ->setTotal(number_format($orderSubtotal + $deliveryFee, 2, '.', ''));

                    $em->persist($order);
                    $em->flush();

                    $session->remove('pending_cart');
                    $session->remove('pending_customer');

                    return $this->redirect('/receipt/' . $order->getId());
                } catch (\RuntimeException $e) {
                    $error = $e->getMessage();
                } catch (\Throwable $e) {
                    $error = 'We could not place your order. Please try again.';
                }
            }
        }

        return $this->render('order/order_confirm.html.twig', [
            'cart' => $cart,
            'customer_name' => $customerName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'order_type' => $orderType,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'error' => $error,
            'first_product' => array_key_first($cart),
        ]);
    }

    /* =========================================================
       REMOVE ITEM FROM PENDING CART
    ========================================================= */

    #[Route('/order-confirm/remove/{id}', name: 'app_order_remove', methods: ['POST'])]
    public function remove(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $session = $request->getSession();
        $cart = $session->get('pending_cart', []);

        if ($this->isCsrfTokenValid('remove_item', (string) $request->request->get('_token'))) {
            unset($cart[$id]);
            $session->set('pending_cart', $cart);
        }

        return $this->redirectToRoute($cart ? 'app_order_confirm' : 'app_menu');
    }
}