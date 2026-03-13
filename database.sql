-- create database
CREATE DATABASE food_delivery;

-- use the database of food_delivery
USE food_delivery;

-- create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    img VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- insert data into categories table
INSERT INTO categories (name, img) VALUES
('Fast Food', '../images/fast_food.png'),
('Drinks', '../images/drinks.png'),
('Chinese Food', '../images/chinese_food.png'),
('Western Food', '../images/western_food.png'),
('BBQ', '../images/BBQ.png'),
('Pizza', '../images/pizza.png');

CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    rating DECIMAL(2,1),
    opening_hours VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO restaurants (category_id, name, address, rating, opening_hours) VALUES
(1, 'McDonald''s', '53 Ang Mo Kio Ave 3, #B2-35, AMK Hub, Singapore 569933', 4.3, '8:00 AM - 11:00 PM'),
(1, 'KFC Junction 8', '9 Bishan Place, #B1-18, Junction 8, Singapore 579837', 4.1, '10:00 AM - 10:00 PM'),
(1, 'Burger King Toa Payoh', '470 Lorong 6 Toa Payoh, #01-70, Singapore 310470', 4.0, '9:00 AM - 10:30 PM'),
(1, 'MOS Burger Nex', '23 Serangoon Central, #B1-12, NEX, Singapore 556083', 4.2, '10:00 AM - 10:00 PM');

INSERT INTO restaurants (category_id, name, address, rating, opening_hours) VALUES
(2, 'Starbucks AMK Hub', '53 Ang Mo Kio Ave 3, #01-20, AMK Hub, Singapore 569933', 4.5, '7:00 AM - 10:00 PM'),
(2, 'LiHO Tea Junction 8', '9 Bishan Place, #01-35, Junction 8, Singapore 579837', 4.2, '10:00 AM - 10:00 PM'),
(2, 'KOI Thé Nex', '23 Serangoon Central, #B1-45, NEX, Singapore 556083', 4.4, '10:00 AM - 10:00 PM'),
(2, 'The Alley Bugis Junction', '200 Victoria Street, #01-15, Bugis Junction, Singapore 188021', 4.3, '11:00 AM - 10:00 PM');

INSERT INTO restaurants (category_id, name, address, rating, opening_hours) VALUES
(3, 'Din Tai Fung', '290 Orchard Road, #B1-03, Paragon, Singapore 238859', 4.6, '11:00 AM - 10:00 PM'),
(3, 'Crystal Jade La Mian Xiao Long Bao', '68 Orchard Road, #04-19, Plaza Singapura, Singapore 238839', 4.3, '11:00 AM - 9:30 PM'),
(3, 'PUTIEN', '127 Kitchener Road, #01-01, Singapore 208514', 4.4, '11:30 AM - 9:30 PM'),
(3, 'Hai Di Lao Hot Pot', '181 Orchard Road, #03-07, Orchard Central, Singapore 238896', 4.7, '10:00 AM - 6:00 AM');

INSERT INTO restaurants (category_id, name, address, rating, opening_hours) VALUES
(4, 'Astons Specialities', '1 HarbourFront Walk, #01-157 VivoCity, Singapore 098585', 4.3, '11:00 AM - 10:00 PM'),
(4, 'The Manhattan Fish Market', '68 Orchard Road, #04-21 Plaza Singapura, Singapore 238839', 4.2, '11:00 AM - 9:30 PM'),
(4, 'Swensen''s', '9 Bishan Place, #02-05 Junction 8, Singapore 579837', 4.1, '10:30 AM - 10:00 PM'),
(4, 'Jack''s Place', '2 Orchard Turn, #03-10 ION Orchard, Singapore 238801', 4.2, '11:30 AM - 10:00 PM');

INSERT INTO restaurants (category_id, name, address, rating, opening_hours) VALUES
(5, 'Seoul Garden BBQ', '180 Kitchener Road, #02-30 City Square Mall, Singapore 208539', 4.2, '11:30 AM - 10:00 PM'),
(5, '8 Korean BBQ', '1 Tras Link, #01-08 Orchid Hotel, Singapore 078867', 4.4, '12:00 PM - 11:00 PM'),
(5, 'Supulae Korean BBQ', '92 Amoy Street, Singapore 069911', 4.3, '12:00 PM - 10:30 PM'),
(5, 'Guiga Korean BBQ', '43 Tanjong Pagar Road, Singapore 088464', 4.2, '11:30 AM - 11:00 PM');

INSERT INTO restaurants (category_id, name, address, rating, opening_hours) VALUES
(6, 'Pizza Hut AMK Hub', '53 Ang Mo Kio Ave 3, #02-20 AMK Hub, Singapore 569933', 4.2, '11:00 AM - 10:00 PM'),
(6, 'Domino''s Pizza Bishan', '150 Bishan Street 11, #01-135, Singapore 570150', 4.1, '10:30 AM - 11:00 PM'),
(6, 'Little Caesars Pizza', '1 HarbourFront Walk, #B2-12 VivoCity, Singapore 098585', 4.0, '11:00 AM - 10:00 PM'),
(6, 'Canadian Pizza', '500 Toa Payoh Lorong 6, #01-01, Singapore 310500', 4.1, '11:00 AM - 11:00 PM');

CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    menu_category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);

INSERT INTO menu_items (restaurant_id, menu_category, name, description, price, image) VALUES
(1, 'Burgers', 'Big Mac', 'Two beef patties with lettuce, cheese, pickles and special sauce', 8.50, '../images/menu/big_mac.png'),
(1, 'Burgers', 'McChicken', 'Crispy chicken burger with lettuce and mayonnaise', 7.20, '../images/menu/mcchicken.png'),
(1, 'Burgers', 'Filet-O-Fish', 'Fish fillet burger with tartar sauce and cheese', 7.80, '../images/menu/filet_o_fish.png'),
(1, 'Burgers', 'Double Cheeseburger', 'Two beef patties with double cheese and pickles', 8.90, '../images/menu/double_cheeseburger.png'),
(1, 'Burgers', 'Cheeseburger', 'Classic beef burger with cheese, pickles and ketchup', 5.90, '../images/menu/cheeseburger.png'),

(1, 'Sides', 'Fries', 'Golden crispy fries', 3.50, '../images/menu/fries.png'),
(1, 'Sides', 'Chicken McNuggets 6pc', 'Six pieces of crispy chicken nuggets', 4.90, '../images/menu/nuggets_6pc.png'),
(1, 'Sides', 'Chicken McNuggets 9pc', 'Nine pieces of crispy chicken nuggets', 6.50, '../images/menu/nuggets_9pc.png'),
(1, 'Sides', 'Apple Pie', 'Hot crispy apple pie dessert', 2.50, '../images/menu/apple_pie.png'),
(1, 'Sides', 'Corn Cup', 'Sweet corn served warm', 2.80, '../images/menu/corn_cup.png'),

(1, 'Drinks', 'Coca Cola', 'Chilled Coca Cola soft drink', 2.50, '../images/menu/coke.png'),
(1, 'Drinks', 'Sprite', 'Refreshing lemon-lime soda', 2.50, '../images/menu/sprite.png'),
(1, 'Drinks', 'Fanta', 'Orange flavoured sparkling drink', 2.50, '../images/menu/fanta.png'),
(1, 'Drinks', 'Milo', 'Cold chocolate malt drink', 3.20, '../images/menu/milo.png'),
(1, 'Drinks', 'Iced Lemon Tea', 'Refreshing iced lemon tea', 2.80, '../images/menu/iced_lemon_tea.png'),
(1, 'Drinks', 'Hot Coffee', 'Freshly brewed hot coffee', 2.90, '../images/menu/hot_coffee.png'),

(1, 'Desserts', 'Hot Fudge Sundae', 'Vanilla soft serve topped with hot fudge', 3.50, '../images/menu/hot_fudge_sundae.png'),
(1, 'Desserts', 'Strawberry Sundae', 'Vanilla soft serve topped with strawberry sauce', 3.50, '../images/menu/strawberry_sundae.png'),
(1, 'Desserts', 'Vanilla Cone', 'Classic vanilla soft serve cone', 1.50, '../images/menu/vanilla_cone.png'),
(1, 'Desserts', 'McFlurry Oreo', 'Vanilla soft serve blended with Oreo pieces', 4.20, '../images/menu/mcflurry_oreo.png');

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    restaurant_id INT,
    total_price DECIMAL(10,2),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    item_name VARCHAR(100),
    quantity INT,
    price DECIMAL(10,2)
);