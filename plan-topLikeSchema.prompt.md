# Enterprise Database Schema & Architecture Plan (v2.3)

This plan outlines a secure, scalable, and normalized database architecture for **TopLike**. It transitions from a monolithic `User` model to a modular design using UUIDs, strict typing, and separation of concerns.

## 1. Core Architecture Principles
*   **Primary Keys**: All models will use `UUID` (Ordered UUID v4 or v7) instead of auto-incrementing integers. This prevents ID enumeration attacks and simplifies distributed scaling.
*   **Authentication**: Custom JWT implementation with OTP/Verification codes.
*   **Financial Integrity**: Money values stored as `DECIMAL(19, 4)` or integers (lowest currency unit), never floats.
*   **Separation of Concerns**:
    *   **Identity** (`User`) vs **Profile** (`UserProfile`).
    *   **Ledger** (`UserWallet`) vs **Transactions** (`WalletTransaction`).
*   **Soft Deletes**: Enabled for `User`, `Post`, `Comment`, and `Challenge` to preserve data integrity.

---

## 2. Detailed Schema Definitions

### A. Identity, Access & Security

#### 1. `users`
*Core authentication and system identity.*
*   `id`: UUID (PK)
*   `username`: String (Unique, Indexed)
*   `email`: String (Unique, Indexed)
*   `password`: String (Hashed) - *Nullable if using OTP-only login in future*
*   `role`: Enum (`admin`, `creator`, `user`) - *Default: user*
*   `status`: Enum (`active`, `suspended`, `banned`)
*   `is_online`: Boolean (Default: false) - *Real-time status*
*   `last_active_at`: Timestamp (Nullable) - *For "Seen recently"*
*   `remember_token`: String
*   `timestamps`
*   `softDeletes`

#### 2. `verifications`
*Stores OTPs and verification codes for auth/actions.*
*   `id`: UUID (PK)
*   `identifier`: String (Indexed) - *Email or Phone*
*   `code`: String - *Hashed for security*
*   `type`: Enum (`registration`, `login_otp`, `password_reset`, `withdrawal_confirmation`)
*   `expires_at`: Timestamp
*   `verified_at`: Timestamp (Nullable)
*   `timestamps`

#### 3. `user_profiles`
*Public-facing information and extended details. One-to-One with User.*
*   `id`: UUID (PK)
*   `user_id`: UUID (FK -> users.id) [Unique, Indexed]
*   `first_name`: String
*   `last_name`: String
*   `bio`: Text (Nullable)
*   `avatar_url`: String (Nullable)
*   `phone_number`: String (Nullable, Indexed)
*   `country`: String
*   `state`: String
*   `lga`: String (Local Government Area)
*   `is_verified`: Boolean (Default: false)
*   `verified_expires_at`: Timestamp (Nullable)
*   `timestamps`

---

### B. Social Graph & Activity Tracking

#### 4. `follows`
*User relationships (Following/Followers).*
*   `follower_id`: UUID (FK -> users.id)
*   `following_id`: UUID (FK -> users.id)
*   `created_at`: Timestamp
*   *Constraint*: Primary Key (`follower_id`, `following_id`) - *Composite PK prevents duplicates*
*   *Index*: `following_id` - *Fast lookup for "Who follows me?"*

#### 5. `user_activities`
*Audit log and behavioral tracking (Login, Time spent, Actions).*
*   `id`: UUID (PK)
*   `user_id`: UUID (FK -> users.id) [Indexed]
*   `type`: Enum (`login`, `logout`, `post_created`, `challenge_joined`, `wallet_deposit`, `session_ping`)
*   `ip_address`: String (Nullable)
*   `user_agent`: String (Nullable)
*   `meta_data`: JSON (Nullable) - *Stores duration, device info, location data*
*   `created_at`: Timestamp
*   *Index*: `(user_id, created_at)` - *For timeline queries*

---

### C. Financial System (Ledger)

#### 6. `user_wallets`
*The current state of a user's funds. One-to-One with User.*
*   `id`: UUID (PK)
*   `user_id`: UUID (FK -> users.id) [Unique, Indexed]
*   `balance`: Decimal(19, 4) (Default: 0.0000)
*   `currency`: String (Default: 'NGN')
*   `withdrawal_pin`: String (Hashed, Nullable)
*   `is_frozen`: Boolean (Default: false)
*   `timestamps`

#### 7. `wallet_transactions`
*Immutable history of every money movement. Double-entry accounting principle.*
*   `id`: UUID (PK)
*   `wallet_id`: UUID (FK -> user_wallets.id) [Indexed]
*   `type`: Enum (`deposit`, `withdrawal`, `entry_fee`, `prize_credit`, `refund`)
*   `direction`: Enum (`credit`, `debit`)
*   `amount`: Decimal(19, 4)
*   `reference`: String (Unique, Indexed) - *External payment ref or internal UUID*
*   `description`: String
*   `status`: Enum (`pending`, `successful`, `failed`)
*   `meta_data`: JSON (Nullable) - *Stores extra context like challenge_id*
*   `timestamps`
*   *Index*: `(wallet_id, created_at)` - *Optimizes transaction history queries*

#### 8. `withdrawals`
*Requests for payouts.*
*   `id`: UUID (PK)
*   `user_id`: UUID (FK -> users.id)
*   `wallet_id`: UUID (FK -> user_wallets.id)
*   `amount`: Decimal(19, 4)
*   `bank_name`: String
*   `account_number`: String
*   `account_name`: String
*   `status`: Enum (`pending`, `processing`, `approved`, `rejected`)
*   `admin_note`: Text (Nullable)
*   `processed_at`: Timestamp (Nullable)
*   `timestamps`

---

### D. Competition & Content Engine

#### 9. `challenges`
*The weekly competition containers.*
*   `id`: UUID (PK)
*   `title`: String (e.g., "Week 42: Dance Off")
*   `slug`: String (Unique)
*   `week_number`: Integer
*   `year`: Integer
*   `status`: Enum (`scheduled`, `active`, `voting_closed`, `completed`)
*   `starts_at`: Timestamp
*   `ends_at`: Timestamp
*   `entry_fee`: Decimal(19, 4)
*   `prize_pool`: Decimal(19, 4)
*   `rules`: Text (Nullable)
*   `timestamps`
*   `softDeletes`

#### 10. `challenge_entries`
*Record of a user paying to join a specific challenge.*
*   `id`: UUID (PK)
*   `challenge_id`: UUID (FK -> challenges.id)
*   `user_id`: UUID (FK -> users.id)
*   `payment_status`: Enum (`pending`, `paid`, `refunded`)
*   `paid_at`: Timestamp
*   `timestamps`
*   *Constraint*: Unique(`challenge_id`, `user_id`) - *Prevents double entry*

#### 11. `posts`
*The content submitted for a challenge.*
*   `id`: UUID (PK)
*   `user_id`: UUID (FK -> users.id)
*   `challenge_id`: UUID (FK -> challenges.id) [Indexed]
*   `caption`: Text (Nullable)
*   `media_url`: String
*   `media_type`: Enum (`image`, `video`)
*   `thumbnail_url`: String (Nullable)
*   `status`: Enum (`pending`, `published`, `rejected`)
*   `likes_count`: BigInteger (Default: 0) - *Cached count*
*   `comments_count`: BigInteger (Default: 0) - *Cached count*
*   `views_count`: BigInteger (Default: 0) - *Cached count*
*   `timestamps`
*   `softDeletes`
*   *Constraint*: Unique(`challenge_id`, `user_id`) - *Enforces "One Post Per Challenge"*

#### 12. `comments`
*User discussions on posts.*
*   `id`: UUID (PK)
*   `user_id`: UUID (FK -> users.id)
*   `post_id`: UUID (FK -> posts.id) [Indexed]
*   `parent_id`: UUID (Nullable, FK -> comments.id) - *For nested replies*
*   `content`: Text
*   `timestamps`
*   `softDeletes`

#### 13. `likes`
*Engagement records.*
*   `id`: UUID (PK)
*   `user_id`: UUID (FK -> users.id)
*   `post_id`: UUID (FK -> posts.id) [Indexed]
*   `ip_address`: String (Nullable) - *For fraud detection*
*   `user_agent`: String (Nullable)
*   `timestamps`
*   *Constraint*: Unique(`user_id`, `post_id`) - *Prevents double liking*

#### 14. `post_views`
*Detailed analytics of who viewed what.*
*   `id`: UUID (PK)
*   `post_id`: UUID (FK -> posts.id) [Indexed]
*   `user_id`: UUID (Nullable, FK -> users.id) - *Nullable for guest views*
*   `ip_address`: String (Nullable)
*   `user_agent`: String (Nullable)
*   `viewed_at`: Timestamp
*   *Index*: `(post_id, viewed_at)` - *For analytics queries*

---

## 3. Implementation Roadmap

1.  **Cleanup**: Remove existing migrations that conflict with this new structure.
2.  **Base Migrations**: Create `users`, `user_profiles`, `verifications`, `follows`, `user_activities`.
3.  **Financial Migrations**: Create `user_wallets`, `wallet_transactions`, `withdrawals`.
4.  **Core Migrations**: Create `challenges`, `challenge_entries`, `posts`, `comments`, `likes`, `post_views`.
5.  **Model Refactoring**:
    *   Update `User` model (add `HasOne` Profile, `HasOne` Wallet, `HasMany` Activities).
    *   Create `UserProfile`, `Verification`, `Follow`, `UserActivity` models.
    *   Update `Post`, `Challenge` to use `HasUuids` trait.
6.  **Seeders**: Create seeders for Admin and test data.
