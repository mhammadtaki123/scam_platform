USE scam_db;

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT
);

-- Insert default settings if not exists
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('weight_rating', '0.4'),
('weight_reports', '0.3'),
('weight_ai', '0.3');

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
  email VARCHAR(100) NOT NULL,
  token VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  INDEX(email)
);

-- Shop Ownership
-- Check if column exists before adding (MySQL trick: unfortunately no CREATE IF NOT EXISTS for columns in older versions, but we'll run a safe ALTER)
-- Since we are running this fresh, we just alter. If it fails, it might already exist.
ALTER TABLE shops ADD COLUMN IF NOT EXISTS owner_id INT DEFAULT NULL;
ALTER TABLE shops ADD FOREIGN KEY IF NOT EXISTS (owner_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- Shop Claims Table
CREATE TABLE IF NOT EXISTS shop_claims (
  claim_id INT AUTO_INCREMENT PRIMARY KEY,
  shop_id INT NOT NULL,
  user_id INT NOT NULL,
  evidence TEXT,
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Shop Responses Table
CREATE TABLE IF NOT EXISTS shop_responses (
  response_id INT AUTO_INCREMENT PRIMARY KEY,
  review_id INT NOT NULL,
  owner_id INT NOT NULL,
  response_text TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
);
