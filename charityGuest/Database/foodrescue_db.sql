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





-- ============================================================================
-- FOODRESCUE TEST / DEMO SEED DATA
-- Run this AFTER importing the project's schema.
-- Intended for a fresh TEST database only. It inserts predictable IDs 1..N.
-- ============================================================================

USE foodrescue_db;

START TRANSACTION;

-- --------------------------------------------------------------------------
-- 1. TEST USERS
-- Admin password: DemoAdmin@123!
-- Hash generated with PHP password_hash(..., PASSWORD_DEFAULT)
-- --------------------------------------------------------------------------
INSERT INTO users
(user_id, full_name, email, password_hash, phone, role, organization_type,
 reg_nid_number, nid_image_path, address, account_status)
VALUES
(1, 'System Administrator', 'admin@foodrescue.test',
 '$2y$12$REb0kFgn2qh71lWMwDjXWO9GQYvQexF7V9LNFD0tZObA1BWtZZ9U2',
 '01700000001', 'Admin', 'Other', NULL, NULL,
 'FoodRescue Test Office, Dhaka', 'Active'),
(2, 'Rahim Restaurant', 'donor1@foodrescue.test',
 '$2y$12$REb0kFgn2qh71lWMwDjXWO9GQYvQexF7V9LNFD0tZObA1BWtZZ9U2',
 '01700000002', 'Donor', 'Restaurant', 'TEST-NID-1002', NULL,
 '12 Test Road, Dhanmondi, Dhaka', 'Verified'),
(3, 'Green Basket Bakery', 'donor2@foodrescue.test',
 '$2y$12$REb0kFgn2qh71lWMwDjXWO9GQYvQexF7V9LNFD0tZObA1BWtZZ9U2',
 '01700000003', 'Donor', 'Bakery', 'TEST-NID-1003', NULL,
 '45 Demo Avenue, Mirpur, Dhaka', 'Active'),
(4, 'Hope Community Foundation', 'charity1@foodrescue.test',
 '$2y$12$REb0kFgn2qh71lWMwDjXWO9GQYvQexF7V9LNFD0tZObA1BWtZZ9U2',
 '01700000004', 'Charity', 'Foundation', 'TEST-NID-2001', NULL,
 '20 Community Lane, Mohammadpur, Dhaka', 'Verified'),
(5, 'Care & Share NGO', 'charity2@foodrescue.test',
 '$2y$12$REb0kFgn2qh71lWMwDjXWO9GQYvQexF7V9LNFD0tZObA1BWtZZ9U2',
 '01700000005', 'Charity', 'Charity NGO', 'TEST-NID-2002', NULL,
 '77 Welfare Street, Uttara, Dhaka', 'Active'),
(6, 'Tanvir Ahmed', 'volunteer1@foodrescue.test',
 '$2y$12$REb0kFgn2qh71lWMwDjXWO9GQYvQexF7V9LNFD0tZObA1BWtZZ9U2',
 '01700000006', 'Volunteer', 'Individual', 'TEST-NID-3001', NULL,
 '8 Volunteer Road, Banani, Dhaka', 'Active'),
(7, 'Nusrat Jahan', 'volunteer2@foodrescue.test',
 '$2y$12$REb0kFgn2qh71lWMwDjXWO9GQYvQexF7V9LNFD0tZObA1BWtZZ9U2',
 '01700000007', 'Volunteer', 'Individual', 'TEST-NID-3002', NULL,
 '19 Rider Lane, Badda, Dhaka', 'Pending');

-- --------------------------------------------------------------------------
-- 2. FOOD CATEGORIES
-- --------------------------------------------------------------------------
INSERT INTO food_categories (category_id, category_name, description, status) VALUES
(1, 'Cooked Meals', 'Prepared meals suitable for same-day distribution.', 'Active'),
(2, 'Bakery Items', 'Bread, buns, pastries and other bakery surplus.', 'Active'),
(3, 'Fresh Produce', 'Fruits and vegetables that remain safe for donation.', 'Active'),
(4, 'Packaged Food', 'Sealed packaged foods with valid expiry dates.', 'Active'),
(5, 'Rice & Grains', 'Cooked or packaged rice and grain-based foods.', 'Active'),
(6, 'Beverages', 'Non-alcoholic drinks and sealed beverages.', 'Active');

-- --------------------------------------------------------------------------
-- 3. FOOD DONATIONS
-- --------------------------------------------------------------------------
INSERT INTO food_donations
(donation_id, donor_id, category_id, food_name, quantity, quantity_unit,
 expiry_date, description, food_safety_info, pickup_date, preferred_time,
 pickup_address, image_path, status)
VALUES
(1, 2, 1, 'Vegetable Rice Boxes', 30, 'boxes',
 '2026-10-05 21:00:00', 'Freshly prepared surplus lunch boxes.',
 'Prepared today; keep covered and consume promptly.', '2026-10-05', '18:00-19:00',
 '12 Test Road, Dhanmondi, Dhaka', NULL, 'Available'),
(2, 2, 1, 'Chicken Meal Boxes', 20, 'boxes',
 '2026-10-04 22:00:00', 'Dinner meal boxes from a restaurant event.',
 'Prepared under normal kitchen hygiene procedures.', '2026-10-04', '19:00-20:00',
 '12 Test Road, Dhanmondi, Dhaka', NULL, 'Claimed'),
(3, 3, 2, 'Assorted Bread', 50, 'packs',
 '2026-10-05 18:00:00', 'Unsold bread and buns.',
 'Sealed after cooling; inspect packaging before distribution.', '2026-10-04', '16:00-17:00',
 '45 Demo Avenue, Mirpur, Dhaka', NULL, 'Picked Up'),
(4, 2, 3, 'Mixed Fresh Vegetables', 40, 'kg',
 '2026-10-07 12:00:00', 'Surplus vegetables from morning stock.',
 'Washed and stored in clean crates.', '2026-10-06', '10:00-11:00',
 '12 Test Road, Dhanmondi, Dhaka', NULL, 'Completed'),
(5, 3, 4, 'Sealed Biscuits', 100, 'packs',
 '2027-02-15 23:59:00', 'Sealed biscuit packets.',
 'Factory-sealed packaging; check expiry before distribution.', '2026-10-06', '15:00-16:00',
 '45 Demo Avenue, Mirpur, Dhaka', NULL, 'Completed'),
(6, 2, 5, 'Cooked Khichuri', 25, 'kg',
 '2026-10-03 20:00:00', 'Cooked khichuri from a cancelled event.',
 'Keep refrigerated; donation window has passed.', '2026-10-03', '17:00-18:00',
 '12 Test Road, Dhanmondi, Dhaka', NULL, 'Expired'),
(7, 3, 6, 'Bottled Water', 60, 'bottles',
 '2027-05-01 23:59:00', 'Unopened bottled drinking water.',
 'Factory-sealed bottles.', '2026-10-08', '11:00-12:00',
 '45 Demo Avenue, Mirpur, Dhaka', NULL, 'Available'),
(8, 2, 2, 'Pastry Assortment', 15, 'boxes',
 '2026-10-04 15:00:00', 'Mixed pastries remaining after a meeting.',
 'Keep chilled where required; use before expiry.', '2026-10-04', '13:00-14:00',
 '12 Test Road, Dhanmondi, Dhaka', NULL, 'Cancelled');

-- --------------------------------------------------------------------------
-- 4. FOOD CLAIMS
-- --------------------------------------------------------------------------
INSERT INTO food_claims
(claim_id, donation_id, charity_id, quantity_claimed, claim_date, status)
VALUES
(1, 2, 4, 20, '2026-10-04 09:30:00', 'Scheduled'),
(2, 3, 5, 50, '2026-10-04 11:15:00', 'In Transit'),
(3, 4, 4, 40, '2026-10-03 14:20:00', 'Completed'),
(4, 5, 5, 80, '2026-10-03 16:45:00', 'Completed'),
(5, 6, 4, 25, '2026-10-03 12:10:00', 'Cancelled');

-- --------------------------------------------------------------------------
-- 5. PICKUP / DELIVERY RECORDS
-- --------------------------------------------------------------------------
INSERT INTO pickup_deliveries
(delivery_id, claim_id, volunteer_id, pickup_address, delivery_address,
 pickup_date, scheduled_time, special_instructions, delivery_status,
 assigned_at, picked_up_at, in_transit_at, delivered_at, completed_at)
VALUES
(1, 1, 6,
 '12 Test Road, Dhanmondi, Dhaka', '20 Community Lane, Mohammadpur, Dhaka',
 '2026-10-04', '19:00-20:00', 'Call charity reception before arrival.', 'Accepted',
 '2026-10-04 17:30:00', NULL, NULL, NULL, NULL),
(2, 2, 6,
 '45 Demo Avenue, Mirpur, Dhaka', '77 Welfare Street, Uttara, Dhaka',
 '2026-10-04', '16:00-17:00', 'Keep bread boxes dry during transport.', 'In Transit',
 '2026-10-04 14:30:00', '2026-10-04 15:45:00', '2026-10-04 16:05:00', NULL, NULL),
(3, 3, 7,
 '12 Test Road, Dhanmondi, Dhaka', '20 Community Lane, Mohammadpur, Dhaka',
 '2026-10-03', '10:00-11:00', 'Deliver to community kitchen.', 'Completed',
 '2026-10-03 08:30:00', '2026-10-03 09:50:00', '2026-10-03 10:05:00', '2026-10-03 10:40:00', '2026-10-03 11:00:00'),
(4, 4, 6,
 '45 Demo Avenue, Mirpur, Dhaka', '77 Welfare Street, Uttara, Dhaka',
 '2026-10-03', '15:00-16:00', 'Leave receipt with charity coordinator.', 'Delivered',
 '2026-10-03 13:20:00', '2026-10-03 14:50:00', '2026-10-03 15:10:00', '2026-10-03 15:55:00', NULL),
(5, 5, NULL,
 '12 Test Road, Dhanmondi, Dhaka', '20 Community Lane, Mohammadpur, Dhaka',
 '2026-10-03', '17:00-18:00', 'Cancelled because donation expired.', 'Cancelled',
 NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------------------------
-- 6. OTP VERIFICATIONS
-- --------------------------------------------------------------------------
INSERT INTO otp_verifications
(otp_id, delivery_id, otp_code, expiry_time, status, generated_at, verified_at)
VALUES
(1, 1, '482731', '2026-10-04 20:30:00', 'Pending', '2026-10-04 18:00:00', NULL),
(2, 2, '615204', '2026-10-04 18:30:00', 'Pending', '2026-10-04 15:30:00', NULL),
(3, 3, '903118', '2026-10-03 12:00:00', 'Verified', '2026-10-03 09:00:00', '2026-10-03 10:42:00'),
(4, 4, '771526', '2026-10-03 17:00:00', 'Verified', '2026-10-03 14:00:00', '2026-10-03 15:57:00');

-- --------------------------------------------------------------------------
-- 7. FOOD DISTRIBUTIONS
-- --------------------------------------------------------------------------
INSERT INTO food_distributions
(distribution_id, claim_id, food_item_name, quantity_distributed, quantity_unit,
 quality, people_served, remarks, distributed_at)
VALUES
(1, 3, 'Mixed Fresh Vegetables', 40, 'kg', 'Good', 85,
 'Distributed through the community kitchen.', '2026-10-03 13:00:00'),
(2, 4, 'Sealed Biscuits', 80, 'packs', 'Excellent', 80,
 'One pack per beneficiary.', '2026-10-03 17:30:00');

-- --------------------------------------------------------------------------
-- 8. ISSUE REPORTS
-- --------------------------------------------------------------------------
INSERT INTO issue_reports
(issue_id, reporter_id, report_type, related_donation_id, related_delivery_id,
 description, evidence_file, status, resolution_details)
VALUES
(1, 5, 'Delivery Issue', 3, 2,
 'Traffic caused a delay during the Mirpur to Uttara route.', NULL,
 'Under Review', NULL),
(2, 4, 'Food Quality Issue', 5, 4,
 'Two biscuit packets had damaged outer packaging.', NULL,
 'Resolved', 'Damaged packets were separated before distribution.'),
(3, 2, 'Pickup Issue', 4, 3,
 'Volunteer arrived earlier than the agreed pickup window.', NULL,
 'Closed', 'Timing guidance was shared with the volunteer team.'),
(4, 5, 'Food Safety Concern', 6, 5,
 'Expired cooked food should not be collected.', NULL,
 'Resolved', 'Donation was cancelled and marked expired.');

-- --------------------------------------------------------------------------
-- 9. DELAY REPORTS
-- --------------------------------------------------------------------------
INSERT INTO delay_reports
(delay_id, delivery_id, volunteer_id, delay_reason, estimated_delay_minutes,
 additional_comments, reported_at)
VALUES
(1, 2, 6, 'Traffic / Road Closure', 25,
 'Heavy traffic near the main intersection.', '2026-10-04 16:15:00'),
(2, 4, 6, 'Restaurant Delay', 15,
 'Loading took longer than expected.', '2026-10-03 14:35:00');

-- --------------------------------------------------------------------------
-- 10. MESSAGES
-- --------------------------------------------------------------------------
INSERT INTO messages
(message_id, sender_id, receiver_id, delivery_id, message_text, is_read, sent_at)
VALUES
(1, 4, 6, 1, 'Please call the community kitchen when you are 10 minutes away.', 1, '2026-10-04 17:45:00'),
(2, 6, 4, 1, 'Understood. I will call before arrival.', 1, '2026-10-04 17:50:00'),
(3, 2, 6, 2, 'The bread boxes are ready at the front desk.', 1, '2026-10-04 15:20:00'),
(4, 6, 2, 2, 'Thank you. I have picked them up.', 1, '2026-10-04 15:48:00'),
(5, 1, 4, NULL, 'Welcome to the FoodRescue test environment.', 0, '2026-10-04 09:00:00');

-- --------------------------------------------------------------------------
-- 11. RECEIPTS
-- --------------------------------------------------------------------------
INSERT INTO receipts (receipt_id, claim_id, generated_at) VALUES
('FR-2026-00001', 3, '2026-10-03 18:00:00'),
('FR-2026-00002', 4, '2026-10-03 18:15:00');

-- --------------------------------------------------------------------------
-- 12. BROADCASTS
-- --------------------------------------------------------------------------
INSERT INTO broadcasts
(broadcast_id, admin_id, title, message, audience, status, scheduled_at)
VALUES
(1, 1, 'Test Environment Notice',
 'This is a dummy broadcast for testing the admin notification system.',
 'All Users', 'Sent', NULL),
(2, 1, 'Volunteer Delivery Reminder',
 'Please confirm pickup and delivery status after each test run.',
 'Volunteers Only', 'Scheduled', '2026-10-05 09:00:00'),
(3, 1, 'Donation Test Notice',
 'Use only dummy food records while testing the application.',
 'Donors Only', 'Draft', NULL);

-- --------------------------------------------------------------------------
-- 13. CAMPAIGNS
-- --------------------------------------------------------------------------
INSERT INTO campaigns
(campaign_id, admin_id, title, campaign_type, message, target_audience,
 start_date, end_date, call_to_action, status)
VALUES
(1, 1, 'Reduce Food Waste', 'Food Waste Awareness',
 'Test campaign encouraging responsible surplus food donation.',
 'All Users', '2026-10-01', '2026-10-31', 'Donate Surplus Food', 'Published'),
(2, 1, 'Community Food Drive', 'Food Donation Drive',
 'Dummy campaign for testing donation-drive management.',
 'Donors Only', '2026-10-10', '2026-11-10', 'Create Donation', 'Draft'),
(3, 1, 'Safe Food Handling Week', 'Food Safety Awareness',
 'Test campaign for communicating food handling practices.',
 'All Users', '2026-10-15', '2026-10-22', 'Read Guidelines', 'Completed');

-- --------------------------------------------------------------------------
-- 14. FOOD SAFETY GUIDELINES
-- --------------------------------------------------------------------------
INSERT INTO food_safety_guidelines
(guideline_id, admin_id, category, title, description, bullet_points,
 display_order, status)
VALUES
(1, 1, 'Safety Status', 'Safe for Donation',
 'Food should be fresh, properly handled, and within its safe consumption window.',
 'Freshly prepared|Properly covered|Within safe time limit', 1, 'Active'),
(2, 1, 'Handling Guideline', 'Packaging and Storage',
 'Keep donated food protected from contamination during storage and transport.',
 'Use clean containers|Keep food covered|Separate raw and cooked food', 2, 'Active'),
(3, 1, 'Compliance & Policy', 'Expiry Policy',
 'Expired food must not be listed for donation or collected for distribution.',
 'Check expiry before pickup|Cancel expired donations|Report safety concerns', 3, 'Active'),
(4, 1, 'Footer Notice', 'Test Environment Notice',
 'All records in this environment are dummy records for software testing.',
 'Do not use real personal data|Do not distribute test food records', 4, 'Active');

COMMIT;

-- ============================================================================
-- ADMIN LOGIN TEST DATA
-- Email:    admin@foodrescue.test
-- Password: DemoAdmin@123!
-- Role:     Admin
-- ============================================================================

-- Optional verification query:
SELECT user_id, full_name, email, role, account_status
FROM users
WHERE email = 'admin@foodrescue.test';
