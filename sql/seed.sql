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

INSERT INTO Categories (category_id, name) VALUES
    (1, 'DC Comics'),
    (2, 'Marvel Comics'),
    (3, 'Independent Comics'),
    (4, 'Apparel'),
    (5, 'Figures'),
    (6, 'Manga'),
    (7, 'Graphic Novels'),
    (8, 'Collectibles');

INSERT INTO Products (product_id, name, price, stock, category_id) VALUES
    (1, 'Batman: Year One', 15.99, 8, 1),
    (2, 'Spider-Man: Blue', 18.50, 5, 2),
    (3, 'Watchmen', 22.00, 0, 3),
    (4, 'Batman T-Shirt', 25.00, 10, 4),
    (5, 'Superman Figure', 35.00, 4, 5),
    (6, 'Iron Man Gauntlet', 120.00, 3, 5),
    (7, 'Saga - Volume 1', 14.99, 12, 3),
    (8, 'Spidey Classic Hoodie', 45.00, 6, 4),
    (9, 'Sandman: Overture', 19.99, 7, 1),
    (10, 'X-Men Classic Cap', 22.00, 15, 4),
    (11, 'Joker Art Figure', 40.00, 2, 5),
    (12, 'Batman: Dark Knight Returns', 16.99, 9, 1),
    (13, 'Demon Slayer: Volume 1', 12.99, 14, 6),
    (14, 'One Piece: East Blue', 13.50, 11, 6),
    (15, 'Maus', 21.99, 8, 7),
    (16, 'The Walking Dead: Compendium One', 39.99, 5, 7),
    (17, 'Wonder Woman Collectible Mug', 14.99, 18, 8),
    (18, 'Spider-Man Enamel Pin', 8.99, 25, 8);

INSERT INTO Comics (product_id, issue_no, author) VALUES
    (1, '1', 'Frank Miller'),
    (2, '1', 'Jeph Loeb'),
    (3, '1', 'Alan Moore'),
    (7, '1', 'Brian K. Vaughan'),
    (9, '1', 'Neil Gaiman'),
    (12, '1', 'Frank Miller'),
    (13, '1', 'Koyoharu Gotouge'),
    (14, 'East Blue', 'Eiichiro Oda'),
    (15, 'Complete Edition', 'Art Spiegelman'),
    (16, 'Compendium 1', 'Robert Kirkman');

INSERT INTO Merchandise (product_id, size, material) VALUES
    (4, 'L', 'Cotton'),
    (5, 'Standard', 'PVC'),
    (6, '1:1 Scale', 'Metal/ABS'),
    (8, 'M', 'Polyester Blend'),
    (10, 'Adjustable', 'Polyester'),
    (11, '7-inch', 'Vinyl'),
    (17, 'Standard', 'Ceramic'),
    (18, '1.25-inch', 'Enamel');

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

INSERT INTO Events (event_id, name, date, location, max_seats) VALUES
    (1, 'Gotham Comic-Con 2026', '2026-10-15', 'Gotham Convention Center', 50),
    (2, 'Metropolis Mini-Con', '2026-11-20', 'Daily Planet Exhibition Hall', 30),
    (3, 'Anime & Manga Expo', '2026-12-05', 'Stuart\'s Comic Shop Backroom', 15),
    (4, 'Cosplay Championship 2026', '2026-12-20', 'Stuart\'s Main Hall', 40),
    (5, 'Stan Lee Tribute Seminar', '2026-10-28', 'Grand Seminar Auditorium', 100),
    (6, 'Marvel vs DC Trivia Night', '2026-11-05', 'Stuart\'s Comic Cafe', 25),
    (7, 'Indie Comic Creators Panel', '2026-11-15', 'Community Library Room B', 35);

INSERT INTO Tickets (ticket_id, user_id, seat_no, event_id) VALUES
    (1, 1, 'S-1', 1),
    (2, 2, 'S-2', 1),
    (3, 1, 'S-1', 2);

INSERT INTO Quiz_Questions
    (quiz_id, question, option_a, option_b, option_c, option_d, correct_option, reward_points)
VALUES
    (1, 'What is Batman''s real name?', 'Clark Kent', 'Bruce Wayne', 'Peter Parker', 'Tony Stark', 'B', 10),
    (2, 'Which city does Superman protect?', 'Gotham City', 'Central City', 'Metropolis', 'Wakanda', 'C', 10),
    (3, 'Who created Spider-Man with Steve Ditko?', 'Stan Lee', 'Alan Moore', 'Frank Miller', 'Neil Gaiman', 'A', 10),
    (4, 'Which material can weaken Superman?', 'Vibranium', 'Adamantium', 'Kryptonite', 'Uru', 'C', 10),
    (5, 'What is Wonder Woman''s home island called?', 'Genosha', 'Themyscira', 'Asgard', 'Atlantis', 'B', 10);
