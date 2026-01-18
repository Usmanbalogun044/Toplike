Plan: Document API Endpoints With Scribe

Objective
- Provide extensive, clear documentation for every API endpoint using Scribe annotations embedded in controllers, plus a generated browsable manual.

Approach
- Prefer Scribe for inline PHPDoc and generated HTML/Markdown docs. Optionally emit an OpenAPI spec for integration.

Tasks
1) Add Scribe if missing: composer require knuckleswtf/scribe --dev; publish config; tailor config/scribe.php (title, auth header, intro).
2) Group endpoints via @group: Auth, Profile, Posts, Comments, Likes, Challenges, Wallet, Withdrawals, Banks, Webhooks.
3) Annotate controllers/actions with Scribe tags: @authenticated, @queryParam, @bodyParam, @response, @responseField, @status.
4) Document authentication: JWT Bearer with auth:api middleware; include header example and token TTL notes.
5) Capture examples using Resources and typical payloads; document file uploads and pagination.
6) Generate and review docs: php artisan scribe:generate; commit docs and share path.

Endpoint Groups & Annotation Outline

Auth (app/Http/Controllers/AuthController.php)
- register(RegisterRequest)
  - @group Auth
  - @bodyParam username string required unique
  - @bodyParam email string required unique
  - @bodyParam password string required confirmed
  - @bodyParam first_name string
  - @bodyParam last_name string
  - @bodyParam phone_number string
  - @bodyParam country string
  - @response 201 { token, user }
- login(LoginRequest)
  - @group Auth
  - @bodyParam email string required
  - @bodyParam password string required
  - @response 200 { token, user }
- verifyOtp(VerifyOtpRequest)
  - @group Auth
  - @bodyParam email string required
  - @bodyParam code string required size:6
  - @bodyParam type string required oneOf: registration, login_otp, password_reset
  - @response 200 { verified: true }
- logout()
  - @group Auth
  - @authenticated
  - @response 204
- me()
  - @group Auth
  - @authenticated
  - @response 200 UserResource

Profile (app/Http/Controllers/UserProfileController.php)
- update(UserProfileRequest)
  - @group Profile
  - @authenticated
  - @bodyParam username string unique (except self)
  - @bodyParam first_name string
  - @bodyParam last_name string
  - @bodyParam bio string
  - @bodyParam phone_number string
  - @bodyParam country string
  - @bodyParam state string
  - @bodyParam lga string
  - @bodyParam avatar file image max:5120
  - @response 200 UserResource

Wallet (app/Http/Controllers/WalletController.php)
- show()
  - @group Wallet
  - @authenticated
  - @response 200 { balances, currency }
- transactions()
  - @group Wallet
  - @authenticated
  - @queryParam page integer
  - @response 200 { data: [WalletTransactionResource], meta }
- provisionVirtualAccount()
  - @group Wallet
  - @authenticated
  - @response 201 { account_number, bank_name, provider }

Withdrawals (app/Http/Controllers/WithdrawalController.php)
- index()
  - @group Withdrawals
  - @authenticated
  - @queryParam page integer
  - @response 200 { data: [WithdrawalResource], meta }
- store()
  - @group Withdrawals
  - @authenticated
  - @bodyParam amount numeric min:100 required
  - @bodyParam bank_name string required
  - @bodyParam account_number string required size:10
  - @bodyParam account_name string
  - @response 201 WithdrawalResource

Banks (app/Http/Controllers/BankController.php)
- list()
  - @group Banks
  - @response 200 [ { code, name } ]
- resolve()
  - @group Banks
  - @bodyParam bank_code string required
  - @bodyParam account_number string required size:10
  - @response 200 { account_name, bank_name }
- save()
  - @group Banks
  - @authenticated
  - @bodyParam bank_name string required
  - @bodyParam account_number string required size:10
  - @response 201 { id, bank_name, account_number }

Posts & Likes (app/Http/Controllers/PostController.php, LikeController.php)
- store()
  - @group Posts
  - @authenticated
  - @bodyParam media file required mimes:jpg,jpeg,png,mp4,mov,avi max:10240
  - @bodyParam caption string
  - @response 201 PostResource
- show(Post)
  - @group Posts
  - @authenticated
  - @response 200 PostResource
- like(Post)
  - @group Likes
  - @authenticated
  - @response 201 { liked: true }
- unlike(Post)
  - @group Likes
  - @authenticated
  - @response 204

Challenges (app/Http/Controllers/ChallengeController.php)
- current()
  - @group Challenges
  - @response 200 ChallengeResource
- index()
  - @group Challenges
  - @response 200 { data: [ChallengeResource], meta }
- leaderboard(Challenge)
  - @group Challenges
  - @response 200 { leaderboard: [ { user, score } ] }
- join()
  - @group Challenges
  - @authenticated
  - @bodyParam challenge_id uuid required
  - @response 201 { entry_id, status }
- joinCallback()
  - @group Challenges
  - @authenticated
  - @response 200 { status }
- webhook()
  - @group Challenges
  - @response 200 { ok: true }

Webhooks (app/Http/Controllers/PaymentWebhookController.php)
- handle()
  - @group Webhooks
  - @header X-Paystack-Signature string required
  - @response 200 { received: true }
  - @response 401 { message: "Invalid signature" }

Comments (if present)
- create()
  - @group Comments
  - @authenticated
  - @bodyParam post_id uuid required
  - @bodyParam content string required
  - @bodyParam parent_id uuid nullable
  - @response 201 CommentResource
- index(show)
  - @group Comments
  - @response 200 { data: [CommentResource], meta }

Authentication
- JWT Bearer tokens via auth:api guard.
- Include header: Authorization: Bearer {token}.
- Token issuance via AuthService@login; TTL per config/jwt.php.
- Additional middleware: user.active; rate limits on selected endpoints.

Pagination & Filtering
- Standard page param; meta includes current_page, last_page, per_page, total.

File Uploads
- multipart/form-data for avatar and post media; document allowed mimes and sizes.

Errors
- 401 unauthenticated; 403 inactive or forbidden; 422 validation (errors map); 429 throttling.

Generation & Review
- Commands:
  - composer require knuckleswtf/scribe --dev
  - php artisan vendor:publish --tag=scribe-config
  - configure config/scribe.php (title, base URL, auth header)
  - php artisan scribe:generate
- Review output under public/docs (or resources/docs if configured); iterate annotations as needed.
