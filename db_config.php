<?php
session_start();
$con = mysqli_connect("localhost", "root", "",);

try {
    mysqli_select_db($con, "MCA_2025");
} catch (Exception $e) {
    echo "Error in selecting database";
}
function flush_stored_results($con)
{
    while ($con->more_results() && $con->next_result()) {
        $extra = $con->use_result();
        if ($extra instanceof mysqli_result) $extra->free();
    }
}
date_default_timezone_set('Asia/Kolkata');
$current_time = date("Y-m-d H:i:s");

$stmt = $con->prepare("CALL CleanupExpiredTokens()");
$stmt->execute();

flush_stored_results($con);




// $stmt = $con->prepare("CALL CleanupExpiredTokens()");
// $stmt->execute();
// // if ($con) {
//     // $q = "create database MCA_sample_1";

// *********************REGISTRATION TABLE*************** */
//     $q = "create table registration (
//     fullname char(50),
//     email varchar(50),
//     password varchar(25),
//     gender char(6),
//     mobile bigint(10),
//     profile_picture varchar(100),
//     address text,
//     status char(8) default 'Inactive',
//     role char(10) default 'User',
//     token text
//     )";

// *********************ADDRESS TABLE*************** */
// $q = "CREATE TABLE IF NOT EXISTS addresses (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     user_id VARCHAR(50) NOT NULL,
//     name VARCHAR(100) NOT NULL,
//     email VARCHAR(100) NOT NULL,
//     mobile VARCHAR(15) NOT NULL,
//     address TEXT NOT NULL,
//     FOREIGN KEY (user_id) REFERENCES registration(email) ON DELETE CASCADE
// )";


// *********************CONTACT US TABLE*************** */

// $q = "create table contact_us(
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     name VARCHAR(100) NOT NULL,
//     email VARCHAR(100) NOT NULL,
//     subject VARCHAR(150) NOT NULL,
//     message TEXT NOT NULL,
//     reply TEXT,
//     status VARCHAR(20) DEFAULT 'Pending',
//     reply_date TIMESTAMP NULL,
//     submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// )";

// ********************PASSWORD TOKEN TABLE*************** */
// $q = "CREATE TABLE password_token (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, otp INT(6),created_at DATETIME NOT NULL,expires_at DATETIME NOT NULL, otp_attempts INT NOT NULL,last_resend TIMESTAMP DEFAULT CURRENT_TIMESTAMP)";
// try {
//     if ($con->query($q)) {
//         echo "Table Created Successfully";
//     }
// } catch (Exception $e) {
//     echo "Error in Creating Table" . $e->getMessage();
// }

//*******************OFFERS*************** */
// $q= "CREATE TABLE offers (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   code VARCHAR(100) NOT NULL UNIQUE,                -- coupon / promo code
//   discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
//   discount_value DECIMAL(10,2) NOT NULL,            -- if percent => store percentage (e.g. 20.00). if fixed => fixed amount.
//   min_order_amount DECIMAL(10,2) DEFAULT NULL,      -- minimum order value to apply the offer (nullable)
//   max_applicable_amount DECIMAL(10,2) DEFAULT NULL, -- maximum amount on which discount is calculated (nullable)
//   max_discount_amount DECIMAL(10,2) DEFAULT NULL,   -- maximum discount payable (cap)
//   valid_from DATETIME DEFAULT NULL,
//   valid_to DATETIME DEFAULT NULL,
//   usage_limit INT DEFAULT NULL,                     -- total number of times offer can be used (nullable)
//   per_user_limit INT DEFAULT NULL,                  -- how many times a single user can use (nullable)
//   status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
//   description VARCHAR(500) DEFAULT NULL
// )";


//*******************CATEGORIES*************** */

// $q="CREATE TABLE categories (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   category_name VARCHAR(150) NOT NULL,
//   status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
// )";

//*******************PRODUCTS*************** */

// $q = "CREATE TABLE products (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   name VARCHAR(255) NOT NULL,
//   category_id INT NULL,
//   brand VARCHAR(100) DEFAULT NULL,
//   price DECIMAL(10,2) NOT NULL,
//   discount DECIMAL(10,2) DEFAULT 0.00,
//   final_price DECIMAL(10,2) AS (price - discount) STORED,
//   stock INT DEFAULT 0,
//   description TEXT DEFAULT NULL,
//   long_description LONGTEXT DEFAULT NULL,
//   image VARCHAR(255) DEFAULT NULL,
//   gallery_images JSON DEFAULT NULL,         -- JSON array of image filenames/URLs
//   status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
//   CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
// )";


// *********** CART ******************

// $q = "CREATE TABLE IF NOT EXISTS cart (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     user_email VARCHAR(255) NOT NULL,
//     product_id INT NOT NULL,
//     quantity INT NOT NULL DEFAULT 1,
//     added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

//     -- Link to your user table
//     FOREIGN KEY (user_email) REFERENCES registration(email) ON DELETE CASCADE,
//     FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE

//     -- Note: We assume you have a 'products' table. 
//     -- If so, you should also add: FOREIGN KEY (product_id) REFERENCES products(id)
// );";


// ***************** Wishlist *********************
// $q = "CREATE TABLE IF NOT EXISTS wishlist (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     user_email VARCHAR(255) NOT NULL,
//     product_id INT NOT NULL,
//     added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
//     -- Links to your user and product tables
//     FOREIGN KEY (user_email) REFERENCES registration(email) ON DELETE CASCADE,
//     FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
//     -- ensure a user can't add the same product twice
//     UNIQUE KEY unique_item (user_email, product_id) 
// )";

// if (mysqli_query($con, $q)) {
//     echo "Table Created";
// } else {
//     echo "Error in creating table";
// }
// echo "Connceted to Database";
// } else {
//     echo "Error in connection";
// }
