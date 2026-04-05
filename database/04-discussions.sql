-- ===== DISCUSSIONS FORUM SYSTEM =====
-- Forum for student discussions on assignments and class topics

-- Discussion threads/topics
CREATE TABLE discussion_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    course_id INT NOT NULL,
    created_by INT NOT NULL,
    
    -- Thread details
    title VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Thread settings
    is_pinned BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE, -- Instructor can lock discussion
    allow_anonymous BOOLEAN DEFAULT FALSE,
    
    -- Status
    is_published BOOLEAN DEFAULT TRUE,
    
    -- Stats
    reply_count INT DEFAULT 0,
    last_activity TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_course (course_id),
    INDEX idx_created_by (created_by),
    INDEX idx_pinned (is_pinned),
    INDEX idx_locked (is_locked),
    INDEX idx_last_activity (last_activity)
);

-- Discussion posts/replies
CREATE TABLE discussion_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    assignment_id INT NOT NULL,
    posted_by INT NOT NULL,
    parent_post_id INT NULL, -- For nested replies
    
    -- Post content
    content TEXT NOT NULL,
    
    -- Author anonymity
    is_anonymous BOOLEAN DEFAULT FALSE,
    
    -- Moderation
    is_flagged BOOLEAN DEFAULT FALSE,
    flag_reason VARCHAR(255),
    is_instructor_response BOOLEAN DEFAULT FALSE,
    
    -- Status
    is_published BOOLEAN DEFAULT TRUE,
    
    -- Engagement
    likes_count INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    
    FOREIGN KEY (thread_id) REFERENCES discussion_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_post_id) REFERENCES discussion_posts(id) ON DELETE CASCADE,
    INDEX idx_thread (thread_id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_posted_by (posted_by),
    INDEX idx_parent (parent_post_id),
    INDEX idx_created_at (created_at)
);

-- Student engagement with discussions (likes, marking helpful)
CREATE TABLE discussion_engagement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Engagement type
    engagement_type ENUM('like', 'mark_helpful', 'report') DEFAULT 'like',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES discussion_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_engagement (post_id, user_id, engagement_type),
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
);

-- Notifications for discussion activity
CREATE TABLE discussion_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    thread_id INT NOT NULL,
    post_id INT,
    
    -- Notification type
    notification_type ENUM('new_reply', 'mention', 'flagged', 'admin_message') DEFAULT 'new_reply',
    
    -- Message
    message TEXT,
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES discussion_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES discussion_posts(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_thread (thread_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Student subscriptions to discussion threads
CREATE TABLE discussion_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    thread_id INT NOT NULL,
    
    -- Subscription type
    subscription_type ENUM('all_replies', 'instructor_only', 'none') DEFAULT 'all_replies',
    
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES discussion_threads(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subscription (user_id, thread_id),
    INDEX idx_user (user_id),
    INDEX idx_thread (thread_id)
);
