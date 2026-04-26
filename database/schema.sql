-- Zeno CRM Database Schema
-- MySQL 8.0

CREATE DATABASE IF NOT EXISTS zeno_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zeno_crm;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    team        VARCHAR(100),
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    last_login  DATETIME,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(150) NOT NULL,
    email            VARCHAR(150),
    phone            VARCHAR(50),
    industry         VARCHAR(100),
    type             VARCHAR(50),
    website          VARCHAR(255),
    billing_address  TEXT,
    shipping_address TEXT,
    description      TEXT,
    created_by       INT,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contacts table
CREATE TABLE IF NOT EXISTS contacts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    account_id  INT,
    first_name  VARCHAR(75) NOT NULL,
    last_name   VARCHAR(75) NOT NULL,
    email       VARCHAR(150),
    phone       VARCHAR(50),
    title       VARCHAR(100),
    department  VARCHAR(100),
    address     TEXT,
    birthday    DATE,
    description TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (first_name, last_name),
    INDEX idx_email (email),
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leads table
CREATE TABLE IF NOT EXISTS leads (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    first_name  VARCHAR(75) NOT NULL,
    last_name   VARCHAR(75) NOT NULL,
    email       VARCHAR(150),
    phone       VARCHAR(50),
    company     VARCHAR(150),
    title       VARCHAR(100),
    status      ENUM('new','in_process','assigned','recycled','converted','dead') NOT NULL DEFAULT 'new',
    source      VARCHAR(100),
    assigned_to INT,
    description TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_name (first_name, last_name),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opportunities table
CREATE TABLE IF NOT EXISTS opportunities (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    account_id  INT,
    contact_id  INT,
    stage       ENUM('prospecting','qualification','proposal','negotiation','closed_won','closed_lost') NOT NULL DEFAULT 'prospecting',
    amount      DECIMAL(15,2),
    probability TINYINT UNSIGNED DEFAULT 0,
    close_date  DATE,
    lead_source VARCHAR(100),
    description TEXT,
    assigned_to INT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stage (stage),
    INDEX idx_close_date (close_date),
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Meetings table
CREATE TABLE IF NOT EXISTS meetings (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(200) NOT NULL,
    parent_type      VARCHAR(50),
    parent_id        INT,
    status           ENUM('planned','held','not_held') NOT NULL DEFAULT 'planned',
    start_date       DATETIME,
    end_date         DATETIME,
    duration_hours   TINYINT UNSIGNED DEFAULT 1,
    duration_minutes TINYINT UNSIGNED DEFAULT 0,
    description      TEXT,
    assigned_to      INT,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    status      ENUM('not_started','in_progress','completed','deferred') NOT NULL DEFAULT 'not_started',
    priority    ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    start_date  DATE,
    due_date    DATE,
    contact_id  INT,
    assigned_to INT,
    description TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date),
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- SEED DATA
-- ================================================

-- Admin user (password: Admin@123)
-- Hash generated with bcrypt for 'Admin@123' ($2y$ is PHP-compatible prefix)
INSERT INTO users (name, email, password, role, team, is_active, created_at) VALUES
('Admin User', 'admin@zenocrm.com', '$2y$12$1d/J8cxBt2mNovTTaSJljejImB/zQK7a6D74/b1mSRI7gcEzXA1Ge', 'admin', 'Management', 1, NOW()),
('Sarah Connor', 'sarah.connor@zenocrm.com', '$2y$12$1d/J8cxBt2mNovTTaSJljejImB/zQK7a6D74/b1mSRI7gcEzXA1Ge', 'user', 'Sales', 1, NOW()),
('Tom Brady', 'tom.brady@zenocrm.com', '$2y$12$1d/J8cxBt2mNovTTaSJljejImB/zQK7a6D74/b1mSRI7gcEzXA1Ge', 'user', 'Sales', 1, NOW()),
('Lisa Simpson', 'lisa.simpson@zenocrm.com', '$2y$12$1d/J8cxBt2mNovTTaSJljejImB/zQK7a6D74/b1mSRI7gcEzXA1Ge', 'user', 'Support', 0, NOW());
-- Note: PHP password_verify() accepts both $2y$ and $2b$ bcrypt prefixes

-- Sample Accounts
INSERT INTO accounts (name, email, phone, industry, type, website, billing_address, created_by, created_at) VALUES
('Acme Corporation', 'info@acme.com', '+1-555-0100', 'Technology', 'Customer', 'https://acme.com', '123 Main St, New York, NY 10001', 1, '2024-01-15 09:00:00'),
('Globex Industries', 'contact@globex.com', '+1-555-0200', 'Manufacturing', 'Partner', 'https://globex.com', '456 Oak Ave, Chicago, IL 60601', 1, '2024-01-20 10:00:00'),
('Initech Solutions', 'hello@initech.com', '+1-555-0300', 'Finance', 'Prospect', 'https://initech.com', '789 Pine Rd, Austin, TX 78701', 1, '2024-02-01 11:00:00'),
('Umbrella Corp', 'info@umbrella.com', '+1-555-0400', 'Healthcare', 'Customer', 'https://umbrella.com', '321 Elm St, Seattle, WA 98101', 1, '2024-02-10 09:30:00'),
('Massive Dynamic', 'contact@massive.com', '+1-555-0500', 'Research', 'Prospect', 'https://massive.com', '654 Cedar Blvd, Boston, MA 02101', 1, '2024-02-15 14:00:00');

-- Sample Contacts
INSERT INTO contacts (account_id, first_name, last_name, email, phone, title, department, created_at) VALUES
(1, 'John', 'Smith', 'john.smith@acme.com', '+1-555-1001', 'CEO', 'Executive', '2024-01-15 09:30:00'),
(1, 'Jane', 'Doe', 'jane.doe@acme.com', '+1-555-1002', 'CTO', 'Technology', '2024-01-16 10:00:00'),
(2, 'Bob', 'Johnson', 'bob.j@globex.com', '+1-555-1003', 'VP Sales', 'Sales', '2024-01-22 11:00:00'),
(2, 'Alice', 'Williams', 'alice.w@globex.com', '+1-555-1004', 'Director', 'Operations', '2024-01-23 09:00:00'),
(3, 'Charlie', 'Brown', 'charlie.b@initech.com', '+1-555-1005', 'CFO', 'Finance', '2024-02-02 10:30:00'),
(3, 'Diana', 'Prince', 'diana.p@initech.com', '+1-555-1006', 'Manager', 'HR', '2024-02-03 11:00:00'),
(4, 'Edward', 'Norton', 'edward.n@umbrella.com', '+1-555-1007', 'President', 'Executive', '2024-02-11 09:00:00'),
(4, 'Fiona', 'Green', 'fiona.g@umbrella.com', '+1-555-1008', 'Head of R&D', 'Research', '2024-02-12 10:00:00'),
(5, 'George', 'Hall', 'george.h@massive.com', '+1-555-1009', 'Director', 'Science', '2024-02-16 11:30:00'),
(5, 'Helen', 'Troy', 'helen.t@massive.com', '+1-555-1010', 'Analyst', 'Analytics', '2024-02-17 09:45:00');

-- Sample Leads
INSERT INTO leads (first_name, last_name, email, phone, company, title, status, source, assigned_to, created_at) VALUES
('Michael', 'Scott', 'm.scott@dunder.com', '+1-555-2001', 'Dunder Mifflin', 'Regional Manager', 'new', 'Web', 1, '2024-03-01 09:00:00'),
('Dwight', 'Schrute', 'd.schrute@schrutefarm.com', '+1-555-2002', 'Schrute Farms', 'Owner', 'in_process', 'Referral', 1, '2024-03-02 10:00:00'),
('Jim', 'Halpert', 'j.halpert@athleap.com', '+1-555-2003', 'Athleap', 'Co-Founder', 'assigned', 'Cold Call', 2, '2024-03-03 11:00:00'),
('Pam', 'Beesly', 'p.beesly@michaelscott.com', '+1-555-2004', 'Michael Scott Paper', 'Sales Rep', 'converted', 'Email', 1, '2024-03-04 09:30:00'),
('Ryan', 'Howard', 'r.howard@wuphf.com', '+1-555-2005', 'WUPHF', 'CEO', 'recycled', 'Social Media', 2, '2024-03-05 14:00:00'),
('Angela', 'Martin', 'a.martin@accounting.com', '+1-555-2006', 'Martin Accounting', 'Manager', 'dead', 'Trade Show', 1, '2024-03-06 10:00:00'),
('Kevin', 'Malone', 'k.malone@kevinschili.com', '+1-555-2007', "Kevin's Chili", 'Chef/Owner', 'new', 'Web', 3, '2024-03-07 11:30:00'),
('Oscar', 'Martinez', 'o.martinez@finance.com', '+1-555-2008', 'Martinez Finance', 'Analyst', 'in_process', 'Referral', 1, '2024-03-08 09:00:00');

-- Sample Opportunities
INSERT INTO opportunities (name, account_id, contact_id, stage, amount, probability, close_date, lead_source, assigned_to, created_at) VALUES
('Acme Enterprise Deal', 1, 1, 'prospecting', 75000.00, 20, '2024-06-30', 'Web', 1, '2024-03-01 09:00:00'),
('Globex Software License', 2, 3, 'qualification', 45000.00, 40, '2024-05-31', 'Referral', 2, '2024-03-05 10:00:00'),
('Initech Cloud Migration', 3, 5, 'proposal', 120000.00, 60, '2024-07-15', 'Cold Call', 1, '2024-03-08 11:00:00'),
('Umbrella Analytics Suite', 4, 7, 'negotiation', 95000.00, 80, '2024-04-30', 'Trade Show', 1, '2024-03-10 09:30:00'),
('Massive Dynamic Research Platform', 5, 9, 'closed_won', 200000.00, 100, '2024-03-15', 'Referral', 1, '2024-02-20 14:00:00'),
('Acme CRM Integration', 1, 2, 'qualification', 35000.00, 35, '2024-08-01', 'Email', 2, '2024-03-12 10:00:00'),
('Globex HR System', 2, 4, 'prospecting', 55000.00, 15, '2024-09-30', 'Web', 3, '2024-03-15 11:00:00'),
('Initech BI Dashboard', 3, 6, 'proposal', 68000.00, 55, '2024-06-15', 'Social Media', 1, '2024-03-18 09:00:00');

-- Sample Meetings
INSERT INTO meetings (name, parent_type, parent_id, status, start_date, end_date, duration_hours, duration_minutes, description, assigned_to, created_at) VALUES
('Acme Quarterly Review', 'Account', 1, 'planned', '2024-04-15 10:00:00', '2024-04-15 11:00:00', 1, 0, 'Q1 review meeting with key stakeholders', 1, '2024-03-20 09:00:00'),
('Product Demo - Globex', 'Opportunity', 2, 'held', '2024-03-10 14:00:00', '2024-03-10 15:30:00', 1, 30, 'Product demonstration for software license deal', 1, '2024-03-05 10:00:00'),
('Contract Negotiation', 'Opportunity', 4, 'planned', '2024-04-20 09:00:00', '2024-04-20 10:00:00', 1, 0, 'Final contract terms discussion', 1, '2024-03-15 11:00:00'),
('Kickoff Meeting', 'Account', 5, 'held', '2024-03-16 11:00:00', '2024-03-16 12:00:00', 1, 0, 'Project kickoff for Massive Dynamic', 1, '2024-03-10 09:00:00'),
('Technical Assessment', 'Lead', 3, 'not_held', '2024-04-05 15:00:00', '2024-04-05 16:00:00', 1, 0, 'Technical requirements gathering session', 2, '2024-03-18 14:00:00');

-- Sample Tasks
INSERT INTO tasks (name, status, priority, start_date, due_date, contact_id, assigned_to, description, created_at) VALUES
('Follow up with Acme CEO', 'not_started', 'high', '2024-04-01', '2024-04-05', 1, 1, 'Schedule follow-up call to discuss enterprise deal', '2024-03-25 09:00:00'),
('Send proposal to Initech', 'in_progress', 'urgent', '2024-03-28', '2024-04-02', 5, 1, 'Prepare and send cloud migration proposal document', '2024-03-25 10:00:00'),
('Update Globex contact info', 'completed', 'low', '2024-03-20', '2024-03-22', 3, 2, 'Update CRM records with latest contact information', '2024-03-18 11:00:00'),
('Prepare quarterly report', 'in_progress', 'medium', '2024-03-25', '2024-04-10', NULL, 1, 'Compile Q1 sales data and performance metrics', '2024-03-22 09:30:00'),
('Demo Massive Dynamic platform', 'not_started', 'high', '2024-04-08', '2024-04-12', 9, 1, 'Prepare demo environment for research platform', '2024-03-26 14:00:00');
