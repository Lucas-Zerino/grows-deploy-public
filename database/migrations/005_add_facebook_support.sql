-- facebook_apps table
CREATE TABLE IF NOT EXISTS facebook_apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    app_id VARCHAR(255) NOT NULL,
    app_secret TEXT NOT NULL,
    page_id VARCHAR(255) NULL,
    page_access_token TEXT NULL,
    webhook_verify_token VARCHAR(255) NULL,
    status ENUM('pending','active','disabled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_facebook_apps_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_facebook_apps_company (company_id),
    INDEX idx_facebook_apps_status (status)
);


