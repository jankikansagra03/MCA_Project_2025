-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 04, 2025 at 05:37 AM
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
CREATE DATABASE IF NOT EXISTS `mca_2025` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `mca_2025`;

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
    INSERT INTO addresses (
        user_id, 
        name, 
        email, 
        mobile, 
        address
    )
    VALUES (
        p_user_id, 
        p_name, 
        p_email, 
        p_mobile, 
        p_address
    );
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Delete` (IN `p_CartId` INT)   BEGIN
    DELETE FROM cart WHERE id = p_CartId;
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Select` (IN `p_Email` VARCHAR(255))   BEGIN
    SELECT 
        c.id as cart_id,
        c.product_id,
        c.quantity,
        p.name as product_name,  -- Assumes products table has 'name'
        p.price,                 -- Assumes products table has 'price'
        p.image                  -- Assumes products table has 'image'
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_email = p_Email;
END$$

DROP PROCEDURE IF EXISTS `Cart_Update`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Update` (IN `p_CartId` INT, IN `p_Quantity` INT)   BEGIN
    UPDATE cart 
    SET quantity = p_Quantity 
    WHERE id = p_CartId;
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `Wishlist_Delete` (IN `p_id` INT)   BEGIN
    DELETE FROM wishlist WHERE id = p_id;
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `Wishlist_Select` (IN `p_user_email` VARCHAR(255))   BEGIN
    SELECT 
        w.id as wishlist_id,
        w.user_email,
        w.product_id,
        w.added_at,
        p.name as product_name,
        p.price,
        p.final_price,
        p.image,
        p.status as product_status
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE 
        (p_user_email IS NULL OR w.user_email = p_user_email)
    ORDER BY w.added_at DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `name`, `email`, `mobile`, `address`) VALUES
(2, 'varunpatel35@gmail.com', 'Varun Patel', 'varunpatel35@gmail.com', '7575857575', 'Rajkot'),
(3, 'varunpatel35@gmail.com', 'Varun Patel', 'varunpatel35@gmail.com', '7575857', 'Rajkot'),
(8, 'jankikansagra12@gmail.com', 'Janki', 'janki.kansagra@rku.ac.in', '4785692130', 'Rajkot\r\n'),
(9, 'denish25@gmail.com', 'Denish Rameshbhai Faldu', 'denishfaldu25@gmail.com', '738365448', 'Bpa sitaram chown mavdi');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_email` (`user_email`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_email`, `product_id`, `quantity`, `added_at`) VALUES
(1, 'denish25@gmail.com', 1, 3, '2025-11-28 12:59:20'),
(2, 'janki.kansagra@rku.ac.in', 1, 5, '2025-11-28 12:59:44'),
(4, 'jankikansagra12@gmail.com', 1, 1, '2025-12-01 17:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(150) NOT NULL,
  `status` enum('Active','Inactive','Deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `status`) VALUES
(1, 'Clothes', 'Deleted'),
(2, 'Mobile', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `contact_info`
--

DROP TABLE IF EXISTS `contact_info`;
CREATE TABLE IF NOT EXISTS `contact_info` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_info`
--

INSERT INTO `contact_info` (`id`, `company_name`, `tagline`, `email`, `phone`, `alternate_phone`, `whatsapp_number`, `address`, `city`, `state`, `country`, `postal_code`, `facebook_url`, `twitter_url`, `instagram_url`, `linkedin_url`, `youtube_url`, `map_embed_url`, `updated_at`) VALUES
(1, 'MobileStore', 'Your Trusted Mobile Partner', 'support@mobilestore.com', '+91 98765 43223', '+91 98765 43211', '+919876543210', 'Tech City, Main Road', 'Mumbai', 'Maharashtra', 'India', '400001', 'https://facebook.com/mobilestore', 'https://twitter.com/mobilestore', 'https://instagram.com/mobilestore', 'https://linkedin.com/company/mobilestore', 'https://youtube.com/mobilestore', NULL, '2025-12-01 16:38:23');

-- --------------------------------------------------------

--
-- Table structure for table `contact_us`
--

DROP TABLE IF EXISTS `contact_us`;
CREATE TABLE IF NOT EXISTS `contact_us` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `reply` text,
  `status` varchar(20) DEFAULT 'Pending',
  `reply_date` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
CREATE TABLE IF NOT EXISTS `faq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `display_order` int DEFAULT '0',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
CREATE TABLE IF NOT EXISTS `offers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_applicable_amount` decimal(10,2) DEFAULT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `per_user_limit` int DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `offers`
--

INSERT INTO `offers` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `max_applicable_amount`, `max_discount_amount`, `valid_from`, `valid_to`, `usage_limit`, `per_user_limit`, `status`, `description`) VALUES
(1, 'PRATYUSH20', 'percent', 20.00, 1000.00, 5000.00, 200.00, '2025-11-29 22:37:00', '2025-12-03 22:37:00', 50, 2, 'Active', 'offer discount 20 percent');

-- --------------------------------------------------------

--
-- Table structure for table `password_token`
--

DROP TABLE IF EXISTS `password_token`;
CREATE TABLE IF NOT EXISTS `password_token` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `otp_attempts` int NOT NULL,
  `last_resend` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `fk_products_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `brand`, `price`, `discount`, `stock`, `description`, `long_description`, `image`, `gallery_images`, `status`) VALUES
(1, 'Samsung A17', 2, 'Samsung', 21000.00, 5.00, 20, '', '8 GB RAM', '1764333566_programming-language-stacked-cubes-o6ete8w4aqet8mv7.jpg', '[\"images/products/gallery/1764213379_0_programming-hd-2eo94s73hxhwjcta.jpg\", \"images/products/gallery/1764213379_1_a-children-learning-coding-or-computer-programming-flat-illustration-coding-for-kids-basic-computer-programing-can-be-used-for-web-landing-page-social-media-promotion-etc-vector.jpg\", \"images/products/gallery/1764213379_2_programming-languages-1.avif\", \"images/products/gallery/1764213379_3_Computer-Programming-Language.jpg\", \"images/products/gallery/1764213379_4_Brief-History-of-Programming-Languages.jpg\", \"images/products/gallery/1764213379_5_Mixed-languages.jpg\"]', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

DROP TABLE IF EXISTS `registration`;
CREATE TABLE IF NOT EXISTS `registration` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `fullname`, `email`, `password`, `gender`, `mobile`, `profile_picture`, `address`, `status`, `role`, `token`) VALUES
(1, 'Janki Kansagra', 'janki.kansagra@rku.ac.in', 'Janki@12345', 'Female', 8523697410, '68d4cb6d18df5m-mongo.png', 'Rajkot', 'Active', 'Admin', '68c7d44a15286c1489ffc0db8b0b5ad6e9e3bfcfaef4b591b978cb21f07d85a6789b5022f5cf5498453552463e53ccb102b7'),
(4, 'Janki Faldu', 'jankikansagra12@gmail.com', 'Janki@123456', 'Male', 8155825235, '6918882b0a30escreencapture-file-D-ravi-JP-test-html-2025-11-14-23_57_52.png', '                                                Rajkot                                                                                                                               ', 'Active', 'User', 'c21ef5ea11056ebe8c9b52955f87d343f9a3c2db6c8d5803e893496c6a0b005c64910183184a463185ef5bf98aa2747235cf'),
(7, 'Rajesh', 'rghumaliya777@rku.ac.in', 'Rajesh@1234', 'Male', 2587413690, '68d4d5284dc6ax-xml.png', 'Rajkot', 'Deleted', 'User', 'fd4a6875ae5d56c8bca1b4e1f41688460cd6f614bb281544a8c0dcc5ef0f57e1f2dd3d6df95c5dd0cd568a75af67a2c3a75d'),
(10, 'gopi', 'gvyas950@rku.ac.in', 'demo@1234', 'Female', 8525825822, '68d4d74ab8f3av-vue.jpeg', 'rajkot', 'Deleted', 'User', '800b71c0648e84ed7910133eae44b0271f6371aa6794acdb772589285e787d0914d4ec518948bf2e100556d6e9645ba9a548'),
(11, 'Varun Patel', 'varunpatel35@gmail.com', 'Varun@12345', 'Male', 5865983212, '68e87c5481cccg-git.png', '                            rajkot', 'Active', 'User', '975a0525f13d8639ce989a19ec088b92198310b68f653f15ab67e582c3a6bd3c44673ec7c0dc3bba81832b529e1cceb874f0'),
(14, 'Pratyush', 'pratyushf31@northstar.edu.in', '$2y$12$W2q/JedahRHpbC9kRJnMouSP3iL4oeLZd96RAC00G/CHsFS0pqWH6', 'Male', 4845845845, NULL, 'Bapa sitaram chowk\r\nMavdi gam', 'Active', 'Admin', '35c01aa9a6615e79fcbf1c96'),
(16, 'Denish', 'denish25@gmail.com', 'Denish@1234', 'Male', 8155825234, 'default.png', 'Bapa sitaram chowk\r\nMavdi gam', 'Active', 'User', 'c3c0a8f6a5d87cd3b649f21ca2b0966b'),
(17, 'Janki Kansagra', 'janki345@gmail.com', 'JAnki@12345', 'Female', 1478523690, 'default.png', 'Bapa sitaram chowk\r\nMavdi gam', 'Active', 'User', '039dafa20c2ec59e3619df533bcd9c9f');

-- --------------------------------------------------------

--
-- Table structure for table `site_pages`
--

DROP TABLE IF EXISTS `site_pages`;
CREATE TABLE IF NOT EXISTS `site_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(50) NOT NULL,
  `page_title` varchar(200) NOT NULL,
  `page_content` longtext NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_slug` (`page_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `site_pages`
--

INSERT INTO `site_pages` (`id`, `page_slug`, `page_title`, `page_content`, `status`, `updated_at`, `updated_by`) VALUES
(1, 'about', 'About Us', '<p>MobileStore is a leading retailer of premium smartphones, committed to bringing the latest technology to our customers. We offer a wide range of devices, accessories, and exclusive offers, ensuring the best shopping experience for mobile enthusiasts.</p><p>Founded in 2020, our mission is to combine quality, affordability, and exceptional customer service under one roof. Whether you\'re looking for the newest flagship model or a budget-friendly smartphone, we\'ve got you covered.</p>', 'Active', '2025-11-29 17:28:23', NULL),
(2, 'privacy', 'Privacy Policy', '<h4>1. Information We Collect</h4><p>We collect information that you provide directly to us...</p><h4>2. How We Use Your Information</h4><p>We use your information to process orders and improve our services...</p>', 'Active', '2025-11-29 17:28:23', NULL),
(3, 'terms', 'Terms & Conditions', '<h4>1. Acceptance of Terms</h4><p>By accessing our website, you agree to these terms...</p>', 'Active', '2025-11-29 17:28:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

DROP TABLE IF EXISTS `team_members`;
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT 'images/team/default.jpg',
  `bio` text,
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `display_order` int DEFAULT '0',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) NOT NULL,
  `product_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item` (`user_email`,`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_email`, `product_id`, `added_at`) VALUES
(1, 'denish25@gmail.com', 1, '2025-11-28 13:10:00');

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
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

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
