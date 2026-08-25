# Database Features Documentation - Stuart's Comic Shop

This document outlines the core database features implemented in the **Stuart's Comic Shop** web application (CSE370 Database Systems Project). It describes how each feature works, which PHP files manage the logic, and how they interact with XAMPP MySQL tables.

---

## 1. User Authentication & Access Control

This system provides session-based security and divides users into **Customers** and **Administrators**.

- **Files:** `login.php`, `register.php`, `logout.php`
- **Database Tables:** `users`, `customer`, `admin`

### How it Works:
1. **Login:** Queries the `users` table by email. Compares the password (supports plain-text demo credentials like `demo123` and bcrypt hashes). If valid, it joins the `admin` table to check if the user is an administrator and sets `$_SESSION['role']` to `'admin'` or `'customer'`.
2. **Registration (Transaction-Safe):** When a new user registers, the script wraps the queries in an **SQL Transaction**. It first inserts the email and password into the `users` table, retrieves the auto-generated `user_id`, and then inserts a matching row into the `customer` table with `loyalty_pts = 0`. This prevents orphaned records and satisfies database foreign key constraints.
3. **Access Control:** Files like `admin/dashboard.php` and `customer/order-history.php` contain checks that redirect unauthorized users back to `login.php`.

```sql
-- Login Check
SELECT user_id, email, password FROM Users WHERE email = ?;
SELECT role FROM Admin WHERE user_id = ?;

-- Registration Transaction
START TRANSACTION;
INSERT INTO Users (email, password) VALUES ('new@test.com', 'pass123');
INSERT INTO Customer (user_id, loyalty_pts) VALUES (LAST_INSERT_ID(), 0);
COMMIT;
```

---

## 2. Product Catalog & Shopping Cart

Displays shop inventory and manages temporary cart sessions before checkout.

- **Files:** `products.php`, `product.php`, `cart.php`, `cart-action.php`
- **Database Tables/Views:** `products`, `categories`, `comics`, `merchandise`, `product_rating_summary` (SQL View)

### How it Works:
1. **Catalog & Filters:** Fetches product details and calculates average ratings by joining the main `products` table with the `product_rating_summary` view. Filters by `category_id` using prepared statements.
2. **Session-based Cart:** Items added to the cart are stored inside **`$_SESSION['cart']`** (an associative array of `product_id => quantity`). This avoids unnecessary database writes while the user is still browsing, keeping memory usage fast. It is only written to the database once the order is finalized.

```sql
-- Fetch catalog products with category filter and rating view
SELECT p.product_id, p.name, p.price, p.stock, prs.average_rating, COALESCE(prs.total_reviews, 0) AS total_reviews
FROM Products p
LEFT JOIN product_rating_summary prs ON p.product_id = prs.product_id
WHERE p.category_id = ?;
```

---

## 3. Checkout & Transactional Ordering

Processes customer orders securely, validating stock levels and finalizing purchases.

- **Files:** `checkout.php`, `place-order.php`
- **Database Tables:** `orders`, `order_items`, `products` (stock), `customer` (loyalty points)

### How it Works:
The ordering process executes inside an **SQL Transaction** (`mysqli_begin_transaction`) to maintain data integrity:
1. **Stock Lock:** Queries the `products` table for the items in the cart and locks the rows using `FOR UPDATE` to prevent race conditions (e.g., two users checking out the last item simultaneously).
2. **Order Creation:** Inserts a single record into the `orders` table.
3. **Items & Stock Deduction:** Loops through the cart items, inserting a record into `order_items` for each item, and decrementing the `stock` in `products`.
4. **Loyalty Deductions:** If the user claimed a loyalty points discount, their point balance in the `customer` table is updated.
5. **Commit/Rollback:** If any step fails (e.g., stock is insufficient), the entire transaction is rolled back (`mysqli_rollback`), leaving the database clean.

```sql
START TRANSACTION;
-- Lock stock details
SELECT product_id, name, price, stock FROM Products WHERE product_id IN (1, 2) FOR UPDATE;
-- Insert main order record
INSERT INTO Orders (user_id, Date, Status, Shipping_address) VALUES (?, CURRENT_DATE, 'PENDING', ?);
-- Insert order items & update stock
INSERT INTO Order_Items (Order_id, item_no, product_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?);
UPDATE Products SET stock = stock - ? WHERE product_id = ?;
COMMIT;
```

---

## 4. True Fan Loyalty Points System

Reward program that automatically awards points for comic book purchases and allows discount redemptions during checkout.

- **Files:** `checkout.php`, `place-order.php`
- **Database Triggers:** `add_loyalty_points` (runs `AFTER INSERT ON Order_Items`)
- **Database Tables:** `customer`, `comics`, `order_items`, `orders`

### How it Works:
1. **Earning (Database Trigger):** An database trigger is configured to run after any insertion in `order_items`. It checks if the purchased `product_id` belongs to the `comics` table. If yes, it calculates `10 points * quantity` and increments the customer's `loyalty_pts` in the `customer` table.
2. **Redemption:** During checkout, the customer's loyalty points are fetched. If `loyalty_pts > 0`, a form allows them to redeem points. Each point gives a `$0.10` discount. The PHP backend determines the maximum points usable based on the order subtotal. On ordering, the redeemed points are subtracted from the customer's profile.

```sql
-- Database Trigger
CREATE TRIGGER add_loyalty_points AFTER INSERT ON Order_Items
FOR EACH ROW
BEGIN
    DECLARE is_comic INT DEFAULT 0;
    DECLARE customer_id INT;
    SELECT COUNT(*) INTO is_comic FROM Comics WHERE product_id = NEW.product_id;
    IF is_comic > 0 THEN
        SELECT user_id INTO customer_id FROM Orders WHERE Order_id = NEW.Order_id;
        UPDATE Customer SET loyalty_pts = loyalty_pts + (10 * NEW.quantity) WHERE user_id = customer_id;
    END IF;
END;
```

---

## 5. Comic-Con Events & Ticketing

Allows users to browse local conventions and reserve specific seats dynamically.

- **Files:** `events.php`, `buy-ticket.php`
- **Database Tables:** `events`, `tickets`, `customer`

### How it Works:
1. **Event Listings:** Displays upcoming events along with available seats, calculated by subtracting the booked tickets from the event's `max_seats`.
2. **Seat Booking (Transaction-Safe):** When a user enters a seat number and books a ticket, the backend starts a transaction:
   - Locks the event capacity and current bookings using `FOR UPDATE`.
   - Counts sold tickets to verify if the event is sold out.
   - Checks if the selected seat is already occupied.
   - If available, inserts a record into the `tickets` table linking the `user_id`, `event_id`, and `seat_no`.

```sql
START TRANSACTION;
-- Lock capacity
SELECT max_seats, name FROM Events WHERE event_id = ? FOR UPDATE;
-- Check seat booking duplication
SELECT COUNT(*) AS seat_taken FROM Tickets WHERE event_id = ? AND seat_no = ?;
-- Book seat ticket
INSERT INTO Tickets (user_id, seat_no, event_id) VALUES (?, ?, ?);
COMMIT;
```

---

## 6. Product Reviews & Ratings

Enables customers to write text reviews and submit 1–5 star ratings.

- **Files:** `product.php`, `add-review.php`
- **Database Tables:** `reviews`, `orders`, `order_items`

### How it Works:
1. **Display:** Fetches all reviews submitted for a specific product ID and displays them.
2. **Verification Check:** To prevent fake reviews, the page verifies if the user is a verified buyer. It runs a query checking if there is a `COMPLETED` order in the `orders` and `order_items` tables linking the logged-in `user_id` and the `product_id`. If true, the review submission form is shown.

```sql
-- Verified Buyer Check
SELECT EXISTS(
    SELECT 1 FROM Orders o
    JOIN Order_Items oi ON o.Order_id = oi.Order_id
    WHERE o.user_id = ? AND oi.product_id = ? AND o.Status = 'COMPLETED'
) AS has_purchased;

-- Submit Review
INSERT INTO Reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?);
```

---

## 7. Admin Sales Dashboard

Shop owner control center presenting aggregated metrics and order operations.

- **Files:** `admin/dashboard.php`
- **Database Tables:** `orders`, `order_items`, `products`, `users`, `customer`

### How it Works:
1. **Total Revenue:** Aggregates order sales using SQL `SUM` on items in the cart.
2. **Out of Stock Items:** Displays items where the stock column equals 0.
3. **Top Customers:** Groups customer orders by `user_id` and email, orders them descending by the sum of their total spent, and limits the list to the top 5 spenders.
4. **Order Status Manager:** Fetches all orders. Admins can update the `Status` column of the `orders` table to update order stages.

```sql
-- Total Revenue
SELECT SUM(quantity * unit_price) AS total_revenue FROM Order_Items;

-- Top Spenders
SELECT u.email, SUM(oi.quantity * oi.unit_price) AS total_spent, c.loyalty_pts
FROM Orders o
JOIN Order_Items oi ON o.Order_id = oi.Order_id
JOIN Users u ON o.user_id = u.user_id
JOIN Customer c ON o.user_id = c.user_id
GROUP BY o.user_id, u.email, c.loyalty_pts
ORDER BY total_spent DESC LIMIT 5;

-- Update Order Status
UPDATE Orders SET Status = ? WHERE Order_id = ?;
```
