INSERT INTO Users (user_id, email, password) VALUES
    (1, 'customer1@stuartscomicshop.test', 'demo123'),
    (2, 'customer2@stuartscomicshop.test', 'demo123'),
    (3, 'customer3@stuartscomicshop.test', 'demo123'),
    (4, 'admin@stuartscomicshop.test', 'demo123');

INSERT INTO Customer (user_id, loyalty_pts) VALUES
    (1, 20),
    (2, 10),
    (3, 0);

INSERT INTO Admin (user_id, role) VALUES
    (4, 'Shop Admin');

INSERT INTO Products (product_id, name, price, stock) VALUES
    (1, 'Batman: Year One', 15.99, 8),
    (2, 'Spider-Man: Blue', 18.50, 5),
    (3, 'Watchmen', 22.00, 0),
    (4, 'Batman T-Shirt', 25.00, 10),
    (5, 'Superman Figure', 35.00, 4);

INSERT INTO Comics (product_id, issue_no, author) VALUES
    (1, '1', 'Frank Miller'),
    (2, '1', 'Jeph Loeb'),
    (3, '1', 'Alan Moore');

INSERT INTO Merchandise (product_id, size, material) VALUES
    (4, 'L', 'Cotton'),
    (5, 'Standard', 'PVC');

INSERT INTO Orders (Order_id, user_id, Date, Status, Shipping_address) VALUES
    (1, 1, '2026-08-10', 'COMPLETED', '12 Comic Street'),
    (2, 2, '2026-08-11', 'COMPLETED', '25 Hero Avenue'),
    (3, 1, '2026-08-12', 'COMPLETED', '12 Comic Street'),
    (4, 3, '2026-08-13', 'COMPLETED', '8 Gotham Road');

INSERT INTO Order_Items (Order_id, item_no, product_id, quantity, unit_price) VALUES
    (1, 1, 1, 2, 15.99),
    (1, 2, 4, 1, 25.00),
    (2, 1, 2, 1, 18.50),
    (2, 2, 5, 1, 35.00),
    (3, 1, 1, 3, 15.99),
    (3, 2, 2, 1, 18.50),
    (4, 1, 4, 2, 25.00);

INSERT INTO Reviews (review_id, user_id, product_id, rating, comment) VALUES
    (1, 1, 1, 5, 'Excellent Batman comic.'),
    (2, 2, 2, 4, 'Great Spider-Man story and artwork.'),
    (3, 3, 4, 5, 'Excellent Batman shirt.'),
    (4, 1, 2, 4, 'Really enjoyable Spider-Man story.'),
    (5, 2, 5, 5, 'Great Superman figure.'),
    (6, 3, 4, 3, 'Good shirt and comfortable material.');
