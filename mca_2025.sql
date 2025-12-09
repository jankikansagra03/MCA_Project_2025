-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 09, 2025 at 02:49 AM
-- Server version: 8.4.3
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mca_2025`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `Addresses_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Delete` (IN `p_id` INT)   BEGIN
    DELETE FROM addresses
    WHERE id = p_id;
END$$

DROP PROCEDURE IF EXISTS `Addresses_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Insert` (IN `p_user_id` VARCHAR(50), IN `p_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_mobile` VARCHAR(15), IN `p_address` TEXT)   BEGIN
    INSERT INTO addresses (user_id, name, email, mobile, address)
    VALUES (p_user_id, p_name, p_email, p_mobile, p_address);
    
    SELECT LAST_INSERT_ID() AS address_id;
END$$

DROP PROCEDURE IF EXISTS `Addresses_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Select` (IN `p_user_id` VARCHAR(50), IN `p_address_id` INT)   BEGIN
    SELECT * FROM addresses
    WHERE
        -- Match the user ID
        (p_user_id IS NULL OR user_id = p_user_id)
        AND
        -- If p_address_id is NOT NULL, find by that ID.
        -- If it IS NULL, this part is ignored.
        (p_address_id IS NULL OR id = p_address_id);
END$$

DROP PROCEDURE IF EXISTS `Addresses_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Update` (IN `p_id` INT, IN `p_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_mobile` VARCHAR(15), IN `p_address` TEXT)   BEGIN
    UPDATE addresses
    SET
        name = p_name,
        email = p_email,
        mobile = p_mobile,
        address = p_address
    WHERE
        id = p_id;
END$$

DROP PROCEDURE IF EXISTS `Cart_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Delete` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT)   BEGIN
    DELETE FROM cart 
    WHERE user_email = p_user_email 
    AND product_id = p_product_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Cart_Empty`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Empty` (IN `p_Email` VARCHAR(255))   BEGIN
    DELETE FROM cart WHERE user_email = p_Email;
END$$

DROP PROCEDURE IF EXISTS `Cart_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Insert` (IN `p_Email` VARCHAR(255), IN `p_ProductId` INT, IN `p_Quantity` INT)   BEGIN
    -- Check if product already exists in cart for this user
    IF EXISTS (SELECT 1 FROM cart WHERE user_email = p_Email AND product_id = p_ProductId) THEN
        -- UPDATE existing record
        UPDATE cart 
       SET quantity = IF(quantity IS NULL, 1, quantity + p_Quantity)
		WHERE user_email = p_Email AND product_id = p_ProductId;
   
    ELSE
        -- INSERT new record
        INSERT INTO cart (user_email, product_id, quantity)
        VALUES (p_Email, p_ProductId, p_Quantity);
    END IF;
END$$

DROP PROCEDURE IF EXISTS `Cart_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Select` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT)   BEGIN
    SELECT 
        c.id as cart_id,
        c.user_email,
        c.product_id,
        c.quantity,
        c.added_at,
        p.name,
        p.price,
        p.discount,
        p.stock,
        p.image,
        p.description,
        p.brand,
        cat.category_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_email = p_user_email
    AND (p_product_id IS NULL OR c.product_id = p_product_id)
    ORDER BY c.added_at DESC;
END$$

DROP PROCEDURE IF EXISTS `Cart_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Update` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT, IN `p_quantity` INT)   BEGIN
    UPDATE cart 
    SET quantity = p_quantity 
    WHERE user_email = p_user_email 
    AND product_id = p_product_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Category_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Delete` (IN `p_id` INT)   BEGIN
    UPDATE categories
    SET status = 'Inactive'
    WHERE id = p_id;

    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Category_GetActive`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_GetActive` ()   BEGIN
    SELECT *
    FROM categories
    WHERE status = 'Active'
    ORDER BY category_name ASC;
END$$

DROP PROCEDURE IF EXISTS `Category_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Insert` (IN `p_category_name` VARCHAR(150), IN `p_status` ENUM('Active','Inactive'))   BEGIN
    INSERT INTO categories (category_name, status)
    VALUES (TRIM(p_category_name), p_status);

    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Category_Search`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Search` (IN `p_keyword` VARCHAR(150))   BEGIN
    SELECT *
    FROM categories
    WHERE 
        category_name LIKE CONCAT('%', p_keyword, '%')
        OR status LIKE CONCAT('%', p_keyword, '%')
        OR id = p_keyword;
END$$

DROP PROCEDURE IF EXISTS `Category_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Update` (IN `p_id` INT, IN `p_category_name` VARCHAR(150), IN `p_status` ENUM('Active','Inactive'))   BEGIN
    UPDATE categories
    SET 
        category_name = TRIM(p_category_name),
        status = p_status
    WHERE id = p_id;
END$$

DROP PROCEDURE IF EXISTS `CleanupExpiredTokens`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanupExpiredTokens` ()   BEGIN
    
    -- 1. Reset OTP attempts for tokens older than 24 hours
    -- (This allows users who were locked out to try again)
    UPDATE password_token 
    SET otp_attempts = 0 
    WHERE 
        TIMESTAMPDIFF(HOUR, last_resend, NOW()) >= 24;
        
    -- 2. Nullify the OTP for tokens that have expired
    -- (This makes old OTPs invalid, which is good for security)
    UPDATE password_token 
    SET otp = NULL 
    WHERE 
        expires_at < NOW();
            
END$$

DROP PROCEDURE IF EXISTS `ContactInfo_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactInfo_Select` ()   BEGIN
    SELECT * FROM contact_info LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS `ContactInfo_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactInfo_Update` (IN `p_id` INT, IN `p_company_name` VARCHAR(200), IN `p_tagline` VARCHAR(255), IN `p_email` VARCHAR(100), IN `p_phone` VARCHAR(20), IN `p_alternate_phone` VARCHAR(20), IN `p_whatsapp_number` VARCHAR(20), IN `p_address` TEXT, IN `p_city` VARCHAR(100), IN `p_state` VARCHAR(100), IN `p_country` VARCHAR(100), IN `p_postal_code` VARCHAR(20), IN `p_facebook_url` VARCHAR(255), IN `p_twitter_url` VARCHAR(255), IN `p_instagram_url` VARCHAR(255), IN `p_linkedin_url` VARCHAR(255), IN `p_youtube_url` VARCHAR(255), IN `p_map_embed_url` TEXT)   BEGIN
    UPDATE contact_info
    SET 
        company_name = p_company_name,
        tagline = p_tagline,
        email = p_email,
        phone = p_phone,
        alternate_phone = p_alternate_phone,
        whatsapp_number = p_whatsapp_number,
        address = p_address,
        city = p_city,
        state = p_state,
        country = p_country,
        postal_code = p_postal_code,
        facebook_url = p_facebook_url,
        twitter_url = p_twitter_url,
        instagram_url = p_instagram_url,
        linkedin_url = p_linkedin_url,
        youtube_url = p_youtube_url,
        map_embed_url = p_map_embed_url
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `ContactUs_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Delete` (IN `p_Id` INT)   BEGIN
    DELETE FROM contact_us
    WHERE id = p_Id;
END$$

DROP PROCEDURE IF EXISTS `ContactUs_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Insert` (IN `p_Name` VARCHAR(100), IN `p_Email` VARCHAR(100), IN `p_Subject` VARCHAR(150), IN `p_Message` TEXT)   BEGIN
    INSERT INTO contact_us (name, email, subject, message)
    VALUES (p_Name, p_Email, p_Subject, p_Message);
END$$

DROP PROCEDURE IF EXISTS `ContactUs_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Select` (IN `p_Id` INT, IN `p_Status` VARCHAR(20), IN `p_Email` VARCHAR(50))   BEGIN
    SELECT * FROM contact_us
    WHERE
        -- If p_Id is NOT NULL, find by ID
        (p_Id IS NULL OR id = p_Id)
        AND
        -- If p_Status is NOT NULL, find by status
        (p_Status IS NULL OR status = p_Status)
        AND
        -- if p_Email is not null find by email
        (p_Email is NULL or email = p_Email)
    ORDER BY 
        -- Show 'Pending' messages first
        FIELD(status, 'Pending') DESC, 
        -- Then show the newest messages first
        submitted_at DESC;
END$$

DROP PROCEDURE IF EXISTS `ContactUs_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Update` (IN `p_Id` INT, IN `p_Reply` TEXT, IN `p_Status` VARCHAR(20))   BEGIN
    UPDATE contact_us
    SET
        reply = p_Reply,
        status = p_Status,
        reply_date = NOW()
    WHERE
        id = p_Id;
END$$

DROP PROCEDURE IF EXISTS `FAQ_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `FAQ_Delete` (IN `p_id` INT)   BEGIN
    DELETE FROM faq WHERE id = p_id;
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `FAQ_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `FAQ_Insert` (IN `p_question` VARCHAR(500), IN `p_answer` TEXT, IN `p_category` VARCHAR(100), IN `p_display_order` INT, IN `p_status` ENUM('Active','Inactive'))   BEGIN
    INSERT INTO faq (question, answer, category, display_order, status)
    VALUES (p_question, p_answer, p_category, p_display_order, p_status);
    
    SELECT LAST_INSERT_ID() AS faq_id;
END$$

DROP PROCEDURE IF EXISTS `FAQ_Search`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `FAQ_Search` (IN `p_category` VARCHAR(100), IN `p_status` ENUM('Active','Inactive'))   BEGIN
    SELECT * FROM faq
    WHERE 
        (p_category IS NULL OR p_category = '' OR category = p_category)
        AND (p_status IS NULL OR p_status = '' OR status = p_status)
    ORDER BY display_order ASC, id ASC;
END$$

DROP PROCEDURE IF EXISTS `FAQ_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `FAQ_Select` (IN `p_id` INT)   BEGIN
    SELECT * FROM faq
    WHERE (p_id IS NULL OR p_id = 0 OR id = p_id)
    ORDER BY display_order ASC, id ASC;
END$$

DROP PROCEDURE IF EXISTS `FAQ_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `FAQ_Update` (IN `p_id` INT, IN `p_question` VARCHAR(500), IN `p_answer` TEXT, IN `p_category` VARCHAR(100), IN `p_display_order` INT, IN `p_status` ENUM('Active','Inactive'))   BEGIN
    UPDATE faq
    SET 
        question = COALESCE(p_question, question),
        answer = COALESCE(p_answer, answer),
        category = COALESCE(p_category, category),
        display_order = COALESCE(p_display_order, display_order),
        status = COALESCE(p_status, status)
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Offers_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Delete` (IN `p_id` INT)   BEGIN
    UPDATE offers 
    SET status = 'Inactive'
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Offers_GetActive`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_GetActive` ()   BEGIN
    SELECT * FROM offers
    WHERE status = 'Active'
        AND (valid_from IS NULL OR valid_from <= NOW())
        AND (valid_to IS NULL OR valid_to >= NOW())
    ORDER BY id DESC;
END$$

DROP PROCEDURE IF EXISTS `Offers_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Insert` (IN `p_code` VARCHAR(100), IN `p_discount_type` ENUM('percent','fixed'), IN `p_discount_value` DECIMAL(10,2), IN `p_min_order_amount` DECIMAL(10,2), IN `p_max_applicable_amount` DECIMAL(10,2), IN `p_max_discount_amount` DECIMAL(10,2), IN `p_valid_from` DATETIME, IN `p_valid_to` DATETIME, IN `p_usage_limit` INT, IN `p_per_user_limit` INT, IN `p_status` ENUM('Active','Inactive'), IN `p_description` VARCHAR(500))   BEGIN
    INSERT INTO offers (
        code,
        discount_type,
        discount_value,
        min_order_amount,
        max_applicable_amount,
        max_discount_amount,
        valid_from,
        valid_to,
        usage_limit,
        per_user_limit,
        status,
        description
    ) VALUES (
        UPPER(TRIM(p_code)),
        p_discount_type,
        p_discount_value,
        p_min_order_amount,
        p_max_applicable_amount,
        p_max_discount_amount,
        p_valid_from,
        p_valid_to,
        p_usage_limit,
        p_per_user_limit,
        p_status,
        p_description
    );
    
    SELECT LAST_INSERT_ID() AS offer_id;
END$$

DROP PROCEDURE IF EXISTS `Offers_Search`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Search` (IN `p_id` INT, IN `p_code` VARCHAR(100), IN `p_discount_type` ENUM('percent','fixed'), IN `p_status` ENUM('Active','Inactive'))   BEGIN
    SELECT * FROM offers
    WHERE
        (p_id IS NULL OR p_id = 0 OR id = p_id)
        AND (p_code IS NULL OR p_code = '' OR code LIKE CONCAT('%', p_code, '%'))
        AND (p_discount_type IS NULL OR p_discount_type = '' OR discount_type = p_discount_type)
        AND (p_status IS NULL OR p_status = '' OR status = p_status)
    ORDER BY id DESC;
END$$

DROP PROCEDURE IF EXISTS `Offers_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Select` (IN `p_id` INT)   BEGIN
    SELECT * FROM offers
    WHERE 
        -- If p_id is NULL or 0, return all offers
        -- Otherwise return specific offer
        (p_id IS NULL OR p_id = 0 OR id = p_id)
    ORDER BY id DESC;
END$$

DROP PROCEDURE IF EXISTS `Offers_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Update` (IN `p_id` INT, IN `p_code` VARCHAR(100), IN `p_discount_type` ENUM('percent','fixed'), IN `p_discount_value` DECIMAL(10,2), IN `p_min_order_amount` DECIMAL(10,2), IN `p_max_applicable_amount` DECIMAL(10,2), IN `p_max_discount_amount` DECIMAL(10,2), IN `p_valid_from` DATETIME, IN `p_valid_to` DATETIME, IN `p_usage_limit` INT, IN `p_per_user_limit` INT, IN `p_status` ENUM('Active','Inactive'), IN `p_description` VARCHAR(500))   BEGIN
    UPDATE offers SET
        code = COALESCE(UPPER(TRIM(p_code)), code),
        discount_type = COALESCE(p_discount_type, discount_type),
        discount_value = COALESCE(p_discount_value, discount_value),
        min_order_amount = COALESCE(p_min_order_amount, min_order_amount),
        max_applicable_amount = COALESCE(p_max_applicable_amount, max_applicable_amount),
        max_discount_amount = COALESCE(p_max_discount_amount, max_discount_amount),
        valid_from = COALESCE(p_valid_from, valid_from),
        valid_to = COALESCE(p_valid_to, valid_to),
        usage_limit = COALESCE(p_usage_limit, usage_limit),
        per_user_limit = COALESCE(p_per_user_limit, per_user_limit),
        status = COALESCE(p_status, status),
        description = COALESCE(p_description, description)
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Offers_ValidateCode`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_ValidateCode` (IN `p_code` VARCHAR(100))   BEGIN
    SELECT * FROM offers
    WHERE 
        UPPER(TRIM(code)) = UPPER(TRIM(p_code))
        AND status = 'Active'
        AND (valid_from IS NULL OR valid_from <= NOW())
        AND (valid_to IS NULL OR valid_to >= NOW())
    LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS `OrderItems_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `OrderItems_Insert` (IN `p_order_id` INT, IN `p_product_id` INT, IN `p_product_name` VARCHAR(255), IN `p_product_image` VARCHAR(255), IN `p_price` DECIMAL(10,2), IN `p_discount` DECIMAL(10,2), IN `p_quantity` INT, IN `p_subtotal` DECIMAL(10,2))   BEGIN
    INSERT INTO order_items (
        order_id,
        product_id,
        product_name,
        product_image,
        price,
        discount,
        quantity,
        subtotal
    ) VALUES (
        p_order_id,
        p_product_id,
        p_product_name,
        p_product_image,
        p_price,
        p_discount,
        p_quantity,
        p_subtotal
    );
    
    SELECT LAST_INSERT_ID() AS item_id;
END$$

DROP PROCEDURE IF EXISTS `OrderItems_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `OrderItems_Select` (IN `p_order_id` INT)   BEGIN
    SELECT * FROM order_items
    WHERE order_id = p_order_id
    ORDER BY id ASC;
END$$

DROP PROCEDURE IF EXISTS `Orders_Cancel`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_Cancel` (IN `p_order_id` INT, IN `p_cancellation_reason` TEXT)   BEGIN
    UPDATE orders 
    SET 
        order_status = 'Cancelled',
        cancellation_reason = p_cancellation_reason,
        cancelled_date = NOW()
    WHERE id = p_order_id
    AND order_status IN ('Pending', 'Confirmed');
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Orders_GetByOrderNumber`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_GetByOrderNumber` (IN `p_order_number` VARCHAR(50))   BEGIN
    SELECT * FROM orders
    WHERE order_number = p_order_number
    LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS `Orders_GetRecent`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_GetRecent` (IN `p_user_email` VARCHAR(255), IN `p_limit` INT)   BEGIN
    SELECT * FROM orders
    WHERE (p_user_email IS NULL OR p_user_email = '' OR user_email = p_user_email)
    ORDER BY order_date DESC
    LIMIT p_limit;
END$$

DROP PROCEDURE IF EXISTS `Orders_GetStatusCount`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_GetStatusCount` (IN `p_user_email` VARCHAR(255))   BEGIN
    SELECT 
        order_status,
        COUNT(*) as count
    FROM orders
    WHERE user_email = p_user_email
    GROUP BY order_status;
END$$

DROP PROCEDURE IF EXISTS `Orders_GetWithItems`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_GetWithItems` (IN `p_order_id` INT)   BEGIN
    -- Get order details
    SELECT * FROM orders WHERE id = p_order_id;
    
    -- Get order items
    SELECT * FROM order_items WHERE order_id = p_order_id;
END$$

DROP PROCEDURE IF EXISTS `Orders_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_Insert` (IN `p_order_number` VARCHAR(50), IN `p_user_email` VARCHAR(255), IN `p_delivery_name` VARCHAR(100), IN `p_delivery_email` VARCHAR(100), IN `p_delivery_mobile` VARCHAR(15), IN `p_delivery_address` TEXT, IN `p_subtotal` DECIMAL(10,2), IN `p_discount` DECIMAL(10,2), IN `p_shipping_fee` DECIMAL(10,2), IN `p_total_amount` DECIMAL(10,2), IN `p_payment_method` ENUM('razorpay','cod'), IN `p_razorpay_order_id` VARCHAR(100))   BEGIN
    INSERT INTO orders (
        order_number,
        user_email,
        delivery_name,
        delivery_email,
        delivery_mobile,
        delivery_address,
        subtotal,
        discount,
        shipping_fee,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        razorpay_order_id
    ) VALUES (
        p_order_number,
        p_user_email,
        p_delivery_name,
        p_delivery_email,
        p_delivery_mobile,
        p_delivery_address,
        p_subtotal,
        p_discount,
        p_shipping_fee,
        p_total_amount,
        p_payment_method,
        'Pending',
        'Pending',
        p_razorpay_order_id
    );
    
    SELECT LAST_INSERT_ID() AS order_id;
END$$

DROP PROCEDURE IF EXISTS `Orders_Search`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_Search` (IN `p_user_email` VARCHAR(255), IN `p_order_status` VARCHAR(50), IN `p_payment_status` VARCHAR(50), IN `p_payment_method` VARCHAR(50))   BEGIN
    SELECT * FROM orders
    WHERE 
        (p_user_email IS NULL OR p_user_email = '' OR user_email = p_user_email)
        AND (p_order_status IS NULL OR p_order_status = '' OR order_status = p_order_status)
        AND (p_payment_status IS NULL OR p_payment_status = '' OR payment_status = p_payment_status)
        AND (p_payment_method IS NULL OR p_payment_method = '' OR payment_method = p_payment_method)
    ORDER BY order_date DESC;
END$$

DROP PROCEDURE IF EXISTS `Orders_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_Select` (IN `p_id` INT, IN `p_user_email` VARCHAR(255), IN `p_order_number` VARCHAR(50))   BEGIN
    SELECT * FROM orders
    WHERE 
        (p_id IS NULL OR p_id = 0 OR id = p_id)
        AND (p_user_email IS NULL OR p_user_email = '' OR user_email = p_user_email)
        AND (p_order_number IS NULL OR p_order_number = '' OR order_number = p_order_number)
    ORDER BY order_date DESC;
END$$

DROP PROCEDURE IF EXISTS `Orders_UpdatePayment`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_UpdatePayment` (IN `p_order_id` INT, IN `p_payment_status` ENUM('Pending','Paid','Failed','Refunded'), IN `p_razorpay_payment_id` VARCHAR(100), IN `p_razorpay_signature` VARCHAR(255))   BEGIN
    UPDATE orders 
    SET 
        payment_status = p_payment_status,
        razorpay_payment_id = COALESCE(p_razorpay_payment_id, razorpay_payment_id),
        razorpay_signature = COALESCE(p_razorpay_signature, razorpay_signature),
        payment_date = IF(p_payment_status = 'Paid' AND payment_date IS NULL, NOW(), payment_date),
        order_status = IF(p_payment_status = 'Paid' AND order_status = 'Pending', 'Confirmed', order_status)
    WHERE id = p_order_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Orders_UpdatePaymentByOrderNumber`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_UpdatePaymentByOrderNumber` (IN `p_order_number` VARCHAR(50), IN `p_payment_status` ENUM('Pending','Paid','Failed','Refunded'), IN `p_razorpay_payment_id` VARCHAR(100), IN `p_razorpay_signature` VARCHAR(255))   BEGIN
    UPDATE orders 
    SET 
        payment_status = p_payment_status,
        razorpay_payment_id = COALESCE(p_razorpay_payment_id, razorpay_payment_id),
        razorpay_signature = COALESCE(p_razorpay_signature, razorpay_signature),
        payment_date = IF(p_payment_status = 'Paid' AND payment_date IS NULL, NOW(), payment_date),
        order_status = IF(p_payment_status = 'Paid' AND order_status = 'Pending', 'Confirmed', order_status)
    WHERE order_number = p_order_number;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Orders_UpdateStatus`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Orders_UpdateStatus` (IN `p_order_id` INT, IN `p_order_status` ENUM('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled','Returned'), IN `p_admin_notes` TEXT)   BEGIN
    UPDATE orders 
    SET 
        order_status = p_order_status,
        admin_notes = COALESCE(p_admin_notes, admin_notes),
        shipped_date = IF(p_order_status = 'Shipped' AND shipped_date IS NULL, NOW(), shipped_date),
        delivered_date = IF(p_order_status = 'Delivered' AND delivered_date IS NULL, NOW(), delivered_date),
        cancelled_date = IF(p_order_status = 'Cancelled' AND cancelled_date IS NULL, NOW(), cancelled_date)
    WHERE id = p_order_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `PasswordToken_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Delete` (IN `p_Email` VARCHAR(255))   BEGIN
    DELETE FROM password_token 
    WHERE email = p_Email;
END$$

DROP PROCEDURE IF EXISTS `PasswordToken_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Insert` (IN `p_Email` VARCHAR(255), IN `p_Otp` INT(6), IN `p_CreatedAt` DATETIME, IN `p_ExpiresAt` DATETIME, IN `p_OtpAttempts` INT(1))   BEGIN
    INSERT INTO password_token (
        email, 
        otp, 
        created_at, 
        expires_at, 
        otp_attempts, 
        last_resend
    )
    VALUES (
        p_Email, 
        p_Otp, 
        p_CreatedAt, 
        p_ExpiresAt, 
        0,          -- Set attempts to 0 for a new token
        CURRENT_TIMESTAMP
    );
END$$

DROP PROCEDURE IF EXISTS `PasswordToken_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Select` (IN `p_Email` VARCHAR(255))   BEGIN
    -- This just selects the data. No more update logic.
    SELECT * FROM password_token 
    WHERE email = p_Email;
    
END$$

DROP PROCEDURE IF EXISTS `PasswordToken_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Update` (IN `p_Email` VARCHAR(255), IN `p_Otp` INT(6), IN `p_CreatedAt` DATETIME, IN `p_ExpiresAt` DATETIME, IN `P_Otp_attempts` INT(1))   BEGIN
    UPDATE password_token
    SET
        otp = p_Otp,
        created_at = p_CreatedAt,
        expires_at = p_ExpiresAt,
        otp_attempts = P_Otp_attempts,
        last_resend = CURRENT_TIMESTAMP
    WHERE
        email = p_Email;
END$$

DROP PROCEDURE IF EXISTS `Products_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Delete` (IN `p_id` INT)   BEGIN
    UPDATE products SET
        status = 'Deleted'
    WHERE id = p_id;
    
END$$

DROP PROCEDURE IF EXISTS `Products_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Insert` (IN `p_name` VARCHAR(255), IN `p_category_id` INT, IN `p_brand` VARCHAR(100), IN `p_price` DECIMAL(10,2), IN `p_discount` DECIMAL(10,2), IN `p_stock` INT, IN `p_description` TEXT, IN `p_long_description` LONGTEXT, IN `p_image` VARCHAR(255), IN `p_gallery_images` TEXT, IN `p_status` ENUM('Active','Inactive'))   BEGIN
    INSERT INTO products (
        name, category_id, brand, price, discount, stock, 
        description, long_description, image, gallery_images, status
    ) VALUES (
        p_name, p_category_id, p_brand, p_price, p_discount, p_stock,
        p_description, p_long_description, p_image, p_gallery_images, p_status
    );
    
    SELECT LAST_INSERT_ID() AS product_id;
END$$

DROP PROCEDURE IF EXISTS `Products_Search`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Search` (IN `p_id` INT, IN `p_name` VARCHAR(255), IN `p_category_id` INT, IN `p_brand` VARCHAR(100), IN `p_min_price` DECIMAL(10,2), IN `p_max_price` DECIMAL(10,2), IN `p_status` ENUM('Active','Inactive'), IN `p_min_stock` INT)   BEGIN
    SELECT 
        p.*,
        c.category_name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 
        (p.id = p_id OR p_id IS NULL OR p_id = 0)
        AND (p.name LIKE CONCAT('%', p_name, '%') OR p_name IS NULL OR p_name = '')
        AND (p.category_id = p_category_id OR p_category_id IS NULL OR p_category_id = 0)
        AND (p.brand LIKE CONCAT('%', p_brand, '%') OR p_brand IS NULL OR p_brand = '')
        AND (p.final_price >= p_min_price OR p_min_price IS NULL)
        AND (p.final_price <= p_max_price OR p_max_price IS NULL)
        AND (p.status = p_status OR p_status IS NULL OR p_status = '')
        AND (p.stock >= p_min_stock OR p_min_stock IS NULL)
    ORDER BY p.id DESC;
END$$

DROP PROCEDURE IF EXISTS `Products_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Select` (IN `p_id` INT)   BEGIN
  SELECT 
        p.*, 
        c.category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 
        -- This is the "Smart Filter" logic:
        -- 1. If parameter is NULL, returns TRUE for all rows.
        -- 2. If parameter is 0, returns TRUE for all rows.
        -- 3. Otherwise, it only returns the specific ID.
        (p_id IS NULL OR p_id = 0 OR p.id = p_id)
    ORDER BY p.id DESC;
END$$

DROP PROCEDURE IF EXISTS `Products_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Update` (IN `p_id` INT, IN `p_name` VARCHAR(255), IN `p_category_id` INT, IN `p_brand` VARCHAR(100), IN `p_price` DECIMAL(10,2), IN `p_discount` DECIMAL(10,2), IN `p_stock` INT, IN `p_description` TEXT, IN `p_long_description` LONGTEXT, IN `p_image` VARCHAR(255), IN `p_gallery_images` JSON, IN `p_status` ENUM('Active','Inactive'))   BEGIN
  UPDATE products SET
        name = COALESCE(p_name, name),
        category_id = COALESCE(p_category_id, category_id),
        brand = COALESCE(p_brand, brand),
        price = COALESCE(p_price, price),
        discount = COALESCE(p_discount, discount),
        stock = COALESCE(p_stock, stock),
        description = COALESCE(p_description, description),
        long_description = COALESCE(p_long_description, long_description),
        image = COALESCE(p_image, image),
        gallery_images = COALESCE(p_gallery_images, gallery_images),
        status = COALESCE(p_status, status)
    WHERE id = p_id;
   
END$$

DROP PROCEDURE IF EXISTS `Registration_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Delete` (IN `p_Email` VARCHAR(255))   BEGIN
    -- Soft delete user
    UPDATE registration
    SET status = 'Deleted'
    WHERE TRIM(LOWER(email)) = TRIM(LOWER(p_Email));

    -- Return how many rows were updated
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Registration_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Insert` (IN `p_Fullname` VARCHAR(255), IN `p_Email` VARCHAR(255), IN `p_Password` VARCHAR(255), IN `p_MobileNumber` BIGINT, IN `p_Gender` VARCHAR(50), IN `p_ProfilePicture` VARCHAR(255), IN `p_Address` VARCHAR(50), IN `p_Token` TEXT, IN `p_Role` VARCHAR(25), IN `p_Status` VARCHAR(25))   BEGIN
    INSERT INTO registration (
        fullname,
        email,
        password,
        mobile,
        gender,
        profile_picture,
        address,
        token,
        role,
        status
    )
    VALUES (
        p_Fullname,
        p_Email,
        p_Password,
        p_MobileNumber,
        p_Gender,
        p_ProfilePicture,
        p_Address,
        p_Token,
        P_role,
        P_status
    );
    
END$$

DROP PROCEDURE IF EXISTS `Registration_Search`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Search` (IN `p_Search` VARCHAR(255))   BEGIN
    -- If p_Search is NULL or empty -> match everything.
    -- Otherwise search all four columns using partial match.
    SELECT *
    FROM registration
    WHERE
      (p_Search IS NULL OR p_Search = ''
        OR email   COLLATE utf8mb4_0900_ai_ci   LIKE CONCAT('%', p_Search COLLATE utf8mb4_0900_ai_ci, '%')
        OR role    COLLATE utf8mb4_0900_ai_ci   LIKE CONCAT('%', p_Search COLLATE utf8mb4_0900_ai_ci, '%')
        OR CAST(mobile AS CHAR) COLLATE utf8mb4_0900_ai_ci LIKE CONCAT('%', p_Search COLLATE utf8mb4_0900_ai_ci, '%')
        OR status  COLLATE utf8mb4_0900_ai_ci   LIKE CONCAT('%', p_Search COLLATE utf8mb4_0900_ai_ci, '%')
      );
END$$

DROP PROCEDURE IF EXISTS `Registration_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Select` (IN `p_Fullname` VARCHAR(255), IN `p_Email` VARCHAR(255), IN `p_Role` VARCHAR(50), IN `p_Status` VARCHAR(50), IN `p_Password` VARCHAR(50), IN `p_Token` TEXT, IN `p_Mobile` BIGINT(10))   BEGIN
    SELECT * FROM registration
    WHERE
        -- For each parameter, we check if it's NULL or empty.
        -- If it is, the (TRUE OR ...) part makes the condition pass.
        -- If it's NOT, it filters by the value: (FALSE OR column = value)
        
        ( (p_Fullname IS NULL OR p_Fullname = '') OR fullname = p_Fullname )
        
        AND
        
        ( (p_Email IS NULL OR p_Email = '') OR email = p_Email )
        
        AND
        
        ( (p_Role IS NULL OR p_Role = '') OR role = p_Role )
        
        AND
        
        ( (p_Status IS NULL OR p_Status = '') OR status = p_Status )
        
        AND
        
        ( (p_Password IS NULL OR p_Password = '') OR password = p_Password)
        
        AND
        
        ( (p_Token IS NULL OR p_Token = '') OR token = p_Token)
        
        AND
        
        ( (p_Mobile IS NULL OR p_Mobile = '') OR mobile=p_Mobile);

END$$

DROP PROCEDURE IF EXISTS `Registration_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Update` (IN `p_Fullname` VARCHAR(255), IN `p_Email` VARCHAR(255), IN `p_Password` VARCHAR(255), IN `p_MobileNumber` BIGINT, IN `p_Gender` CHAR(52), IN `p_ProfilePicture` VARCHAR(255), IN `p_Address` TEXT, IN `p_Status` VARCHAR(50), IN `p_Role` VARCHAR(50))   BEGIN
    UPDATE registration
    SET
        fullname = IFNULL(p_Fullname, fullname),
        password = IFNULL(p_Password, password),
        role = IFNULL(p_Role, role),
        status = IFNULL(p_Status, status),
        -- Added new fields
        mobile = IFNULL(p_MobileNumber, mobile),
        profile_picture = IFNULL(p_ProfilePicture, profile_picture),
        gender = IFNULL(p_Gender,gender),
        address = IFNULL(p_Address,address)
    WHERE
        email = p_Email;
END$$

DROP PROCEDURE IF EXISTS `Reviews_CheckCanReview`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Reviews_CheckCanReview` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT)   BEGIN
    -- Check if user has purchased this product
    SELECT COUNT(*) as can_review
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE o.user_email = p_user_email
    AND oi.product_id = p_product_id
    AND o.order_status = 'Delivered';
    
    -- Check if already reviewed
    SELECT COUNT(*) as already_reviewed
    FROM reviews
    WHERE user_email = p_user_email
    AND product_id = p_product_id;
END$$

DROP PROCEDURE IF EXISTS `Reviews_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Reviews_Delete` (IN `p_review_id` INT, IN `p_user_email` VARCHAR(255))   BEGIN
    DELETE FROM reviews
    WHERE id = p_review_id
    AND user_email = p_user_email;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Reviews_GetProductRating`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Reviews_GetProductRating` (IN `p_product_id` INT)   BEGIN
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as average_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
    FROM reviews
    WHERE product_id = p_product_id
    AND status = 'Approved';
END$$

DROP PROCEDURE IF EXISTS `Reviews_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Reviews_Insert` (IN `p_product_id` INT, IN `p_user_email` VARCHAR(255), IN `p_user_name` VARCHAR(100), IN `p_rating` INT, IN `p_title` VARCHAR(200), IN `p_review` TEXT)   BEGIN
    INSERT INTO reviews (
        product_id,
        user_email,
        user_name,
        rating,
        title,
        review,
        status
    ) VALUES (
        p_product_id,
        p_user_email,
        p_user_name,
        p_rating,
        p_title,
        p_review,
        'Pending'
    );
    
    SELECT LAST_INSERT_ID() AS review_id;
END$$

DROP PROCEDURE IF EXISTS `Reviews_SelectByProduct`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Reviews_SelectByProduct` (IN `p_product_id` INT, IN `p_status` VARCHAR(50))   BEGIN
    SELECT * FROM reviews
    WHERE product_id = p_product_id
    AND (p_status IS NULL OR p_status = '' OR status = p_status)
    ORDER BY created_at DESC;
END$$

DROP PROCEDURE IF EXISTS `Reviews_SelectByUser`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Reviews_SelectByUser` (IN `p_user_email` VARCHAR(255))   BEGIN
    SELECT r.*, p.name as product_name, p.image as product_image
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    WHERE r.user_email = p_user_email
    ORDER BY r.created_at DESC;
END$$

DROP PROCEDURE IF EXISTS `Reviews_UpdateStatus`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Reviews_UpdateStatus` (IN `p_review_id` INT, IN `p_status` ENUM('Pending','Approved','Rejected'))   BEGIN
    UPDATE reviews
    SET status = p_status
    WHERE id = p_review_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `SitePages_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `SitePages_Select` (IN `p_page_slug` VARCHAR(50))   BEGIN
    SELECT * FROM site_pages
    WHERE (p_page_slug IS NULL OR p_page_slug = '' OR page_slug = p_page_slug)
    ORDER BY page_slug;
END$$

DROP PROCEDURE IF EXISTS `SitePages_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `SitePages_Update` (IN `p_id` INT, IN `p_page_title` VARCHAR(200), IN `p_page_content` LONGTEXT, IN `p_status` ENUM('Active','Inactive'), IN `p_updated_by` VARCHAR(100))   BEGIN
    UPDATE site_pages
    SET 
        page_title = p_page_title,
        page_content = p_page_content,
        status = p_status,
        updated_by = p_updated_by
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `TeamMembers_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `TeamMembers_Delete` (IN `p_id` INT)   BEGIN
    DELETE FROM team_members WHERE id = p_id;
END$$

DROP PROCEDURE IF EXISTS `TeamMembers_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `TeamMembers_Insert` (IN `p_name` VARCHAR(100), IN `p_designation` VARCHAR(100), IN `p_photo` VARCHAR(255), IN `p_bio` TEXT, IN `p_facebook_url` VARCHAR(255), IN `p_twitter_url` VARCHAR(255), IN `p_linkedin_url` VARCHAR(255), IN `p_display_order` INT, IN `p_status` ENUM('Active','Inactive'))   BEGIN
    INSERT INTO team_members (
        name, designation, photo, bio, 
        facebook_url, twitter_url, linkedin_url,
        display_order, status
    ) VALUES (
        p_name, p_designation, p_photo, p_bio,
        p_facebook_url, p_twitter_url, p_linkedin_url,
        p_display_order, p_status
    );
    
    SELECT LAST_INSERT_ID() AS team_id;
END$$

DROP PROCEDURE IF EXISTS `TeamMembers_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `TeamMembers_Select` (IN `p_id` INT)   BEGIN
    SELECT * FROM team_members
    WHERE (p_id IS NULL OR p_id = 0 OR id = p_id)
    ORDER BY display_order ASC, id ASC;
END$$

DROP PROCEDURE IF EXISTS `TeamMembers_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `TeamMembers_Update` (IN `p_id` INT, IN `p_name` VARCHAR(100), IN `p_designation` VARCHAR(100), IN `p_photo` VARCHAR(255), IN `p_bio` TEXT, IN `p_facebook_url` VARCHAR(255), IN `p_twitter_url` VARCHAR(255), IN `p_linkedin_url` VARCHAR(255), IN `p_display_order` INT, IN `p_status` ENUM('Active','Inactive'))   BEGIN
    UPDATE team_members
    SET 
        name = COALESCE(p_name, name),
        designation = COALESCE(p_designation, designation),
        photo = COALESCE(p_photo, photo),
        bio = COALESCE(p_bio, bio),
        facebook_url = COALESCE(p_facebook_url, facebook_url),
        twitter_url = COALESCE(p_twitter_url, twitter_url),
        linkedin_url = COALESCE(p_linkedin_url, linkedin_url),
        display_order = COALESCE(p_display_order, display_order),
        status = COALESCE(p_status, status)
    WHERE id = p_id;
    
  
END$$

DROP PROCEDURE IF EXISTS `Wishlist_CheckExists`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Wishlist_CheckExists` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT)   BEGIN
    SELECT COUNT(*) as exists_count
    FROM wishlist
    WHERE user_email = p_user_email AND product_id = p_product_id;
END$$

DROP PROCEDURE IF EXISTS `Wishlist_Delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Wishlist_Delete` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT)   BEGIN
    DELETE FROM wishlist 
    WHERE user_email = p_user_email 
    AND product_id = p_product_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Wishlist_Empty`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Wishlist_Empty` (IN `p_user_email` VARCHAR(255))   BEGIN
    DELETE FROM wishlist WHERE user_email = p_user_email;
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Wishlist_Insert`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Wishlist_Insert` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT)   BEGIN
    -- Insert only if not already in wishlist (unique constraint handles this)
    INSERT IGNORE INTO wishlist (user_email, product_id)
    VALUES (p_user_email, p_product_id);
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

DROP PROCEDURE IF EXISTS `Wishlist_Select`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Wishlist_Select` (IN `p_user_email` VARCHAR(255), IN `p_product_id` INT)   BEGIN
    SELECT 
        w.id as wishlist_id,
        w.user_email,
        w.product_id,
        w.added_at,
        p.name,
        p.price,
        p.discount,
        p.stock,
        p.image,
        p.description,
        p.brand,
        p.status,
        cat.category_name
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE w.user_email = p_user_email
    AND (p_product_id IS NULL OR w.product_id = p_product_id)
    ORDER BY w.added_at DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `id` int NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `name`, `email`, `mobile`, `address`, `created_at`, `updated_at`) VALUES
(2, 'varunpatel35@gmail.com', 'Varun Patel', 'varunpatel35@gmail.com', '7575857575', 'Rajkot', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(3, 'varunpatel35@gmail.com', 'Varun Patel', 'varunpatel35@gmail.com', '7575857', 'Rajkot', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(8, 'jankikansagra12@gmail.com', 'Janki', 'janki.kansagra@rku.ac.in', '4785692130', 'Rajkot\r\n', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(9, 'denish25@gmail.com', 'Denish Rameshbhai Faldu', 'denishfaldu25@gmail.com', '738365448', 'Bpa sitaram chown mavdi', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(10, 'jankikansagra12@gmail.com', 'Janki Kansagra', 'pratyushf31@northstar.edu.in', '8155825235', 'Bapa sitaram chowk\r\nMavdi gam', '2025-12-06 08:37:55', '2025-12-06 08:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_email`, `product_id`, `quantity`, `added_at`, `created_at`, `updated_at`) VALUES
(1, 'denish25@gmail.com', 1, 3, '2025-11-28 12:59:20', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(2, 'janki.kansagra@rku.ac.in', 1, 1, '2025-11-28 12:59:44', '2025-12-06 08:37:55', '2025-12-08 02:54:16');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `status` enum('Active','Inactive','Deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Clothes', 'Deleted', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(2, 'Mobile', 'Active', '2025-12-06 08:37:55', '2025-12-06 08:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `contact_info`
--

DROP TABLE IF EXISTS `contact_info`;
CREATE TABLE `contact_info` (
  `id` int NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `map_embed_url` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_info`
--

INSERT INTO `contact_info` (`id`, `company_name`, `tagline`, `email`, `phone`, `alternate_phone`, `whatsapp_number`, `address`, `city`, `state`, `country`, `postal_code`, `facebook_url`, `twitter_url`, `instagram_url`, `linkedin_url`, `youtube_url`, `map_embed_url`, `updated_at`) VALUES
(1, 'MobileStore', 'Your Trusted Mobile Partner', 'support@mobilestore.com', '+91 8485868987', '+91 98765 43211', '+919876543210', 'Tech City, Main Road', 'Mumbai', 'Maharashtra', 'India', '400001', 'https://facebook.com/mobilestore', 'https://twitter.com/mobilestore', 'https://instagram.com/mobilestore', 'https://linkedin.com/company/mobilestore', 'https://youtube.com/mobilestore', NULL, '2025-12-06 08:10:26');

-- --------------------------------------------------------

--
-- Table structure for table `contact_us`
--

DROP TABLE IF EXISTS `contact_us`;
CREATE TABLE `contact_us` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `reply` text,
  `status` varchar(20) DEFAULT 'Pending',
  `reply_date` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_us`
--

INSERT INTO `contact_us` (`id`, `name`, `email`, `subject`, `message`, `reply`, `status`, `reply_date`, `submitted_at`) VALUES
(1, 'Janki', 'kansagrajanki@gmail.com', 'Login Error', 'Error in login', 'hello your query is solved', 'Replied', '2025-11-29 16:50:20', '2025-10-06 03:17:37');

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
CREATE TABLE `faq` (
  `id` int NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `display_order` int DEFAULT '0',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id`, `question`, `answer`, `category`, `display_order`, `status`, `created_at`) VALUES
(1, 'What payment methods do you accept?', 'We accept Credit Cards, Debit Cards, Net Banking, UPI, and Cash on Delivery.', 'Payment', 1, 'Active', '2025-11-29 17:28:23'),
(2, 'How long does delivery take?', 'Standard delivery takes 3-5 business days. Express delivery is available in 1-2 days.', 'Shipping', 2, 'Active', '2025-11-29 17:28:23'),
(3, 'What is your return policy?', 'We offer a 7-day return policy for all products. Items must be in original condition with all accessories.', 'Returns', 3, 'Active', '2025-11-29 17:28:23'),
(4, 'Do you provide warranty on products?', 'Yes, all products come with manufacturer warranty. Extended warranty options are also available.', 'Warranty', 4, 'Active', '2025-11-29 17:28:23'),
(5, 'How can I track my order?', 'You will receive a tracking link via email and SMS once your order is shipped.', 'Shipping', 5, 'Active', '2025-11-29 17:28:23');

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `id` int NOT NULL,
  `code` varchar(100) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_applicable_amount` decimal(10,2) DEFAULT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `times_used` int NOT NULL DEFAULT '0',
  `per_user_limit` int DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `offers`
--

INSERT INTO `offers` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `max_applicable_amount`, `max_discount_amount`, `valid_from`, `valid_to`, `usage_limit`, `times_used`, `per_user_limit`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 'PRATYUSH20', 'percent', 20.00, 1000.00, 5000.00, 200.00, '2025-11-29 22:37:00', '2025-12-16 22:37:00', 50, 0, 2, 'Active', 'offer discount 20 percent', '2025-12-06 08:37:55', '2025-12-06 08:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `delivery_name` varchar(100) NOT NULL,
  `delivery_email` varchar(100) NOT NULL,
  `delivery_mobile` varchar(15) NOT NULL,
  `delivery_address` text NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('razorpay','cod') NOT NULL DEFAULT 'cod',
  `payment_status` enum('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `order_status` enum('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled','Returned') NOT NULL DEFAULT 'Pending',
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_date` timestamp NULL DEFAULT NULL,
  `shipped_date` timestamp NULL DEFAULT NULL,
  `delivered_date` timestamp NULL DEFAULT NULL,
  `cancelled_date` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text,
  `admin_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_email`, `delivery_name`, `delivery_email`, `delivery_mobile`, `delivery_address`, `subtotal`, `discount`, `shipping_fee`, `total_amount`, `payment_method`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `order_status`, `order_date`, `payment_date`, `shipped_date`, `delivered_date`, `cancelled_date`, `cancellation_reason`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 'ORD-693293C07243A-20251205', 'jankikansagra12@gmail.com', 'Janki Kansagra', 'pratyushf31@northstar.edu.in', '8155825235', 'Bapa sitaram chowk\r\nMavdi gam', 21000.00, 1050.00, 0.00, 19950.00, 'cod', 'Pending', NULL, NULL, NULL, 'Shipped', '2025-12-05 08:11:44', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-06 08:37:55', '2025-12-06 09:18:54'),
(2, 'ORD-6932947CC2087-20251205', 'jankikansagra12@gmail.com', 'Janki Kansagra', 'pratyushf31@northstar.edu.in', '8155825235', 'Bapa sitaram chowk\r\nMavdi gam', 21000.00, 1050.00, 0.00, 19950.00, 'cod', 'Pending', NULL, NULL, NULL, 'Pending', '2025-12-05 08:14:52', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-06 08:37:55', '2025-12-06 08:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_image`, `price`, `discount`, `quantity`, `subtotal`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Samsung A17', '1764333566_programming-language-stacked-cubes-o6ete8w4aqet8mv7.jpg', 21000.00, 5.00, 1, 19950.00, '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(2, 2, 1, 'Samsung A17', '1764333566_programming-language-stacked-cubes-o6ete8w4aqet8mv7.jpg', 21000.00, 5.00, 1, 19950.00, '2025-12-06 08:37:55', '2025-12-06 08:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `password_token`
--

DROP TABLE IF EXISTS `password_token`;
CREATE TABLE `password_token` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `otp_attempts` int NOT NULL,
  `last_resend` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_token`
--

INSERT INTO `password_token` (`id`, `email`, `otp`, `created_at`, `expires_at`, `otp_attempts`, `last_resend`) VALUES
(23, 'kansagrajanki@gmail.com', NULL, '2025-11-17 22:18:39', '2025-11-17 22:20:39', 0, '2025-11-17 16:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `final_price` decimal(10,2) GENERATED ALWAYS AS ((`price` - `discount`)) STORED,
  `stock` int DEFAULT '0',
  `description` text,
  `long_description` longtext,
  `image` varchar(255) DEFAULT NULL,
  `gallery_images` json DEFAULT NULL,
  `status` enum('Active','Inactive','Deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `brand`, `price`, `discount`, `stock`, `description`, `long_description`, `image`, `gallery_images`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Samsung A17', 2, 'Samsung', 21000.00, 5.00, 20, '', '8 GB RAM', '1764333566_programming-language-stacked-cubes-o6ete8w4aqet8mv7.jpg', '[\"images/products/gallery/1764213379_0_programming-hd-2eo94s73hxhwjcta.jpg\", \"images/products/gallery/1764213379_1_a-children-learning-coding-or-computer-programming-flat-illustration-coding-for-kids-basic-computer-programing-can-be-used-for-web-landing-page-social-media-promotion-etc-vector.jpg\", \"images/products/gallery/1764213379_2_programming-languages-1.avif\", \"images/products/gallery/1764213379_3_Computer-Programming-Language.jpg\", \"images/products/gallery/1764213379_4_Brief-History-of-Programming-Languages.jpg\", \"images/products/gallery/1764213379_5_Mixed-languages.jpg\"]', 'Active', '2025-12-06 08:37:55', '2025-12-06 08:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

DROP TABLE IF EXISTS `registration`;
CREATE TABLE `registration` (
  `id` int NOT NULL,
  `fullname` char(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `gender` char(6) DEFAULT NULL,
  `mobile` bigint DEFAULT NULL,
  `profile_picture` varchar(100) DEFAULT NULL,
  `address` text,
  `status` char(8) DEFAULT 'Inactive',
  `role` char(10) DEFAULT 'User',
  `token` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `fullname`, `email`, `password`, `gender`, `mobile`, `profile_picture`, `address`, `status`, `role`, `token`, `created_at`, `updated_at`) VALUES
(1, 'Janki Kansagra', 'janki.kansagra@rku.ac.in', 'Janki@12345', 'Female', 8523697410, '693642f3ce718_1765163763.png', 'Rajkot', 'Active', 'User', '68c7d44a15286c1489ffc0db8b0b5ad6e9e3bfcfaef4b591b978cb21f07d85a6789b5022f5cf5498453552463e53ccb102b7', '2025-12-06 08:37:55', '2025-12-08 03:16:03'),
(4, 'Janki Faldu', 'jankikansagra12@gmail.com', 'Janki@123456', 'Male', 8155825235, '6918882b0a30escreencapture-file-D-ravi-JP-test-html-2025-11-14-23_57_52.png', '                                                Rajkot                                                                                                                               ', 'Active', 'User', 'c21ef5ea11056ebe8c9b52955f87d343f9a3c2db6c8d5803e893496c6a0b005c64910183184a463185ef5bf98aa2747235cf', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(7, 'Rajesh', 'rghumaliya777@rku.ac.in', 'Rajesh@1234', 'Male', 2587413690, '68d4d5284dc6ax-xml.png', 'Rajkot', 'Active', 'User', 'fd4a6875ae5d56c8bca1b4e1f41688460cd6f614bb281544a8c0dcc5ef0f57e1f2dd3d6df95c5dd0cd568a75af67a2c3a75d', '2025-12-06 08:37:55', '2025-12-06 09:19:26'),
(10, 'gopi', 'gvyas950@rku.ac.in', 'demo@1234', 'Female', 8525825822, '68d4d74ab8f3av-vue.jpeg', 'rajkot', 'Active', 'User', '800b71c0648e84ed7910133eae44b0271f6371aa6794acdb772589285e787d0914d4ec518948bf2e100556d6e9645ba9a548', '2025-12-06 08:37:55', '2025-12-06 09:19:23'),
(11, 'Varun Patel', 'varunpatel35@gmail.com', 'Varun@12345', 'Male', 5865983212, '68e87c5481cccg-git.png', '                            rajkot', 'Active', 'User', '975a0525f13d8639ce989a19ec088b92198310b68f653f15ab67e582c3a6bd3c44673ec7c0dc3bba81832b529e1cceb874f0', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(14, 'Pratyush', 'pratyushf31@northstar.edu.in', 'Pratyush@1234', 'Male', 4845845845, 'admin_6936426dba273.png', 'Bapa sitaram chowk\r\nMavdi gam', 'Active', 'Admin', '35c01aa9a6615e79fcbf1c96', '2025-12-06 08:37:55', '2025-12-08 03:13:49'),
(16, 'Denish', 'denish25@gmail.com', 'Denish@1234', 'Male', 8155825234, 'default.png', 'Bapa sitaram chowk\r\nMavdi gam', 'Active', 'User', 'c3c0a8f6a5d87cd3b649f21ca2b0966b', '2025-12-06 08:37:55', '2025-12-06 08:37:55'),
(17, 'Janki Kansagra', 'janki345@gmail.com', 'JAnki@12345', 'Female', 1234567890, 'default.png', 'Bapa sitaram chowk\r\nMavdi gam', 'Active', 'User', '039dafa20c2ec59e3619df533bcd9c9f', '2025-12-06 08:37:55', '2025-12-06 08:37:55');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `rating` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `review` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_email`, `user_name`, `rating`, `title`, `review`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'jankikansagra12@gmail.com', 'Janki Faldu', 5, 'good', 'excellent excellent', 'Approved', '2025-12-06 03:31:23', '2025-12-06 03:31:52');

-- --------------------------------------------------------

--
-- Table structure for table `site_pages`
--

DROP TABLE IF EXISTS `site_pages`;
CREATE TABLE `site_pages` (
  `id` int NOT NULL,
  `page_slug` varchar(50) NOT NULL,
  `page_title` varchar(200) NOT NULL,
  `page_content` longtext NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `site_pages`
--

INSERT INTO `site_pages` (`id`, `page_slug`, `page_title`, `page_content`, `status`, `updated_at`, `updated_by`) VALUES
(1, 'about', 'About Us', '<p>MobileStore is a leading retailer of premium smartphones, committed to bringing the latest technology to our customers. We offer a wide range of devices, accessories, and exclusive offers, ensuring the best shopping experience for mobile enthusiasts.</p><p>Founded in 2020, our mission is to combine quality, affordability, and exceptional customer service under one roof. Whether you\'re looking for the newest flagship model or a budget-friendly smartphone, we\'ve got you covered.</p>', 'Active', '2025-11-29 17:28:23', NULL),
(2, 'privacy', 'Privacy Policy', '<h3>1. Information We Collect</h3><p>We collect information that you provide directly to us...</p><h3>2. How We Use Your Information</h3><p>We use your information to process orders and improve our services...</p>', 'Active', '2025-11-29 17:28:23', NULL),
(3, 'terms', 'Terms & Conditions', '<h3>1. Acceptance of Terms</h3><p>By accessing our website, you agree to these terms...</p>', 'Active', '2025-11-29 17:28:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

DROP TABLE IF EXISTS `team_members`;
CREATE TABLE `team_members` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT 'images/team/default.jpg',
  `bio` text,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `display_order` int DEFAULT '0',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`, `designation`, `photo`, `bio`, `facebook_url`, `twitter_url`, `linkedin_url`, `display_order`, `status`) VALUES
(1, 'John Doe', 'Founder & CEO', 'images/team/1764438594_692b3242bc3f5.jpg', 'Visionary leader with 15 years of experience in mobile retail industry.', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>381</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>387</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>393</b><br />', 1, 'Active'),
(2, 'Jane Smith', 'Head of Operations', 'images/team/1764438603_692b324b77994.jpg', 'Expert in supply chain management and logistics.', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>381</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>387</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>393</b><br />', 2, 'Active'),
(3, 'Mike Johnson', 'Marketing Lead', 'images/team/1764438617_692b32591e525.png', 'Creative marketing strategist driving brand growth.', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>381</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>387</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>393</b><br />', 3, 'Active'),
(4, 'Sarah Wilson', 'Tech Head', 'images/team/1764438624_692b326013006.png', 'Technology enthusiast ensuring best product selection.', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>381</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>387</b><br />', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>D:\\laragon\\www\\MCA_Project_2025\\admin_team.php</b> on line <b>393</b><br />', 4, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
  `id` int NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `product_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_email`, `product_id`, `added_at`, `created_at`, `updated_at`) VALUES
(1, 'denish25@gmail.com', 1, '2025-11-28 13:10:00', '2025-12-06 08:37:55', '2025-12-06 08:37:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_info`
--
ALTER TABLE `contact_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_us`
--
ALTER TABLE `contact_us`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user_email` (`user_email`),
  ADD KEY `idx_order_status` (`order_status`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_token`
--
ALTER TABLE `password_token`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `mobile` (`mobile`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_email` (`user_email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `site_pages`
--
ALTER TABLE `site_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_slug` (`page_slug`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item` (`user_email`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_info`
--
ALTER TABLE `contact_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_us`
--
ALTER TABLE `contact_us`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_token`
--
ALTER TABLE `password_token`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_pages`
--
ALTER TABLE `site_pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `registration` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `registration` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `registration` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
