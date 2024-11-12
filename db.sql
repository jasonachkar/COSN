-- 1. Create the database
CREATE DATABASE IF NOT EXISTS COSN;
USE COSN;

-- 2. Create tables

-- Members table
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('junior', 'senior', 'admin') DEFAULT 'junior',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
);

-- Groups table
CREATE TABLE IF NOT EXISTS `groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    owner_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES members(id) ON DELETE SET NULL
);

-- Group members table
CREATE TABLE IF NOT EXISTS group_members (
    group_id INT,
    member_id INT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, member_id),
    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT,
    author_id INT,
    group_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    commenter_id INT,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (commenter_id) REFERENCES members(id) ON DELETE SET NULL
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    recipient_id INT,
    message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Friend requests table
CREATE TABLE IF NOT EXISTS friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT,
    recipient_id INT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Notifications table (optional)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT,
    type ENUM('message', 'comment', 'friend_request', 'group_invite'),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seen BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (recipient_id) REFERENCES members(id) ON DELETE CASCADE
);

-- 3. Insert sample data (optional)

INSERT INTO members (username, password, email, role, status)
VALUES ('admin', 'admin_hashed_password', 'admin@example.com', 'admin', 'active'),
       ('user1', 'user1_hashed_password', 'user1@example.com', 'junior', 'active'),
       ('user2', 'user2_hashed_password', 'user2@example.com', 'junior', 'active');

INSERT INTO `groups` (name, description, owner_id)
VALUES ('Photography Club', 'A group for photography enthusiasts', 1);

INSERT INTO group_members (group_id, member_id)
VALUES (1, 2), (1, 3);

INSERT INTO posts (title, content, author_id, group_id)
VALUES ('Welcome to the Photography Club', 'This is a welcome post for all members.', 1, 1);

INSERT INTO comments (post_id, commenter_id, content)
VALUES (1, 2, 'Thank you for the welcome message!');

INSERT INTO messages (sender_id, recipient_id, message)
VALUES (2, 3, 'Hello, welcome to the club!');
