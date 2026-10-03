-- ============================================================================
-- FOODRESCUE / SURPLUS FOOD RESCUE & DONATION NETWORK DATABASE SCHEMA
-- Database Management System: MySQL / MariaDB (Compatible with XAMPP / phpMyAdmin)
-- Project: FoodRescue DBMS / Web Programming Project
-- ============================================================================

CREATE DATABASE IF NOT EXISTS foodrescue_db;

USE foodrescue_db;

-- ============================================================================
-- 1. USERS TABLE
-- Central authentication & user management for Donors, Charities, Volunteers, Admins
-- ============================================================================
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  role ENUM('Donor', 'Charity', 'Volunteer', 'Admin') NOT NULL,
  organization_type ENUM('Restaurant', 'Bakery', 'Supermarket', 'Foundation', 'Charity NGO', 'Individual', 'Other') DEFAULT 'Individual',
  reg_nid_number VARCHAR(50) DEFAULT NULL,
  nid_image_path VARCHAR(255) DEFAULT NULL,
  address TEXT NOT NULL,
  account_status ENUM('Pending', 'Active', 'Disabled', 'Verified', 'Rejected') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_status (account_status)
);

-- ============================================================================
-- 2. FOOD CATEGORIES TABLE
-- Managed by Admin for organizing food donations
-- ============================================================================
CREATE TABLE food_categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_categories_status (status)
);

-- ============================================================================
-- 3. FOOD DONATIONS TABLE
-- Surplus food listings posted by Food Donors
-- ============================================================================
CREATE TABLE food_donations (
  donation_id INT AUTO_INCREMENT PRIMARY KEY,
  donor_id INT NOT NULL,
  category_id INT NOT NULL,
  food_name VARCHAR(200) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  quantity_unit VARCHAR(50) NOT NULL DEFAULT 'unit',
  expiry_date DATETIME NOT NULL,
  description TEXT DEFAULT NULL,
  food_safety_info TEXT DEFAULT NULL,
  pickup_date DATE NOT NULL,
  preferred_time VARCHAR(50) NOT NULL,
  pickup_address TEXT NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  status ENUM('Available', 'Claimed', 'Picked Up', 'Completed', 'Expired', 'Cancelled') NOT NULL DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_donation_quantity CHECK (quantity > 0),
  CONSTRAINT fk_donations_donor FOREIGN KEY (donor_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_donations_category FOREIGN KEY (category_id) REFERENCES food_categories (category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_donations_status (status),
  INDEX idx_donations_donor (donor_id),
  INDEX idx_donations_category (category_id)
);

-- ============================================================================
-- 4. FOOD CLAIMS TABLE
-- Record of surplus food claimed by Recipient Charities
-- ============================================================================
CREATE TABLE food_claims (
  claim_id INT AUTO_INCREMENT PRIMARY KEY,
  donation_id INT NOT NULL UNIQUE,
  charity_id INT NOT NULL,
  quantity_claimed DECIMAL(10,2) NOT NULL,
  claim_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Claimed', 'Scheduled', 'In Transit', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Claimed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_claim_quantity CHECK (quantity_claimed > 0),
  CONSTRAINT fk_claims_donation FOREIGN KEY (donation_id) REFERENCES food_donations (donation_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_claims_charity FOREIGN KEY (charity_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_claims_charity (charity_id),
  INDEX idx_claims_status (status)
);

-- ============================================================================
-- 5. PICKUP & DELIVERIES TABLE
-- Logistics lifecycle tracking connecting Donor, Charity, and Volunteer Rider
-- Donor and Charity references are derived via claim_id -> food_claims -> food_donations
-- ============================================================================
CREATE TABLE pickup_deliveries (
  delivery_id INT AUTO_INCREMENT PRIMARY KEY,
  claim_id INT NOT NULL UNIQUE,
  volunteer_id INT DEFAULT NULL,
  pickup_address TEXT NOT NULL,
  delivery_address TEXT NOT NULL,
  pickup_date DATE NOT NULL,
  scheduled_time VARCHAR(50) NOT NULL,
  special_instructions TEXT DEFAULT NULL,
  delivery_status ENUM('Available', 'Assigned', 'Accepted', 'Picked Up', 'In Transit', 'Delivered', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Available',
  assigned_at DATETIME DEFAULT NULL,
  picked_up_at DATETIME DEFAULT NULL,
  in_transit_at DATETIME DEFAULT NULL,
  delivered_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_deliveries_claim FOREIGN KEY (claim_id) REFERENCES food_claims (claim_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_deliveries_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_deliveries_status (delivery_status),
  INDEX idx_deliveries_volunteer (volunteer_id)
);

-- ============================================================================
-- 6. OTP VERIFICATIONS TABLE
-- 6-digit OTP security handover verification for deliveries
-- ============================================================================
CREATE TABLE otp_verifications (
  otp_id INT AUTO_INCREMENT PRIMARY KEY,
  delivery_id INT NOT NULL,
  otp_code CHAR(6) NOT NULL,
  expiry_time DATETIME NOT NULL,
  status ENUM('Pending', 'Verified', 'Expired') NOT NULL DEFAULT 'Pending',
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verified_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_otp_delivery FOREIGN KEY (delivery_id) REFERENCES pickup_deliveries (delivery_id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_otp_delivery (delivery_id),
  INDEX idx_otp_status (status)
);

-- ============================================================================
-- 7. FOOD DISTRIBUTIONS TABLE
-- Distribution log submitted by Recipient Charities tracking community impact
-- Charity reference is derived via claim_id -> food_claims(charity_id)
-- ============================================================================
CREATE TABLE food_distributions (
  distribution_id INT AUTO_INCREMENT PRIMARY KEY,
  claim_id INT NOT NULL,
  food_item_name VARCHAR(200) NOT NULL,
  quantity_distributed DECIMAL(10,2) NOT NULL,
  quantity_unit VARCHAR(50) NOT NULL DEFAULT 'unit',
  quality VARCHAR(100) NOT NULL DEFAULT 'Good',
  people_served INT NOT NULL,
  remarks TEXT DEFAULT NULL,
  distributed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_dist_quantity CHECK (quantity_distributed > 0),
  CONSTRAINT chk_dist_people CHECK (people_served >= 0),
  CONSTRAINT fk_dist_claim FOREIGN KEY (claim_id) REFERENCES food_claims (claim_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_dist_claim (claim_id)
);

-- ============================================================================
-- 8. ISSUE REPORTS TABLE
-- Issue tracking system for Charities, Volunteers, and Donors
-- ============================================================================
CREATE TABLE issue_reports (
  issue_id INT AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT NOT NULL,
  report_type ENUM('Food Quality Issue', 'Pickup Issue', 'Delivery Issue', 'Food Safety Concern', 'Other') NOT NULL,
  related_donation_id INT DEFAULT NULL,
  related_delivery_id INT DEFAULT NULL,
  description TEXT NOT NULL,
  evidence_file VARCHAR(255) DEFAULT NULL,
  status ENUM('Pending', 'Under Review', 'Resolved', 'Closed') NOT NULL DEFAULT 'Pending',
  resolution_details TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_issues_reporter FOREIGN KEY (reporter_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_issues_donation FOREIGN KEY (related_donation_id) REFERENCES food_donations (donation_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_issues_delivery FOREIGN KEY (related_delivery_id) REFERENCES pickup_deliveries (delivery_id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_issues_reporter (reporter_id),
  INDEX idx_issues_status (status)
);

-- ============================================================================
-- 9. DELAY REPORTS TABLE
-- Delivery service delay reports submitted by Volunteer Riders
-- ============================================================================
CREATE TABLE delay_reports (
  delay_id INT AUTO_INCREMENT PRIMARY KEY,
  delivery_id INT NOT NULL,
  volunteer_id INT NOT NULL,
  delay_reason ENUM('Traffic / Road Closure', 'Vehicle Breakdown', 'Weather Conditions', 'Restaurant Delay', 'Other') NOT NULL,
  estimated_delay_minutes INT NOT NULL,
  additional_comments TEXT DEFAULT NULL,
  reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_delay_minutes CHECK (estimated_delay_minutes > 0),
  CONSTRAINT fk_delays_delivery FOREIGN KEY (delivery_id) REFERENCES pickup_deliveries (delivery_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_delays_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_delays_delivery (delivery_id),
  INDEX idx_delays_volunteer (volunteer_id)
);

-- ============================================================================
-- 10. MESSAGES TABLE
-- Direct messaging communication between platform users
-- ============================================================================
CREATE TABLE messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  delivery_id INT DEFAULT NULL,
  message_text TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_messages_delivery FOREIGN KEY (delivery_id) REFERENCES pickup_deliveries (delivery_id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_messages_conversation (sender_id, receiver_id),
  INDEX idx_messages_read (is_read)
);

-- ============================================================================
-- 11. RECEIPTS TABLE
-- Tax & donation proof receipts linked strictly to completed food claims
-- All donor/charity names, emails, quantities, and dates derived via JOINs
-- ============================================================================
CREATE TABLE receipts (
  receipt_id VARCHAR(50) PRIMARY KEY,
  claim_id INT NOT NULL UNIQUE,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_receipts_claim FOREIGN KEY (claim_id) REFERENCES food_claims (claim_id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- ============================================================================
-- 12. BROADCASTS TABLE
-- Urgent alerts and public notices published by System Admin
-- ============================================================================
CREATE TABLE broadcasts (
  broadcast_id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  audience ENUM('All Users', 'Donors Only', 'Volunteers Only', 'Charities Only') NOT NULL DEFAULT 'All Users',
  status ENUM('Draft', 'Sent', 'Scheduled') NOT NULL DEFAULT 'Draft',
  scheduled_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_broadcasts_admin FOREIGN KEY (admin_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_broadcasts_status (status)
);

-- ============================================================================
-- 13. CAMPAIGNS TABLE
-- Public awareness drives and donation campaigns published by System Admin
-- ============================================================================
CREATE TABLE campaigns (
  campaign_id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  campaign_type ENUM('Food Waste Awareness', 'Food Donation Drive', 'Community Awareness', 'Food Safety Awareness') NOT NULL,
  message TEXT NOT NULL,
  target_audience ENUM('All Users', 'Donors Only', 'Volunteers Only') NOT NULL DEFAULT 'All Users',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  call_to_action VARCHAR(150) DEFAULT NULL,
  status ENUM('Draft', 'Published', 'Completed') NOT NULL DEFAULT 'Draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_campaigns_admin FOREIGN KEY (admin_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_campaigns_status (status)
);


-- ============================================================================
-- 14. FOOD SAFETY GUIDELINES TABLE
-- Dynamic management of food safety policies, status definitions, and rules
-- ============================================================================
CREATE TABLE IF NOT EXISTS food_safety_guidelines (
  guideline_id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  category ENUM('Safety Status', 'Handling Guideline', 'Compliance & Policy', 'Footer Notice') NOT NULL DEFAULT 'Handling Guideline',
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  bullet_points TEXT DEFAULT NULL,
  display_order INT NOT NULL DEFAULT 0,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_guideline_order CHECK (display_order >= 0),
  CONSTRAINT fk_guidelines_admin FOREIGN KEY (admin_id) REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_guideline_category (category),
  INDEX idx_guideline_status (status),
  INDEX idx_category_status (category, status)
);

