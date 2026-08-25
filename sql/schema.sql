CREATE DATABASE comic_shop;
USE comic_shop;

CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE Admin (
    user_id INT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    CONSTRAINT fk_admin_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE Customer (
    user_id INT PRIMARY KEY,
    loyalty_pts INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_customer_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE Customer_Address (
    user_id INT NOT NULL,
    House_No VARCHAR(20) NOT NULL,
    Street VARCHAR(100) NOT NULL,
    City VARCHAR(50) NOT NULL,
    Zip_Code VARCHAR(10) NOT NULL,
    PRIMARY KEY (user_id, House_No, Street, City, Zip_Code),
    CONSTRAINT fk_customer_address_customer
        FOREIGN KEY (user_id) REFERENCES Customer(user_id)
        ON DELETE CASCADE
);

CREATE TABLE Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE Products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    category_id INT NULL,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES Categories(category_id)
        ON DELETE SET NULL
);

CREATE TABLE Comics (
    product_id INT PRIMARY KEY,
    issue_no VARCHAR(20) NOT NULL,
    author VARCHAR(100) NOT NULL,
    CONSTRAINT fk_comics_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE CASCADE
);

CREATE TABLE Merchandise (
    product_id INT PRIMARY KEY,
    size VARCHAR(20),
    material VARCHAR(50),
    CONSTRAINT fk_merchandise_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE CASCADE
);

CREATE TABLE Orders (
    Order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    Date DATE NOT NULL DEFAULT (CURRENT_DATE),
    Status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    Shipping_address VARCHAR(255) NOT NULL,
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (user_id) REFERENCES Customer(user_id)
        ON DELETE RESTRICT
);

CREATE TABLE Order_Items (
    Order_id INT NOT NULL,
    item_no INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1 CHECK (quantity > 0),
    unit_price DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (Order_id, item_no),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (Order_id) REFERENCES Orders(Order_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE RESTRICT
);

CREATE TABLE Events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    max_seats INT NOT NULL DEFAULT 50
);

CREATE TABLE Tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    seat_no VARCHAR(10) NOT NULL,
    event_id INT NOT NULL,
    CONSTRAINT fk_tickets_customer
        FOREIGN KEY (user_id) REFERENCES Customer(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_tickets_event
        FOREIGN KEY (event_id) REFERENCES Events(event_id)
        ON DELETE CASCADE
);

CREATE TABLE Reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment VARCHAR(1000),
    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (user_id) REFERENCES Customer(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE CASCADE
);

CREATE TABLE Quiz_Questions (
    quiz_id INT PRIMARY KEY AUTO_INCREMENT,
    question VARCHAR(255) NOT NULL,
    option_a VARCHAR(150) NOT NULL,
    option_b VARCHAR(150) NOT NULL,
    option_c VARCHAR(150) NOT NULL,
    option_d VARCHAR(150) NOT NULL,
    correct_option CHAR(1) NOT NULL CHECK (correct_option IN ('A', 'B', 'C', 'D')),
    reward_points INT NOT NULL DEFAULT 10 CHECK (reward_points > 0),
    is_active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE Quiz_Attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    selected_option CHAR(1) NOT NULL CHECK (selected_option IN ('A', 'B', 'C', 'D')),
    is_correct BOOLEAN NOT NULL,
    points_awarded INT NOT NULL DEFAULT 0 CHECK (points_awarded >= 0),
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_quiz_attempt UNIQUE (user_id, quiz_id),
    CONSTRAINT fk_quiz_attempt_customer
        FOREIGN KEY (user_id) REFERENCES Customer(user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempt_question
        FOREIGN KEY (quiz_id) REFERENCES Quiz_Questions(quiz_id)
        ON DELETE CASCADE
);

DELIMITER //
CREATE TRIGGER add_loyalty_points
AFTER INSERT ON Order_Items
FOR EACH ROW
BEGIN
    DECLARE is_comic INT DEFAULT 0;
    DECLARE customer_id INT;
    
    -- Check if the product is a Comic
    SELECT COUNT(*) INTO is_comic FROM Comics WHERE product_id = NEW.product_id;
    
    IF is_comic > 0 THEN
        -- Get the user_id (customer_id) of the order
        SELECT user_id INTO customer_id FROM Orders WHERE Order_id = NEW.Order_id;
        
        -- Add 10 loyalty points per comic book purchased
        UPDATE Customer 
        SET loyalty_pts = loyalty_pts + (10 * NEW.quantity)
        WHERE user_id = customer_id;
    END IF;
END //
DELIMITER ;
