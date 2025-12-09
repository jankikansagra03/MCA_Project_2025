DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Delete`(
    IN p_id INT
)
BEGIN
    DELETE FROM addresses
    WHERE id = p_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Insert`(
    IN p_user_id VARCHAR(50),
    IN p_name VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_mobile VARCHAR(15),
    IN p_address TEXT
)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Select`(
    IN p_user_id VARCHAR(50),  -- The user's email
    IN p_address_id INT       -- The specific address ID (or NULL)
)
BEGIN
    SELECT * FROM addresses
    WHERE
        -- Match the user ID
        (p_user_id IS NULL OR user_id = p_user_id)
        AND
        -- If p_address_id is NOT NULL, find by that ID.
        -- If it IS NULL, this part is ignored.
        (p_address_id IS NULL OR id = p_address_id);
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Addresses_Update`(
    IN p_id INT, -- The ID of the address to update
    IN p_name VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_mobile VARCHAR(15),
    IN p_address TEXT
)
BEGIN
    UPDATE addresses
    SET
        name = p_name,
        email = p_email,
        mobile = p_mobile,
        address = p_address
    WHERE
        id = p_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Delete`(
    IN p_CartId INT
)
BEGIN
    DELETE FROM cart WHERE id = p_CartId;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Empty`(
    IN p_Email VARCHAR(255)
)
BEGIN
    DELETE FROM cart WHERE user_email = p_Email;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Insert`(
    IN p_Email VARCHAR(255),
    IN p_ProductId INT,
    IN p_Quantity INT
)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Select`(
    IN p_Email VARCHAR(255)
)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Cart_Update`(
    IN p_CartId INT,
    IN p_Quantity INT
)
BEGIN
    UPDATE cart 
    SET quantity = p_Quantity 
    WHERE id = p_CartId;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Delete`(IN p_id INT)
BEGIN
    UPDATE categories
    SET status = 'Inactive'
    WHERE id = p_id;

    SELECT ROW_COUNT() AS rows_affected;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_GetActive`()
BEGIN
    SELECT *
    FROM categories
    WHERE status = 'Active'
    ORDER BY category_name ASC;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Insert`(
    IN p_category_name VARCHAR(150),
    IN p_status ENUM('Active','Inactive')
)
BEGIN
    INSERT INTO categories (category_name, status)
    VALUES (TRIM(p_category_name), p_status);

    SELECT ROW_COUNT() AS rows_affected;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Search`(IN p_keyword VARCHAR(150))
BEGIN
    SELECT *
    FROM categories
    WHERE 
        category_name LIKE CONCAT('%', p_keyword, '%')
        OR status LIKE CONCAT('%', p_keyword, '%')
        OR id = p_keyword;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Category_Update`(IN `p_id` INT, IN `p_category_name` VARCHAR(150), IN `p_status` ENUM('Active','Inactive'))
BEGIN
    UPDATE categories
    SET 
        category_name = TRIM(p_category_name),
        status = p_status
    WHERE id = p_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanupExpiredTokens`()
BEGIN
    
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Delete`(IN `p_Id` INT)
BEGIN
    DELETE FROM contact_us
    WHERE id = p_Id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Insert`(IN `p_Name` VARCHAR(100), IN `p_Email` VARCHAR(100), IN `p_Subject` VARCHAR(150), IN `p_Message` TEXT)
BEGIN
    INSERT INTO contact_us (name, email, subject, message)
    VALUES (p_Name, p_Email, p_Subject, p_Message);
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Select`(IN `p_Id` INT, IN `p_Status` VARCHAR(20), IN `p_Email` VARCHAR(50))
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ContactUs_Update`(IN `p_Id` INT, IN `p_Reply` TEXT, IN `p_Status` VARCHAR(20))
BEGIN
    UPDATE contact_us
    SET
        reply = p_Reply,
        status = p_Status,
        reply_date = NOW()
    WHERE
        id = p_Id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Delete`(
    IN `p_id` INT
)
BEGIN
    UPDATE offers 
    SET status = 'Inactive'
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_GetActive`()
BEGIN
    SELECT * FROM offers
    WHERE status = 'Active'
        AND (valid_from IS NULL OR valid_from <= NOW())
        AND (valid_to IS NULL OR valid_to >= NOW())
    ORDER BY id DESC;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Insert`(
    IN `p_code` VARCHAR(100),
    IN `p_discount_type` ENUM('percent','fixed'),
    IN `p_discount_value` DECIMAL(10,2),
    IN `p_min_order_amount` DECIMAL(10,2),
    IN `p_max_applicable_amount` DECIMAL(10,2),
    IN `p_max_discount_amount` DECIMAL(10,2),
    IN `p_valid_from` DATETIME,
    IN `p_valid_to` DATETIME,
    IN `p_usage_limit` INT,
    IN `p_per_user_limit` INT,
    IN `p_status` ENUM('Active','Inactive'),
    IN `p_description` VARCHAR(500)
)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Search`(
    IN `p_id` INT,
    IN `p_code` VARCHAR(100),
    IN `p_discount_type` ENUM('percent','fixed'),
    IN `p_status` ENUM('Active','Inactive')
)
BEGIN
    SELECT * FROM offers
    WHERE
        (p_id IS NULL OR p_id = 0 OR id = p_id)
        AND (p_code IS NULL OR p_code = '' OR code LIKE CONCAT('%', p_code, '%'))
        AND (p_discount_type IS NULL OR p_discount_type = '' OR discount_type = p_discount_type)
        AND (p_status IS NULL OR p_status = '' OR status = p_status)
    ORDER BY id DESC;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Select`(
    IN `p_id` INT
)
BEGIN
    SELECT * FROM offers
    WHERE 
        -- If p_id is NULL or 0, return all offers
        -- Otherwise return specific offer
        (p_id IS NULL OR p_id = 0 OR id = p_id)
    ORDER BY id DESC;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_Update`(
    IN `p_id` INT,
    IN `p_code` VARCHAR(100),
    IN `p_discount_type` ENUM('percent','fixed'),
    IN `p_discount_value` DECIMAL(10,2),
    IN `p_min_order_amount` DECIMAL(10,2),
    IN `p_max_applicable_amount` DECIMAL(10,2),
    IN `p_max_discount_amount` DECIMAL(10,2),
    IN `p_valid_from` DATETIME,
    IN `p_valid_to` DATETIME,
    IN `p_usage_limit` INT,
    IN `p_per_user_limit` INT,
    IN `p_status` ENUM('Active','Inactive'),
    IN `p_description` VARCHAR(500)
)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Offers_ValidateCode`(
    IN `p_code` VARCHAR(100)
)
BEGIN
    SELECT * FROM offers
    WHERE 
        UPPER(TRIM(code)) = UPPER(TRIM(p_code))
        AND status = 'Active'
        AND (valid_from IS NULL OR valid_from <= NOW())
        AND (valid_to IS NULL OR valid_to >= NOW())
    LIMIT 1;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Delete`(IN `p_Email` VARCHAR(255))
BEGIN
    DELETE FROM password_token 
    WHERE email = p_Email;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Insert`(
    IN p_Email VARCHAR(255),
    IN p_Otp INT(6),
    IN p_CreatedAt DATETIME,
    IN p_ExpiresAt DATETIME,
    IN p_OtpAttempts INT(1)
)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Select`(IN `p_Email` VARCHAR(255))
BEGIN
    -- This just selects the data. No more update logic.
    SELECT * FROM password_token 
    WHERE email = p_Email;
    
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `PasswordToken_Update`(IN `p_Email` VARCHAR(255), IN `p_Otp` INT(6), IN `p_CreatedAt` DATETIME, IN `p_ExpiresAt` DATETIME, IN `P_Otp_attempts` INT(1))
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Delete`(
    IN p_id INT
)
BEGIN
    UPDATE products SET
        status = 'Deleted'
    WHERE id = p_id;
    
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Insert`(
    IN p_name VARCHAR(255),
    IN p_category_id INT,
    IN p_brand VARCHAR(100),
    IN p_price DECIMAL(10,2),
    IN p_discount DECIMAL(10,2),
    IN p_stock INT,
    IN p_description TEXT,
    IN p_long_description LONGTEXT,
    IN p_image VARCHAR(255),
    IN p_gallery_images TEXT,
    IN p_status ENUM('Active','Inactive')
)
BEGIN
    INSERT INTO products (
        name, category_id, brand, price, discount, stock, 
        description, long_description, image, gallery_images, status
    ) VALUES (
        p_name, p_category_id, p_brand, p_price, p_discount, p_stock,
        p_description, p_long_description, p_image, p_gallery_images, p_status
    );
    
    SELECT LAST_INSERT_ID() AS product_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Search`(IN `p_id` INT, IN `p_name` VARCHAR(255), IN `p_category_id` INT, IN `p_brand` VARCHAR(100), IN `p_min_price` DECIMAL(10,2), IN `p_max_price` DECIMAL(10,2), IN `p_status` ENUM('Active','Inactive'), IN `p_min_stock` INT)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Select`(IN `p_id` INT)
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Products_Update`(IN `p_id` INT, IN `p_name` VARCHAR(255), IN `p_category_id` INT, IN `p_brand` VARCHAR(100), IN `p_price` DECIMAL(10,2), IN `p_discount` DECIMAL(10,2), IN `p_stock` INT, IN `p_description` TEXT, IN `p_long_description` LONGTEXT, IN `p_image` VARCHAR(255), IN `p_gallery_images` JSON, IN `p_status` ENUM('Active','Inactive'))
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Delete`(IN p_Email VARCHAR(255))
BEGIN
    -- Soft delete user
    UPDATE registration
    SET status = 'Deleted'
    WHERE TRIM(LOWER(email)) = TRIM(LOWER(p_Email));

    -- Return how many rows were updated
    SELECT ROW_COUNT() AS rows_affected;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Insert`(IN `p_Fullname` VARCHAR(255), IN `p_Email` VARCHAR(255), IN `p_Password` VARCHAR(255), IN `p_MobileNumber` BIGINT, IN `p_Gender` VARCHAR(50), IN `p_ProfilePicture` VARCHAR(255), IN `p_Address` VARCHAR(50), IN `p_Token` TEXT, IN `p_Role` VARCHAR(25), IN `p_Status` VARCHAR(25))
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Search`(IN `p_Search` VARCHAR(255))
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Select`(IN `p_Fullname` VARCHAR(255), IN `p_Email` VARCHAR(255), IN `p_Role` VARCHAR(50), IN `p_Status` VARCHAR(50), IN `p_Password` VARCHAR(50), IN `p_Token` TEXT, IN `p_Mobile` BIGINT(10))
BEGIN
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Registration_Update`(IN `p_Fullname` VARCHAR(255), IN `p_Email` VARCHAR(255), IN `p_Password` VARCHAR(255), IN `p_MobileNumber` BIGINT, IN `p_Gender` CHAR(52), IN `p_ProfilePicture` VARCHAR(255), IN `p_Address` TEXT, IN `p_Status` VARCHAR(50), IN `p_Role` VARCHAR(50))
BEGIN
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
DELIMITER ;

DELIMITER $$

-- ============================================
-- WISHLIST PROCEDURES
-- ============================================

CREATE PROCEDURE `Wishlist_Insert` (
    IN `p_user_email` VARCHAR(255),
    IN `p_product_id` INT
)
BEGIN
    -- Insert only if not already in wishlist (unique constraint handles this)
    INSERT IGNORE INTO wishlist (user_email, product_id)
    VALUES (p_user_email, p_product_id);
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

CREATE PROCEDURE `Wishlist_Select` (
    IN `p_user_email` VARCHAR(255)
)
BEGIN
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

CREATE PROCEDURE `Wishlist_Delete` (
    IN `p_id` INT
)
BEGIN
    DELETE FROM wishlist WHERE id = p_id;
    SELECT ROW_COUNT() AS rows_affected;
END$$

CREATE PROCEDURE `Wishlist_Empty` (
    IN `p_user_email` VARCHAR(255)
)
BEGIN
    DELETE FROM wishlist WHERE user_email = p_user_email;
    SELECT ROW_COUNT() AS rows_affected;
END$$

CREATE PROCEDURE `Wishlist_CheckExists` (
    IN `p_user_email` VARCHAR(255),
    IN `p_product_id` INT
)
BEGIN
    SELECT COUNT(*) as exists_count
    FROM wishlist
    WHERE user_email = p_user_email AND product_id = p_product_id;
END$$

DELIMITER ;
DELIMITER $$

-- ============================================
-- SITE PAGES PROCEDURES
-- ============================================

CREATE PROCEDURE `SitePages_Select` (
    IN `p_page_slug` VARCHAR(50)
)
BEGIN
    SELECT * FROM site_pages
    WHERE (p_page_slug IS NULL OR p_page_slug = '' OR page_slug = p_page_slug)
    ORDER BY page_slug;
END$$

CREATE PROCEDURE `SitePages_Update` (
    IN `p_id` INT,
    IN `p_page_title` VARCHAR(200),
    IN `p_page_content` LONGTEXT,
    IN `p_status` ENUM('Active','Inactive'),
    IN `p_updated_by` VARCHAR(100)
)
BEGIN
    UPDATE site_pages
    SET 
        page_title = p_page_title,
        page_content = p_page_content,
        status = p_status,
        updated_by = p_updated_by
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

-- ============================================
-- CONTACT INFO PROCEDURES
-- ============================================

CREATE PROCEDURE `ContactInfo_Select` ()
BEGIN
    SELECT * FROM contact_info LIMIT 1;
END$$

CREATE PROCEDURE `ContactInfo_Update` (
    IN `p_id` INT,
    IN `p_company_name` VARCHAR(200),
    IN `p_tagline` VARCHAR(255),
    IN `p_email` VARCHAR(100),
    IN `p_phone` VARCHAR(20),
    IN `p_alternate_phone` VARCHAR(20),
    IN `p_whatsapp_number` VARCHAR(20),
    IN `p_address` TEXT,
    IN `p_city` VARCHAR(100),
    IN `p_state` VARCHAR(100),
    IN `p_country` VARCHAR(100),
    IN `p_postal_code` VARCHAR(20),
    IN `p_facebook_url` VARCHAR(255),
    IN `p_twitter_url` VARCHAR(255),
    IN `p_instagram_url` VARCHAR(255),
    IN `p_linkedin_url` VARCHAR(255),
    IN `p_youtube_url` VARCHAR(255),
    IN `p_map_embed_url` TEXT
)
BEGIN
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

-- ============================================
-- TEAM MEMBERS PROCEDURES
-- ============================================

CREATE PROCEDURE `TeamMembers_Insert` (
    IN `p_name` VARCHAR(100),
    IN `p_designation` VARCHAR(100),
    IN `p_photo` VARCHAR(255),
    IN `p_bio` TEXT,
    IN `p_facebook_url` VARCHAR(255),
    IN `p_twitter_url` VARCHAR(255),
    IN `p_linkedin_url` VARCHAR(255),
    IN `p_display_order` INT,
    IN `p_status` ENUM('Active','Inactive')
)
BEGIN
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

CREATE PROCEDURE `TeamMembers_Select` (
    IN `p_id` INT
)
BEGIN
    SELECT * FROM team_members
    WHERE (p_id IS NULL OR p_id = 0 OR id = p_id)
    ORDER BY display_order ASC, id ASC;
END$$

CREATE PROCEDURE `TeamMembers_Update` (
    IN `p_id` INT,
    IN `p_name` VARCHAR(100),
    IN `p_designation` VARCHAR(100),
    IN `p_photo` VARCHAR(255),
    IN `p_bio` TEXT,
    IN `p_facebook_url` VARCHAR(255),
    IN `p_twitter_url` VARCHAR(255),
    IN `p_linkedin_url` VARCHAR(255),
    IN `p_display_order` INT,
    IN `p_status` ENUM('Active','Inactive')
)
BEGIN
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
    
    SELECT ROW_COUNT() AS rows_affected;
END$$

CREATE PROCEDURE `TeamMembers_Delete` (
    IN `p_id` INT
)
BEGIN
    DELETE FROM team_members WHERE id = p_id;
    SELECT ROW_COUNT() AS rows_affected;
END$$

-- ============================================
-- FAQ PROCEDURES
-- ============================================

CREATE PROCEDURE `FAQ_Insert` (
    IN `p_question` VARCHAR(500),
    IN `p_answer` TEXT,
    IN `p_category` VARCHAR(100),
    IN `p_display_order` INT,
    IN `p_status` ENUM('Active','Inactive')
)
BEGIN
    INSERT INTO faq (question, answer, category, display_order, status)
    VALUES (p_question, p_answer, p_category, p_display_order, p_status);
    
    SELECT LAST_INSERT_ID() AS faq_id;
END$$

CREATE PROCEDURE `FAQ_Select` (
    IN `p_id` INT
)
BEGIN
    SELECT * FROM faq
    WHERE (p_id IS NULL OR p_id = 0 OR id = p_id)
    ORDER BY display_order ASC, id ASC;
END$$

CREATE PROCEDURE `FAQ_Update` (
    IN `p_id` INT,
    IN `p_question` VARCHAR(500),
    IN `p_answer` TEXT,
    IN `p_category` VARCHAR(100),
    IN `p_display_order` INT,
    IN `p_status` ENUM('Active','Inactive')
)
BEGIN
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

CREATE PROCEDURE `FAQ_Delete` (
    IN `p_id` INT
)
BEGIN
    DELETE FROM faq WHERE id = p_id;
    SELECT ROW_COUNT() AS rows_affected;
END$$

CREATE PROCEDURE `FAQ_Search` (
    IN `p_category` VARCHAR(100),
    IN `p_status` ENUM('Active','Inactive')
)
BEGIN
    SELECT * FROM faq
    WHERE 
        (p_category IS NULL OR p_category = '' OR category = p_category)
        AND (p_status IS NULL OR p_status = '' OR status = p_status)
    ORDER BY display_order ASC, id ASC;
END$$

DELIMITER ;

