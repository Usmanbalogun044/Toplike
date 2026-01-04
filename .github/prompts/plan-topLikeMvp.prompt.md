## Plan: TopLike MVP Backend (Enterprise-Level)

This plan translates the TopLike PRD into an enterprise-style backend design and implementation roadmap. It assumes a Laravel-based modular monolith with clear bounded contexts, layered architecture, and strong focus on correctness for money, competition logic, and abuse prevention.

---

### 1. Architecture & Principles

- **Style:** Modular monolith with clear domains:
   - Identity & Access
   - Wallet & Payments
   - Challenge & Competition
   - Content & Engagement
   - Admin & Fraud
- **Layers:**
   - Presentation: HTTP controllers, requests, resources.
   - Application: services and use-case orchestration.
   - Domain: models, enums, core rules.
   - Infrastructure: persistence details, external APIs, jobs.
- **Key principles:**
   - All money operations go through a single WalletService.
   - All challenge lifecycle logic centralized in ChallengeService + scheduled commands.
   - Controllers stay thin, delegating to services.
   - Explicit role and status checks on every sensitive route.

---

### 2. Bounded Contexts / Modules

1. **Identity & Access Context**
    - Responsibilities:
       - Registration, login/logout, JWT issuance.
       - Role management: Guest, User, Admin (future: Recruiter).
       - User status: active, suspended, banned.
    - Core objects:
       - User, UserProfile, UserRole, UserStatus.
    - Deliverables:
       - Harden AuthService and UserProfileService.
       - Middleware: `auth:api`, `role:user`, `role:admin`.

2. **Wallet & Payments Context**
    - Responsibilities:
       - Wallet balances per user.
       - Immutable transaction history.
       - Participation fee debits, prize credits, withdrawals.
    - Core objects:
       - UserWallet, WalletTransaction, Withdrawal, TransactionType enum.
    - Deliverables:
       - WalletService, WithdrawalController, WalletController.
       - PaymentGatewayService abstraction for future Paystack/Flutterwave.

3. **Challenge & Competition Context**
    - Responsibilities:
       - Weekly challenge lifecycle and configuration.
       - Entries, rankings, prize pool management.
    - Core objects:
       - Challenge, ChallengeEntry, ChallengeStatus enum.
    - Deliverables:
       - ChallengeService and ChallengeController.
       - Weekly scheduled command to open/close/settle challenges.

4. **Content & Engagement Context**
    - Responsibilities:
       - Posts (image/video), captions, basic comments.
       - Likes, views.
    - Core objects:
       - Post, Like, Comment, PostView.
    - Deliverables:
       - PostService, LikeService, PostController, LikeController.

5. **Admin & Fraud Context**
    - Responsibilities:
       - Admin management of users, challenges, withdrawals.
       - Fraud detection hooks (rate anomalies, suspicious wallets).
    - Core objects:
       - Admin actions log, simple fraud flags.
    - Deliverables:
       - AdminController(s), FraudService (initial hooks only).

---

### 3. Layered Structure (per Context)

- **Presentation layer**
   - Controllers under app/Http/Controllers/{Context} (Auth, Wallet, Challenge, Post, Admin).
   - Form Requests under app/Http/Requests/{Context} for validation.
   - API Resources under app/Http/Resources/{Context} for consistent responses.

- **Application layer**
   - Services in app/Services/{Context} encapsulating use cases:
      - AuthService, UserProfileService.
      - WalletService, ChallengeService, PostService, LikeService.
   - Console Commands / Jobs for scheduled tasks (weekly challenge management).

- **Domain layer**
   - Models in app/Models.
   - Enums in app/Enums (UserRole, UserStatus, TransactionType, ChallengeStatus).
   - Core business invariants enforced through services + DB constraints.

- **Infrastructure layer**
   - PaymentGatewayService implementation (later).
   - FileUploadTrait + Cloudinary integration.
   - Logging/Telescope for observability.

---

### 4. Detailed Domain Designs

#### 4.1 Identity & Auth

- **User model:**
   - Fields: id, name, email, username, password, role, status, last_login_at, etc.
   - Relationships: profile, wallet, posts, challengeEntries, withdrawals.
- **Flows:**
   - Register: create user → create profile → create wallet.
   - Login: issue JWT, update last_login_at.
   - Role enforcement: middleware on admin routes; user-only access for posting/liking.

#### 4.2 Wallet & Transactions

- **Rules:**
   - Wallet balance must never go negative.
   - Every change generates a WalletTransaction record.
   - Use clear transaction types: PARTICIPATION_FEE, PRIZE_PAYOUT, DEPOSIT, WITHDRAWAL, PLATFORM_SHARE.
- **WalletService responsibilities:**
   - `getBalance(User $user)`
   - `credit(User $user, int $amount, TransactionType $type, array $meta = [])`
   - `debit(User $user, int $amount, TransactionType $type, array $meta = [])` (throws if insufficient funds).
   - Wrap sensitive operations in DB transactions.
- **Withdrawals:**
   - User submits withdrawal request (min threshold enforced).
   - Status: PENDING → APPROVED/REJECTED by admin.
   - On APPROVED: WalletService.debit and mark withdrawal as completed.

#### 4.3 Challenge Lifecycle

- **Rules (from PRD):**
   - Participation fee: ₦500 per week.
   - One active challenge at a time.
   - One post per user per challenge.
   - Timeline: opens Monday 00:00, closes Sunday 23:59, likes freeze at deadline.
- **ChallengeService responsibilities:**
   - `createWeeklyChallenge(int $weekNumber, Carbon $startAt, Carbon $endAt)`
   - `getActiveChallenge(): ?Challenge`
   - `enterChallenge(User $user, Post $post)`:
      - Verify active challenge exists.
      - Verify user not already entered.
      - Debit wallet for participation fee.
      - Create ChallengeEntry.
- **Settlement:**
   - At end time:
      - Collect all entries, compute likes count per entry.
      - Rank by likes descending.
      - Compute prize pool and distribute: 1st 40%, 2nd 10%, 3rd 5%, platform 45%.
      - Use WalletService for all credits (winners + platform wallet).
      - Mark challenge as SETTLED and store final leaderboard snapshot.

#### 4.4 Posts & Engagement

- **Post rules:**
   - Each post belongs to a user and optionally to a challenge via ChallengeEntry.
   - Media stored via Cloudinary; DB stores URLs and metadata.
   - During active challenge, posts cannot be edited or deleted.
- **PostService responsibilities:**
   - `createPostForCurrentChallenge(User $user, UploadedFile $media, string $caption)`:
      - Verify active challenge.
      - Verify user has not posted yet in this challenge.
      - Upload media via FileUploadTrait.
      - Create Post + ChallengeEntry (and optionally trigger participation fee debit through WalletService if not charged earlier).

- **LikeService responsibilities:**
   - `likePost(User $user, Post $post)`:
      - Prevent self-likes.
      - Ensure user has not already liked.
      - Create Like.
   - `unlikePost(User $user, Post $post)` (optional for MVP).
   - Endpoints guarded with Laravel rate limiters.

---

### 5. API Design (v1)

- **Base path:** `api/v1/...` to allow future versioning.

- **Auth & User**
   - POST `api/v1/auth/register`
   - POST `api/v1/auth/login`
   - POST `api/v1/auth/logout`
   - GET  `api/v1/auth/me`
   - PUT  `api/v1/profile`

- **Wallet**
   - GET  `api/v1/wallet` (current balance + summary)
   - GET  `api/v1/wallet/transactions` (paginated history)

- **Challenges**
   - GET  `api/v1/challenge/current`
   - GET  `api/v1/challenges` (past challenges)
   - GET  `api/v1/challenges/{id}/leaderboard`

- **Posts**
   - POST `api/v1/posts` (create post in current challenge)
   - GET  `api/v1/challenge/current/posts`
   - GET  `api/v1/posts/{id}`

- **Likes**
   - POST   `api/v1/posts/{id}/like`
   - DELETE `api/v1/posts/{id}/like`

- **Withdrawals**
   - POST `api/v1/withdrawals`
   - GET  `api/v1/withdrawals`

- **Admin** (protected by `role:admin`)
   - GET   `api/v1/admin/users`
   - PATCH `api/v1/admin/users/{id}/status`
   - GET   `api/v1/admin/withdrawals`
   - PATCH `api/v1/admin/withdrawals/{id}` (approve/reject)
   - GET   `api/v1/admin/challenges`

---

### 6. Data & Persistence Guidelines

- Use integer or fixed-precision decimals for all monetary values.
- Enforce DB-level constraints:
   - Unique (challenge_id, user_id) in challenge_entries.
   - Unique (post_id, user_id) in likes.
   - Foreign keys for all user_id, challenge_id, post_id references.
- Use soft deletes for Users and Posts to preserve history.
- WalletTransactions act as the single source of truth for financial history.

---

### 7. Security, Compliance, Fraud

- **Security:**
   - JWT auth on all non-public routes.
   - Strict validation via Form Requests.
   - HTTPS-only in production; secure cookies if used.

- **Fraud / Abuse:**
   - Throttle login, registration, like endpoints.
   - Basic checks for abnormal like activity (same IP/device, high velocity).
   - Ability for admin to freeze wallets and suspend users.

- **Compliance:**
   - Minimal PII storage; encrypt sensitive data where needed.
   - NDPR-style data handling: clear purpose, minimal retention.

---

### 8. Observability & Operations

- Enable Laravel Telescope in non-production for request and query inspection.
- Log key events: registrations, logins, wallet debits/credits, challenge settlements, admin approvals.
- Define simple metrics (can start with logs/dashboards):
   - Weekly active users and paid participants.
   - Participation fees vs payouts per challenge.
   - Number and volume of withdrawals.

---

### 9. Delivery Roadmap (Implementation Order)

1. Harden Identity & Auth (roles, statuses, JWT, basic tests).
2. Implement WalletService and wallet APIs (no external payments yet).
3. Implement ChallengeService and ChallengeController (current, list, leaderboard).
4. Implement PostService and PostController (media upload + 1-post-per-challenge rule).
5. Implement LikeService and LikeController (self-like prevention, rate limiting).
6. Implement weekly challenge command + scheduler and settlement logic.
7. Implement Withdrawal and Admin APIs (moderation, approvals, wallet freeze).
8. Add initial fraud checks, logging, and metrics.

This plan is now the enterprise-level backend roadmap for the TopLike MVP and can guide implementation, code reviews, and further refinement.
