SET time_zone = "+00:00";

CREATE TABLE `users` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `user_type` varchar(20) NOT NULL DEFAULT 'user',
  `status` varchar(50) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `products` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `author` varchar(100) NOT NULL,
  `price` int(100) NOT NULL,
  `stock` int(100) NOT NULL,
  `image` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);



CREATE TABLE `cart` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(100) NOT NULL,
  `quantity` int(100) NOT NULL,
  `image` varchar(100) NOT NULL
) 

CREATE TABLE `orders` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `user_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `number` varchar(12) NOT NULL,
  `email` varchar(100) NOT NULL,
  `method` varchar(50) NOT NULL,
  `address` varchar(500) NOT NULL,
  `total_products` varchar(1000) NOT NULL,
  `total_price` int(100) NOT NULL,
  `placed_on` varchar(50) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`)
)

CREATE TABLE `audit_log` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(100) NOT NULL,
  `operation` varchar(10) NOT NULL,
  `user_id` int(100) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `old_value` JSON,
  `new_value` JSON,
  PRIMARY KEY (`id`)
);



/*Triggers*/

/*For users*/
DELIMITER //
CREATE TRIGGER users_audit_trigger_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, operation, user_id, old_value, new_value)
    VALUES ('users', NEW.id, 'INSERT', 0, NULL, JSON_OBJECT('name', NEW.name, 'email', NEW.email, 'password', NEW.password, 'user_type', NEW.user_type, 'status', NEW.status));
END //
DELIMITER ;
DELIMITER //
CREATE TRIGGER users_audit_trigger_delete
AFTER DELETE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, operation, user_id, old_value, new_value)
    VALUES ('users', OLD.id, 'DELETE', 0, JSON_OBJECT('name', OLD.name, 'email', OLD.email, 'password', OLD.password, 'user_type', OLD.user_type, 'status', OLD.status), NULL);
END //
DELIMITER ;


/*For products*/
DELIMITER //
CREATE TRIGGER products_insert
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, operation, user_id, old_value, new_value)
    VALUES ('products', NEW.id, 'INSERT', 0, NULL, JSON_OBJECT('name', NEW.name, 'author', NEW.author, 'price', NEW.price, 'stock', NEW.stock, 'image', NEW.image, 'status', NEW.status));
END //
DELIMITER ;
DELIMITER //
CREATE TRIGGER products_update
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, operation, user_id, old_value, new_value)
    VALUES ('products', NEW.id, 'UPDATE', 0, JSON_OBJECT('name', OLD.name, 'author', OLD.author, 'price', OLD.price, 'stock', OLD.stock, 'image', OLD.image, 'status', OLD.status), JSON_OBJECT('name', NEW.name, 'author', NEW.author, 'price', NEW.price, 'stock', NEW.stock, 'image', NEW.image, 'status', NEW.status));
END //
DELIMITER ;

/*For Cart*/
DELIMITER //
CREATE TRIGGER cart_insert
AFTER INSERT ON cart
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, operation, user_id, old_value, new_value)
    VALUES ('cart', NEW.id, 'INSERT', NEW.user_id, NULL, JSON_OBJECT('name', NEW.name, 'price', NEW.price, 'quantity', NEW.quantity, 'image', NEW.image));
END //
DELIMITER ;

/*For order*/
DELIMITER //
CREATE TRIGGER orders_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, operation, user_id, old_value, new_value)
    VALUES ('orders', NEW.id, 'INSERT', NEW.user_id, NULL, JSON_OBJECT('name', NEW.name, 'number', NEW.number, 'email', NEW.email, 'method', NEW.method, 'address', NEW.address, 'total_products', NEW.total_products, 'total_price', NEW.total_price, 'placed_on', NEW.placed_on, 'payment_status', NEW.payment_status));
END //
DELIMITER ;
 /*Order status update*/
 DELIMITER //
CREATE TRIGGER orders_status_change_trigger
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    DECLARE admin_user_id INT;
    
    -- Check if the status column has been updated and the user updating is an admin
    IF OLD.status <> NEW.status THEN
        SELECT user_id INTO admin_user_id FROM audit_log WHERE table_name = 'users' AND record_id = NEW.user_id AND operation = 'UPDATE';
        -- Replace 'YourAdminUserId' with the actual user ID for your admin user(s)
        IF admin_user_id = YourAdminUserId THEN
            INSERT INTO audit_log (table_name, record_id, operation, user_id, old_value, new_value)
            VALUES ('orders', NEW.id, 'STATUS_CHANGE_BY_ADMIN', admin_user_id, JSON_OBJECT('status', OLD.status), JSON_OBJECT('status', NEW.status));
        END IF;
    END IF;
END //
DELIMITER ;

