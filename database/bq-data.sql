-- =========================================================
-- BURGER QUEEN - SEED DATA
-- I-import PAGKATAPOS sa migrations (doctrine:migrations:migrate)
-- phpMyAdmin: burger_queen -> Import -> Choose file -> Go
-- I-import usa ra ka beses.
--
-- Accounts:
--   admin   / admin123    (Admin)
--   staff1  / staff12345  (Staff)
--   julina  / 12345678    (Customer)
-- =========================================================

INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `phone`, `role`, `status`, `crowns`, `created_at`) VALUES
(4, 'admin', 'Burger Queen Admin', 'admin@burgerqueen.com', '$2y$10$QD2gfRmk.a7VQmn81eLOVec95beCkSRZa.b3Peg9KuV.crzmEz8gm', '09123456789', 'admin', 'active', 0, '2026-09-12 14:17:34'),
(5, 'julina', 'julina', 'julina@gmail.com', '$2y$10$BFRNxbJHGnb4Mhu8os1iz.k5fn7XNwknpYqzWbujLCTZvp.wgUd2.', '09123456789', 'customer', 'active', 0, '2026-09-14 06:14:27'),
(6, 'staff1', 'Staff One', 'staff1@burgerqueen.com', '$2y$10$Sx3thkOZ5VZQbejBx5OYdux6jduygPJDU/WlhnlphLKGyZ7qO2rne', '09123456789', 'staff', 'active', 0, '2026-11-09 10:00:00');

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `image`, `status`, `created_by_id`, `created_at`, `updated_at`) VALUES
(9, 'Royal Fries', 'Golden crispy fries seasoned to royal perfection.', 99.00, 'Fries', 'assets/images/royalfries.jpg', 'Available', 4, '2026-09-14 07:12:19', NULL),
(10, 'Crispy Onion Rings', 'Golden crispy onion rings with a delicious crunch.', 109.00, 'Sides', 'assets/images/onion.jpg', 'Available', 4, '2026-09-14 07:12:19', NULL),
(11, 'Classic Vanilla Shake', 'Creamy vanilla shake made for a royal treat.', 129.00, 'Drinks', 'assets/images/vanilla.jpg', 'Available', 4, '2026-09-14 07:12:19', NULL),
(15, 'burger', 'burger', 299.00, 'Burgers', 'assets/uploads/products/hero-burger-f54b71478f24.png', 'Available', 4, '2026-09-15 18:34:41', NULL);

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `email`, `phone`, `address`, `order_type`, `payment_method`, `subtotal`, `delivery_fee`, `total`, `notes`, `status`, `rider_latitude`, `rider_longitude`, `rider_last_update`, `created_at`) VALUES
(3, 5, 'julina', 'julina@gmail.com', '09123456789', 'SAN JOSE NEGROS ORIENTAL', 'delivery', 'Cash on Delivery', 567.00, 50.00, 617.00, 'burger', 'Out for Delivery', NULL, NULL, NULL, '2026-09-15 15:52:36');

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_key`, `product_name`, `unit_price`, `quantity`, `line_total`) VALUES
(1, 3, NULL, 'royal-burger', 'Royal Burger', 189.00, 3, 567.00);
