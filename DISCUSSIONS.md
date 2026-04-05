# Discussion Forum System Documentation

## Overview

The Discussion Forum System is a comprehensive module that enables students and instructors to engage in threaded discussions for assignments, particularly for discussion-type assignments. The system supports nested conversations, moderation tools, engagement tracking, and notification management.

## Features

### Student Features
- **Create Discussion Threads**: Start new conversation topics within an assignment
- **Post Replies**: Respond to existing threads with threaded conversations
- **Thread Subscription**: Get notified when new replies are posted
  - All replies
  - Instructor replies only
  - No notifications
- **Anonymous Posting**: Option to post anonymously if enabled by instructor (thread-level setting)
- **Like Posts**: React to helpful or insightful posts
- **Thread Insights**: View thread statistics including reply counts and last activity

### Instructor Features
- **Moderate Discussions**: Pin important threads to top of discussion list
- **Lock Threads**: Prevent further replies to a discussion (e.g., after topic is concluded)
- **Monitor Activity**: View all posts and thread statistics from admin dashboard
- **Manage Flagged Content**: Review and remove inappropriate posts
- **Engagement Analytics**: See metrics on discussion participation across course

## Database Schema

### discussion_threads
Stores the main discussion thread (initial post/topic).

```sql
- id: Primary key
- assignment_id: Link to assignment
- course_id: Link to course
- created_by: User who started thread
- title: Thread topic title
- description: Optional detailed description
- is_pinned: Boolean to pin important threads
- is_locked: Boolean to prevent new replies
- allow_anonymous: Allow anonymous posts in this thread
- is_published: Thread visibility flag
- reply_count: Running count of posts
- last_activity: Last post timestamp
- created_at, updated_at: Timestamps
```

### discussion_posts
Individual posts/replies within a thread.

```sql
- id: Primary key
- thread_id: Parent thread
- assignment_id: Linked assignment
- posted_by: User who posted
- parent_post_id: For nested replies (future feature)
- content: Post text
- is_anonymous: Posted anonymously
- is_flagged: Flagged for moderation
- flag_reason: Why flagged
- is_instructor_response: Mark important instructor responses
- is_published: Post visibility
- likes_count: Aggregate likes
- created_at, updated_at, edited_at: Timestamps
```

### discussion_engagement
Tracks user interactions with posts (likes, helpful marks, reports).

```sql
- id: Primary key
- post_id: Post being engaged with
- user_id: User performing action
- engagement_type: 'like', 'mark_helpful', 'report'
- created_at: Timestamp
```

### discussion_subscriptions
Student subscription preferences for thread notifications.

```sql
- id: Primary key
- user_id: Student
- thread_id: Thread subscribed to
- subscription_type: 'all_replies', 'instructor_only', 'none'
- subscribed_at: Timestamp
```

### discussion_notifications
Notification history for user actions.

```sql
- id: Primary key
- user_id: Recipient
- thread_id: Related thread
- post_id: Related post
- notification_type: 'new_reply', 'mention', 'flagged', 'admin_message'
- message: Notification text
- is_read: Read status
- read_at: When read
- created_at: Timestamp
```

## File Structure

### Core Pages

#### `/discussions.php`
**Purpose**: Main discussion forum entrance for an assignment

**URL**: `discussions.php?aid={assignment_id}`

**Features**:
- Lists all discussion threads for an assignment
- "Start New Discussion" form on sidebar
- Thread cards showing title, author, post count, last activity
- Pin/Lock status badges
- Link to individual threads

**Permissions**: Students in the course, instructors

#### `/discussion_thread.php`
**Purpose**: View a single discussion thread with all posts and replies

**URL**: `discussion_thread.php?tid={thread_id}`

**Features**:
- Displays thread info and description
- Lists all posts in chronological order
- "Add Your Reply" form (if thread not locked)
- Like/engagement buttons on each post
- Subscription management
- Thread statistics sidebar

**Permissions**: Thread participants, instructors

#### `/assignment_view.php`
**Purpose**: Single assignment details page with discussion access

**URL**: `assignment_view.php?id={assignment_id}`

**Features**:
- Complete assignment details
- Student submission status and grades
- Direct link to discussions forum (for discussion assignments)
- Resubmission options
- Grade display with feedback

**Permissions**: Students (view own data), instructors (view all)

### Admin Pages

#### `/admin/discussions.php`
**Purpose**: Admin dashboard for discussion management

**URL**: `admin/discussions.php`

**Features**:
- Discussion statistics (total threads, posts, locked threads, flagged)
- Recent threads list with pin/lock toggles
- Flagged posts with moderation actions
- Thread management (pin, lock, delete posts)

**Permissions**: Admin only

## API Functions

### Thread Management

#### `createDiscussionThread($assignment_id, $course_id, $created_by, $title, $description, $allow_anonymous)`
Creates a new discussion thread.

```php
$thread_id = createDiscussionThread(
    $assignment_id,
    $course_id,
    $_SESSION['user_id'],
    'How to solve problem X?',
    'Detailed question description...',
    true  // allow anonymous
);
```

#### `getAssignmentDiscussions($assignment_id, $sort_by = 'last_activity', $limit = null)`
Retrieves all threads for an assignment, sorted by pinned status and activity.

#### `getDiscussionThread($thread_id)`
Gets a specific thread with user details and post count.

#### `toggleThreadPin($thread_id, $pin = true)`
Pins/unpins a thread to top of discussion list (instructor only).

#### `toggleThreadLock($thread_id, $lock = true)`
Locks/unlocks a thread to prevent new replies (instructor only).

### Post Management

#### `createDiscussionPost($thread_id, $assignment_id, $posted_by, $content, $is_anonymous, $parent_post_id)`
Adds a new post to a discussion thread.

```php
$post_id = createDiscussionPost(
    $thread_id,
    $assignment_id,
    $_SESSION['user_id'],
    'Here is my response...',
    false,  // not anonymous
    null    // no parent (top-level reply)
);
```

#### `getDiscussionPosts($thread_id)`
Retrieves all posts in a thread with engagement data.

#### `updateDiscussionPost($post_id, $content)`
Edits an existing post.

#### `deleteDiscussionPost($post_id)`
Soft-deletes a post (marks unpublished).

### Engagement

#### `toggleDiscussionPostLike($post_id, $user_id)`
Likes or unlikes a post. Returns 'liked' or 'unliked'.

#### `hasUserLikedPost($post_id, $user_id)`
Checks if user has liked a specific post.

### Subscriptions

#### `toggleDiscussionSubscription($thread_id, $user_id, $subscription_type)`
Updates user's notification preference for a thread.

**Subscription types**:
- `all_replies`: Notified on all new posts
- `instructor_only`: Only notified on instructor posts
- `none`: No notifications

#### `getUserSubscriptionStatus($thread_id, $user_id)`
Gets current subscription type for user on a thread.

### Notifications

#### `notifyDiscussionReply($thread_id, $assignment_id, $posted_by, $post_id)`
Sends notifications to subscribed users when new reply posted.

#### `getUserDiscussionNotifications($user_id, $limit = 10)`
Retrieves unread notifications for a user.

#### `markDiscussionNotificationAsRead($notification_id)`
Marks notification as read.

## Integration with Assignments

### Discussion-Type Assignments

When creating an assignment with `assignment_type = 'discussion'`:

1. Assignment fields used:
   - `assignment_type`: Must be 'discussion'
   - `title`: Discussion topic/name
   - `description`: Full discussion prompt
   - `submission_deadline`: Optional due date for participation
   - `points_possible`: Graded based on participation level

2. Automatic links appear:
   - Assignment view page has prominent "Open Discussions Forum" button
   - Assignment list shows 💬 icon and discussion link
   - Course navigation includes discussion forum access

3. Grading considerations:
   - Track student participation separately from regular submissions
   - Can grade based on:
     - Number of quality posts
     - Engagement with peers (likes, helpful marks)
     - Addressing discussion prompts
     - Timeliness of contributions

## Usage Examples

### For Students

**Starting a Discussion:**
```
1. Go to assignment page
2. Click "Discussions Forum" or visit discussions.php?aid=123
3. Click "Start New Discussion" sidebar form
4. Enter title and optional description
5. Optionally choose "Allow anonymous posts"
6. Click "Create Discussion"
```

**Posting a Reply:**
```
1. Click thread to open discussion_thread.php
2. Scroll to "Add Your Reply" form
3. Type response
4. Optionally check "Post anonymously" if enabled
5. Click "Post Reply"
```

**Managing Notifications:**
```
1. In thread, click "Thread Subscription" sidebar card
2. Select preference: "All replies", "Instructor only", "Unsubscribe"
3. Click "Update"
```

### For Instructors

**Managing Discussions:**
```
1. Go to admin/discussions.php
2. View statistics and recent threads
3. Click "Pin" to feature important discussions
4. Click "Lock" to end a discussion
5. Review flagged posts under "Flagged Posts" section
6. Click "Delete" to remove inappropriate content
```

## Moderation & Flags

### Flagging Posts
Future enhancement: Students can flag inappropriate posts, which appear in admin dashboard for review.

### Best Practices
1. Set clear discussion guidelines in assignment description
2. Monitor discussions regularly, especially early on
3. Pin instructor announcements or key insights
4. Lock discussions when topic is fully addressed
5. Review for spam and off-topic posts

## Performance Considerations

### Indexes
- `idx_assignment`: Speed thread retrieval by assignment
- `idx_pinned`, `idx_locked`: Quick filtering for display
- `idx_post_thread`, `idx_post_user`: Post query optimization
- `idx_subscription_user`: Notification lookup optimization

### Optimization Tips
1. Limit threads displayed per page (use pagination)
2. Cache thread statistics
3. Archive old discussions after course completion
4. Implement breadth-first retrieval for post trees

## Security

### Access Control
- Only course members can view course discussions
- Students cannot see discussions from other courses
- Anonymous posts mask user identity at display level
- Audit logging tracks moderation actions

### Data Protection
- SQL injection prevention via parameterized queries
- XSS prevention via htmlspecialchars() on all output
- CSRF token validation can be extended to forms
- Soft deletes for audit trail preservation

## Future Enhancements

1. **Search & Filter**: Search posts by keyword, filter by date range
2. **Thread Rating**: Rate thread quality/helpfulness
3. **Nested Replies**: Full thread-like conversation structure
4. **Rich Text Editor**: TinyMCE integration for formatted posts
5. **Mentions**: @mention students to notify them
6. **Email Notifications**: Send email alerts for subscribed threads
7. **Digest Mode**: Weekly digest of discussion activity
8. **Analytics**: Detailed participation metrics and engagement reports
9. **Post Editing History**: Track changes to edited posts
10. **Peer Moderation**: Allow students to rate post helpfulness (for future post visibility)

## Troubleshooting

### Discussions Not Showing
- Verify assignment has `show_to_students = 1`
- Check assignment type is set to 'discussion'
- Ensure user is enrolled in course

### Posts Not Saving
- Check if thread is locked (locked threads reject new posts)
- Verify post content is not empty
- Check database connection and permissions

### Notifications Not Sending
- Verify subscription type is not 'none'
- Check notification table for created entries
- Review access permissions and filters

## Configuration

No global configuration needed. Discussion settings are managed per-thread:
- Thread-level: `allow_anonymous`, `is_locked`, `is_pinned`
- Post-level: `is_anonymous`, `is_flagged`
- User-level: subscription preference

Adjust moderation policies by updating assignment descriptions and instructor guidelines.
