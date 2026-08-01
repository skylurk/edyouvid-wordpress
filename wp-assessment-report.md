# WordPress Site Assessment Report
**Site:** edyouvid.com | **Date:** 2026-07-08 | **Prepared for:** Development planning

---

## 1. Environment & Server

### OS & Web Stack
| Component | Version / Detail |
|---|---|
| OS | Ubuntu 22.04.1 LTS (Jammy Jellyfish) |
| Web server | Apache 2.4.52 (Ubuntu), mod_php (not FPM) |
| PHP | 8.3.31 — served via `mod_php` (Apache module) |
| MySQL | 8.0.35 Community Server |
| Redis | Active (`redis-server` bound to 127.0.0.1:6379) |

### PHP Limits (Apache/production values)
| Setting | Value | Impact |
|---|---|---|
| `max_execution_time` | 60 s | Tight for large CSV imports (500+ rows) |
| `memory_limit` | 128 MB | Low — WP sets 512 MB via `define('WP_MEMORY_LIMIT','512M')`, but this is a WP-layer hint, not the hard PHP ceiling |
| `upload_max_filesize` | 16 MB | Adequate for CSV/Excel imports |
| `post_max_size` | 16 MB | Adequate |
| `max_input_vars` | 1000 | May truncate large HTML forms (bulk user lists) |

**PHP serving method:** `mod_php` (not PHP-FPM). This means each Apache worker holds a PHP interpreter; no easy per-request isolation. Relevant to long-running import jobs.

**PHP extensions present:** curl, gd, gmp, intl, mbstring, mysqli, mysqlnd, openssl, pdo_mysql, soap, sockets, zip, zlib, apcu. Notably **no `imagick`** (uses GD). All extensions needed for WooCommerce, LearnDash, and CSV parsing are present.

### Caching Layers
- **Redis** — running on 127.0.0.1:6379, `redis-cache` plugin active. All WordPress object cache and transients route through Redis.
- **APCu** — extension loaded; used by some plugins as local in-process cache.
- **No page cache** — no WP Super Cache, W3 Total Cache, or Cloudflare page-caching detected.
- **No Memcached.**

### Cron
- `DISABLE_WP_CRON true` is set — WP-Cron is **disabled** for page-load triggering.
- **Real server crontab entry:**
  ```
  */5 * * * * www-data php /var/www/html/wp-cron.php > /dev/null 2>&1
  ```
- This is the correct production setup. Scheduled emails and renewals will fire every 5 minutes reliably.

### Mail
- **MTA:** Postfix is installed and active (`/usr/sbin/postfix`, `active`).
- **WP Mail SMTP plugin (v4.8.0) — configured to use Gmail API** via OAuth2.
  - From address: `info@stage.edyouvid.com` (**staging address — must be updated for production**)
  - Authenticated Gmail account: `david@acuvera.com`
  - No transactional service (SendGrid, Mailgun, SES, Brevo, Resend) is configured.
- **wp-mail-logging** plugin is active — all outgoing emails are logged in `wp_check_email_log` (17,686 rows).
- **Risk:** Gmail API is rate-limited (~500 emails/day for personal accounts, up to ~2,000/day for Workspace). For bulk engagement emails (3,000+ users) this will fail. A transactional email provider is required.

### SSL & Disk
- **SSL:** Valid Let's Encrypt cert covering `edyouvid.com` and `stage.edyouvid.com`. Expires 2026-09-29. Certificate is named under the staging domain — should be reissued under primary domain name.
- **Disk:** 78 GB total, 56 GB used, **22 GB free (28% remaining)**. Backups on-disk consume ~24 GB. Disk space is a concern.

### Error Log Summary
- Apache error log is **dominated by a single repeated warning**: `PHP Warning: Constant FORCE_SSL_ADMIN already defined in wp-config.php on line 119`. This fires on **every page request**. Root cause: `FORCE_SSL_ADMIN` is defined twice in wp-config.php (lines 5 and 119 — once by a plugin or earlier block, once explicitly). No fatal errors, no 500s in the sample reviewed.

---

## 2. WordPress Core & Configuration

| Item | Value |
|---|---|
| WordPress version | **7.0** |
| Multisite | No |
| Active theme | **Astra** (stock theme, no child theme) |
| Site URL / Home | `https://edyouvid.com` |
| Table prefix | `wp_` |
| WP_DEBUG | `false` (no debug log present) |

### wp-config.php — Constants Defined (secrets redacted)
- `WP_MEMORY_LIMIT` — 512 M (WP-layer hint; PHP hard ceiling is 128 MB — mismatch)
- `WP_REDIS_HOST`, `WP_REDIS_PORT` (6379), `WP_REDIS_TIMEOUT`, `WP_REDIS_READ_TIMEOUT`, `WP_REDIS_DATABASE`, `WP_REDIS_PASSWORD` — Redis fully configured
- `DISABLE_WP_CRON` — true
- `WP2FA_ENCRYPT_KEY` — defined
- `DB_*` — standard database credentials
- Auth keys/salts — all defined
- `JWT_AUTH_SECRET_KEY`, `JWT_AUTH_CORS_ENABLE` — REST API JWT auth configured
- `DISALLOW_FILE_EDIT` — true (good — no theme/plugin editor)
- `DISALLOW_FILE_MODS` — **false** (plugin/theme installs allowed from WP admin — consider setting true in production)
- `FORCE_SSL_ADMIN` — **defined twice** (causes warning on every request — must be deduplicated)

---

## 3. Plugins — Full Inventory

### Active Plugins (53 total)
| Plugin | Version | Category |
|---|---|---|
| LearnDash LMS (sfwd-lms) | 4.3.1.2 | LMS core |
| LearnDash Course Grid | — | LMS add-on |
| LearnDash Hub | — | LMS add-on |
| LearnDash Retake Quiz | 1.7.7 | LMS add-on |
| LearnDash WooCommerce | — | LMS-WC bridge |
| LearndashRefresherHistory Premium | — | LMS add-on |
| Uncanny LearnDash Groups | **6.1.5** | Groups / user mgmt |
| Uncanny LearnDash Toolkit | 3.8.0.3 | LMS utilities |
| Uncanny Toolkit Pro (OLD) | 4.3.2 | LMS utilities (INACTIVE dir but active) |
| Uncanny Continuing Education Credits | — | CEU tracking |
| Uncanny LearnDash Codes | — | Enrollment codes |
| Tin Canny LearnDash Reporting | 5.1.3.1 | Reporting / SCORM |
| WooCommerce | **10.8.1** | E-commerce |
| Woo Product Bundle | — | Bundled products |
| Woo Product Table | — | Product display |
| Easy WooCommerce Discounts | — | Discounts |
| WooCommerce Services | — | WC services |
| WooCommerce Menu Bar Cart | — | Cart display |
| Users/Customers Import-Export for WP | — | Import/export |
| Woo-Rave (Flutterwave) | 2.4.1 | Payment gateway |
| Pesapal (thebunch-ke) | 3.0.5 | Payment gateway |
| Points and Rewards for WooCommerce | — | Loyalty/credits |
| WP Mail SMTP | 4.8.0 | Email delivery |
| WP Mail Logging | — | Email logging |
| JWT Auth for WP REST API | — | REST API auth |
| Elementor | — | Page builder |
| Essential Addons for Elementor | — | Elementor add-ons |
| Header Footer Elementor | — | HF builder |
| Jetpack | — | Jetpack suite |
| Jetpack VideoPress | — | Video |
| Presto Player | — | Video player |
| Spotlightr | — | Video hosting |
| WP Security Audit Log | — | Activity logging |
| MalCare Security | — | Security/WAF |
| WP Fail2Ban | — | Brute-force |
| WP 2FA | — | Two-factor auth |
| Limit Login Attempts Reloaded | — | Login protection |
| LoginPress / LoginPress Pro | — | Login page |
| Advanced NoCaptcha & Invisible Captcha | — | Anti-spam |
| WP User Avatar | — | Avatar |
| Yoast SEO | — | SEO |
| IndexNow | — | SEO indexing |
| Microsoft Clarity | — | Analytics |
| Testimonials Carousel Elementor | — | Social proof |
| Scroll Top | — | UI |
| Reveal IDs | — | Dev tool |
| Check Email | — | Email test |
| Media Library Plus | — | Media org |
| Redis Cache | — | Object cache |

### Inactive / Installed-but-not-active Plugins
`all-in-one-wp-migration`, `all-in-one-seo-pack`, `astra-sites`, `autocomplete-learndash`, `cartflows`, `cf7-hubspot`, `cloudinary`, `code-snippets`, `duplicator`, `ewww-image-optimizer`, `google-sitemap-generator`, `heroku`, `import-users-from-csv`, `kliken-marketing`, `leadin`, `login-or-logout-menu-item`, `manage-enrollment-learndash`, `manage-xml-rpc`, `new-users-monitor`, **`paid-memberships-pro`** (3.0.3 — installed, not active), `rave-payment-forms`, `rave-woocommerce-payment-gateway`, `redis-woo-dynamic-pricing`, `testimonial-free`, `wisdm-reports-for-learndash`, `woo-coupon-usage`, `woocommerce-gateway-paypal-express-checkout`, `woocommerce-paypal-payments`, `wp-activity-log-for-woocommerce`, `wp-lazyload-*` (×2), `wp-test-email`, `wpforms-lite`, `wpvivid-backuprestore`

### Category Assessment vs Planned Features

#### LMS
- **LearnDash 4.3.1.2** is the LMS. Current version is ~4.15+; this is approximately 2 years out of date. 68 courses, 845 lessons, 893 quizzes, 40 groups.
- **Uncanny LearnDash Groups 6.1.5** — this is the key group-management plugin. It provides:
  - A **Group Management page** (ID 54318) where a group leader manages their group's learners
  - Group Course Report, Quiz Report, Assignment Report, Essay Report, Progress Report pages (all published)
  - Enrollment code system (`wp_ulgm_group_codes`: 8,320 codes)
  - `wp_ulgm_group_details`: records which order/product provisioned each group (order_id, product_id, ld_group_id, issue_date)
  - **This plugin substantially overlaps with features 4 (user add/import) and parts of 2 (dashboard)**

#### E-Commerce / Payments
- **WooCommerce 10.8.1** — current and active. 462 completed orders, 126 cancelled. No subscription products (`shop_subscription` post type absent — WooCommerce Subscriptions is NOT installed).
- **No saved payment tokens** in `wp_woocommerce_payment_tokens` (table is empty).
- **LearnDash-WooCommerce integration** plugin active — links WC products to LD courses.

#### Payment Gateways
| Gateway | Status | Saved Cards | Off-session | KES | M-PESA |
|---|---|---|---|---|---|
| **Flutterwave/Rave** (woo-rave 2.4.1) | Disabled | `saved_cards: yes` in older settings; unclear if tokenisation works without WC Subscriptions | Requires WC Subscriptions or manual implementation | Yes | Via Flutterwave |
| **Pesapal** (thebunch-ke 3.0.5) | **Enabled (live)** | No | No | Yes (primary KES gateway) | Yes |
| **M-PESA direct** (wc-mpesa-payment-gateway) | **Enabled** | No | No | Yes | Yes |
| PayPal (ppec + ppcp) | Enabled (live) | Via PayPal vault (ppcp has vault disabled) | No (vault_enabled: false) | No | No |
| Rave tbz | Disabled | — | — | — | — |

**Critical finding:** No gateway currently in production supports saved card tokenisation + merchant-initiated (off-session) charging. Flutterwave supports this via their API but the WooCommerce plugin integration needs WooCommerce Subscriptions or custom implementation. Pesapal and M-PESA are redirect/STK-push only.

#### Membership / Subscription
- **Paid Memberships Pro 3.0.3** — installed but **inactive**. One level defined: "Premium" at KES 10,000/year. Zero active members. Tables exist (`wp_pmpro_*`) but are empty. This was likely a prior attempt.
- **WooCommerce Subscriptions** — **not installed**. This is the standard WC plugin for recurring billing, saved cards, and proration.
- **Custom gl-dashboard plugin** — has its own `wp_gld_subscriptions` table (0 rows currently) tracking group subscription start/expiry dates.

#### Email / Automation
- **WP Mail SMTP 4.8.0** — Gmail API, rate-limited. No newsletter or CRM plugin (FluentCRM, Mailchimp, ActiveCampaign, etc.) present.
- **No marketing automation or bulk email tool installed.** Engagement emails (inactivity, monthly recap, custom) would require custom development or a CRM plugin.

#### User Import / Export
- **Users/Customers Import-Export for WP & WooCommerce** — active. Handles CSV import/export of WP users.
- **import-users-from-csv** — inactive, older plugin.
- **Uncanny LearnDash Codes** — active. Allows enrollment via single-use codes (alternative to CSV import for self-service enrollment).

#### Page Builder & Forms
- **Elementor** — active, full site built with it.
- **No dedicated form plugin** active (WPForms Lite is inactive).

#### Security & Backup
- MalCare WAF, WP Security Audit Log, WP Fail2Ban, WP 2FA, Limit Login Attempts — all active.
- **Backup:** All-in-One WP Migration (`.wpress` files on-disk, latest 2026-05-26, ~3.5 GB). WPVivid also installed (inactive). No offsite/cloud backup destination configured.

---

## 4. LMS Deep-Dive

### Storage Architecture
| Post Type | Count | Storage |
|---|---|---|
| `sfwd-courses` | 68 | `wp_posts` + `wp_postmeta` |
| `sfwd-lessons` | 845 | `wp_posts` |
| `sfwd-topic` | 870 | `wp_posts` |
| `sfwd-quiz` | 893 | `wp_posts` + `wp_learndash_pro_quiz_*` |
| `sfwd-certificates` | 68 | `wp_posts` |
| `groups` | 40 | `wp_posts` |

**Custom tables:**
| Table | Rows | Purpose |
|---|---|---|
| `wp_learndash_user_activity` | 521,306 | Per-user course/lesson/topic/quiz activity events |
| `wp_learndash_user_activity_meta` | 5,287,835 | Meta for activity records (largest table at 819 MB) |
| `wp_learndash_pro_quiz_statistic` | 2,425,362 | Per-attempt quiz statistics |
| `wp_learndash_pro_quiz_statistic_ref` | 254,897 | Statistic reference records |
| `wp_learndash_pro_quiz_question` | 7,030 | Quiz question definitions |
| `wp_ulgm_group_codes` | 8,320 | Enrollment codes |
| `wp_ulgm_group_details` | 52 | Group provisioning records |

Course progress also lives in `wp_usermeta` key `_sfwd-course_progress` (serialised PHP array per user per course) — this is the primary progress store.

### Group Leader Role
LearnDash has a built-in `group_leader` role. 145 users currently hold this role. Group leaders can:
- View their assigned groups in LearnDash admin
- Add/remove users from their groups (admin-side)

**Uncanny LearnDash Groups 6.1.5** extends this with front-end pages for the group leader:
- **Group Management** (`/group-management/`) — add/remove learners, view who's enrolled
- **Group Course Report** — per-learner course completion
- **Group Quiz Report** — per-learner quiz scores
- **Group Progress Report** — progress percentages
- **Buy Courses** page — purchasing additional courses for the group

What Uncanny Groups does **not** do: subscription billing, card-on-file charging, proration, credit handling, or inactivity emails.

### Reporting Data Available for Engagement Emails
| Data Point | Source | Coverage |
|---|---|---|
| Last login | `wp_usermeta.learndash-last-login` | 2,263 / 3,072 users (74%) |
| Last WC activity | `wp_usermeta.wc_last_active` | WC customers only |
| Course completion | `wp_learndash_user_activity` (activity_type='course', activity_completed != null) | Full |
| Lesson/topic progress | `wp_learndash_user_activity` | Full |
| Quiz attempts | `wp_learndash_pro_quiz_statistic` | Full (2.4M rows) |
| Time spent | `wp_ld_time_entries` | 0 rows — not in use |

A monthly recap email drawing on `wp_learndash_user_activity` is feasible. The 26% of users with no `learndash-last-login` meta can fall back to `wp_users.user_registered` or activity table timestamps.

---

## 5. Payments Deep-Dive

### Active Gateways (live credentials present)
1. **Pesapal 3.0.5** — Production keys configured. Handles M-PESA, Airtel Money, Visa/Mastercard via redirect. **No tokenisation.** Primary real-money gateway currently in use (462 completed orders).
2. **M-PESA direct** (STK Push) — Live shortcode 4082149 configured. Handles STK push to customer phone. No saved cards.
3. **PayPal** (ppec + ppcp) — Live credentials for `tim.walsh@acuvera.com`. Vault is **disabled** in PPCP settings.
4. **Flutterwave** (woo-rave) — Live keys present (`FLWPUBK-43a3...X`). Plugin currently **disabled**. The older tbz-rave settings have `saved_cards: yes` but this is the plugin setting, not active card storage — `wp_woocommerce_payment_tokens` is empty.

### Capability Matrix for Planned Features
| Requirement | Pesapal | M-PESA | Flutterwave | PayPal |
|---|---|---|---|---|
| KES currency | ✅ | ✅ | ✅ | ❌ |
| M-PESA | ✅ | ✅ | ✅ | ❌ |
| Saved card / token | ❌ | ❌ | ✅ (API-level) | ✅ (vault, disabled) |
| Off-session charge | ❌ | ❌ | ✅ (API-level) | ⚠️ (reference transactions) |
| Prorated amount | N/A — gateway-agnostic | N/A | N/A | N/A |
| Credit/refund | Via Pesapal dashboard | No | ✅ | ✅ |

**Conclusion:** For saved-card + merchant-initiated charging (features 5, 6, 7), **Flutterwave is the only viable existing gateway**. It must be reactivated and configured for tokenisation using their Charge with Token API. Pesapal/M-PESA remain appropriate for first-time or one-off payments.

### Existing Subscription Products
- None. No `shop_subscription` post type, no WooCommerce Subscriptions plugin.
- Paid Memberships Pro has one level ("Premium", KES 10,000/yr) but zero members and the plugin is inactive.
- The custom `wp_gld_subscriptions` table exists but has 0 rows — the gl-dashboard subscription system is set up but never populated with real data.

---

## 6. Users, Roles & Data

### User Counts
- **Total users:** 3,072
- **Group leaders:** 145 (role: `group_leader`)
- **Administrators:** 1
- **Subscribers:** very few (<10 explicitly set; most users appear to have default/subscriber caps)

### Roles (from `wp_capabilities` meta)
LearnDash adds: `group_leader`. All other users are `subscriber`. Standard WP roles (editor, author, contributor) do not appear to be in use.

### Last Login Tracking
- `learndash-last-login` user meta: 2,263 users have this set — good coverage.
- `wc_last_active` user meta: present for WooCommerce customers.
- WP Security Audit Log has 913 login events in `wp_wsal_occurrences` (alert IDs 1000/1001) — but this is a log, not a current "last login" value per user.

### Custom User Meta Suggesting Prior Group Structures
- `wp_ulgm_group_codes` links users to groups via enrollment codes — this is the Uncanny Groups provisioning trail.
- `wp_ulgm_group_details` (52 rows) shows 52 groups were provisioned via WooCommerce orders.
- `learndash_group_users_{group_id}` user meta — LearnDash's native group membership flag.

### Database Size
- **Total DB:** ~2.3 GB
- Largest tables: `wp_learndash_user_activity_meta` (819 MB), `wp_usermeta` (329 MB), `wp_learndash_user_activity` (192 MB), `wp_learndash_pro_quiz_statistic` (158 MB).

---

## 7. Email & Consent Readiness

### Current Email Flow
All WordPress email (`wp_mail()`) is routed through **WP Mail SMTP → Gmail API** using OAuth2 credentials for `david@acuvera.com`. From name: "Educative Content". From address: `info@stage.edyouvid.com` (**staging address still set — must be updated**).

Emails are logged in `wp_check_email_log` (17,686 records) and `wp_wpml_mails` (31,234 records).

Current emails sent by the system:
- WooCommerce order confirmations
- LearnDash course completion / certificate emails
- gl-dashboard subscription warning emails (7-day, 1-day, expiry notices — built but `wp_gld_subscriptions` is empty so none sent yet)

### Consent / Unsubscribe
- **No marketing consent meta found** in `wp_usermeta` (no `*consent*`, `*subscribe*`, `*optin*`, `*marketing*` keys with meaningful data — only 2 `uae-optin-notice` records for UI dismissals).
- **No unsubscribe mechanism exists.**
- Terms & Conditions page: ID 40401, last modified 2022-04-23, URL includes staging domain in GUID.
- Privacy Policy page: ID 40451, last modified 2022-04-23, WP privacy page option points to ID 3 (different page — likely orphaned).

---

## 8. Code Quality & Customisation

### Custom Plugins
**`gl-dashboard` (v1.1.3)** — the only custom-built plugin. Provides:
- REST API endpoints: `/gl-dashboard/v1/groups`, `/progress`, `/analytics`, `/users`, `/subscription`, `/course-stats`
- Alpine.js + Chart.js frontend dashboard rendered via shortcode `[gl_dashboard]`
- WooCommerce integration: bundle products with course lists, access grant on order completion
- Custom subscription table (`wp_gld_subscriptions`) with expiry tracking
- Cron-based cache warming (every 5 min) and daily expiry checks
- Cache busting on LearnDash completion hooks
- Redis-backed transient caching (10-min TTL, 50-users-per-page pagination)

### Must-Use Plugins
- `elementor-safe-mode.php` — Elementor troubleshooting scaffolding (harmless)
- `installatron_hide_status_test.php` — suppresses WP health-check for auto-update test (Installatron artefact)
- `learndash-multisite.php` — LearnDash multisite helper (present even though this is not a multisite install; harmless but tidying is recommended)

### Theme
**Astra** (stock, no child theme). `functions.php` is 214 lines — stock Astra, no custom payment/email/user code. No custom code relevant to planned features in the theme.

### Version Control
- **Git repository initialised** at `/var/www/html`. One commit: "Initial commit — full WordPress site".
- **No staging environment** (staging domain `stage.edyouvid.com` appears to be this same server with a second SSL cert — not a separate environment).
- No CI/CD or deploy tooling detected.

### Notable Config Issues
1. `FORCE_SSL_ADMIN` defined twice in wp-config.php (line 5 area and line 119) — causes PHP warning on every request.
2. `WP_MEMORY_LIMIT` = 512 MB in wp-config.php but Apache PHP hard ceiling is 128 MB — WP's hint is not honoured; Apache's `php.ini` must also be updated.
3. `DISALLOW_FILE_MODS` = false — allows plugin installs from WP admin panel (security risk in production).
4. WP Mail SMTP `from_email` still set to `info@stage.edyouvid.com`.

---

## 9. Backups & Safety

### Current Backup Solution
- **All-in-One WP Migration** — manual `.wpress` snapshots stored locally on the server:
  - 2026-05-26: 3.5 GB (most recent)
  - 2025-06-23: 3.3 GB
  - 2025-02-17: 2.9 GB
  - ...six snapshots total (~24 GB on-disk)
- **No offsite/cloud backup destination** configured (WPVivid remote destination not set).
- **No automated backup schedule** visible in crontab or WPVivid settings.

### Staging Feasibility
- Disk: 22 GB free. DB is 2.3 GB; codebase (excluding uploads) ~700 MB. A staging copy **without** uploads is feasible (needs ~3 GB). Including uploads (846 MB) still fits but leaves ~18 GB headroom.
- **Recommendation:** Before any development work, configure WPVivid or All-in-One WP Migration to push backups to an offsite location (S3, Google Drive, DigitalOcean Spaces). Current on-disk-only strategy means a disk failure or accidental `rm -rf` loses everything.

---

## Build-vs-Buy Analysis

| Feature | Status | Assessment |
|---|---|---|
| **1. Group Leader registration & sign-in** | ⚠️ Partial | WP user registration exists. LearnDash group_leader role exists with 145 leaders. **Missing:** admin approval workflow before a new leader can pay or access the dashboard. Requires custom development (new registration form, pending-approval queue, admin notification + one-click approval). |
| **2. Group leader dashboard** | ✅ Substantial | **Two overlapping systems exist:** (a) Uncanny LearnDash Groups 6.1.5 provides a full front-end group management suite (7 published pages); (b) custom gl-dashboard plugin provides a modern Alpine.js/Chart.js portal with REST API. Decision needed on which to extend. Uncanny Groups is more mature; gl-dashboard is more modern and custom. |
| **3. Saved card / card-on-file** | ❌ Not present | No saved payment tokens exist. Requires either: (a) WooCommerce Subscriptions + Flutterwave tokenisation add-on, or (b) custom Flutterwave Charge-with-Token API integration. This is the most complex technical gap. |
| **4. Add users individually or bulk CSV import** | ✅ Partial | Uncanny Groups provides front-end user add. "Users/Customers Import-Export" plugin is active for admin-side CSV import. **Gap:** a group-leader-facing CSV upload within their dashboard doesn't exist — leaders cannot import their own users via CSV without admin involvement. Moderate custom development needed. |
| **5. Per-user annual billing (debit saved card)** | ❌ Not present | Requires saved card (feature 3) + off-session charge logic. No existing plugin covers per-seat billing triggered by adding a user. Custom development required on top of whichever payment solution is chosen. |
| **6. Prorated billing for mid-cycle additions** | ❌ Not present | No proration logic anywhere in current code. Custom development required: calculate days remaining in subscription year, compute fractional charge, trigger off-session payment. |
| **7. Deactivation credit → next renewal** | ❌ Not present | No credit/wallet mechanism exists. WooCommerce store credit or a custom credit column in `wp_gld_subscriptions` would need to be built. |
| **8. Engagement emails (welcome, inactivity, monthly recap, ad-hoc)** | ❌ Not present | Data exists to power all of these (last login in user meta, activity in `wp_learndash_user_activity`). No automation or newsletter tool is installed. Options: (a) install FluentCRM (free, self-hosted, WP-native, LD integration available) or (b) build custom cron-driven email jobs. Gmail SMTP must be replaced with a transactional provider first. |
| **9. T&C page update + email consent / unsubscribe** | ⚠️ Partial | T&C page exists (ID 40401) but is outdated (2022) and GUID still references staging domain. No consent meta or unsubscribe mechanism. T&C content update is non-technical; consent checkbox on registration + unsubscribe link in emails requires custom development or FluentCRM's built-in consent tools. |

---

## Gaps & Risks

1. **No saved card / tokenisation pipeline** — the single biggest blocker for features 5, 6, and 7. Flutterwave is the only viable path given KES + M-PESA requirements, but their tokenisation requires either WooCommerce Subscriptions or a bespoke integration. Estimated effort: high.

2. **Gmail SMTP will break at scale** — sending 3,000 emails via Gmail API will hit rate limits within hours. Must migrate to a transactional provider (Resend, Brevo, Amazon SES) before any bulk engagement email feature goes live.

3. **No staging environment** — changes must be made directly to production or via a manual clone. Risk of breaking a live site with 3,000+ users. Set up a proper staging environment before development begins.

4. **Disk space at 72%** — 22 GB free with 24 GB of on-disk backups. Clearing old backups to offsite storage would free ~18 GB. Must monitor during development (Redis AOF, MySQL growth, new logs).

5. **PHP memory ceiling mismatch** — wp-config.php requests 512 MB but Apache's php.ini enforces 128 MB. Large CSV imports or bulk email jobs may exhaust memory. Must update `/etc/php/8.3/apache2/php.ini` `memory_limit`.

6. **LearnDash 4.3.1.2 is ~2 years out of date** — current is ~4.15+. Major Uncanny Groups features may require a newer LD version. Update should be tested on staging before production.

7. **`FORCE_SSL_ADMIN` duplicate warning** — fires on every page load, polluting logs and wasting CPU. Simple fix but should be done before development adds more noise.

8. **Two overlapping group management systems** (Uncanny Groups + gl-dashboard) — must decide which to extend for the new features rather than building on both in parallel.

9. **No offsite backups** — all backups are on the same disk as the live site. One disk failure = total loss.

10. **`from_email` still staging address** — `info@stage.edyouvid.com` is the current sender address. All system emails carry this address. Must be corrected immediately.

---

## Recommended Architecture

1. **Adopt Uncanny LearnDash Groups as the group management foundation** — it already provides the group leader portal, user management pages, and WooCommerce integration. Extend it rather than duplicating in gl-dashboard. Use gl-dashboard exclusively for the analytics/progress data API.

2. **Integrate Flutterwave tokenisation for saved cards** — on first payment (new group leader subscription), tokenise the card using Flutterwave's `charge/tokenize` endpoint. Store the token in `wp_woocommerce_payment_tokens` or a new `wp_gld_payment_tokens` table. All subsequent per-seat debits and renewals use off-session `charge/token` calls.

3. **Extend `wp_gld_subscriptions` to track per-seat billing** — add columns: `seats_count`, `seat_price`, `billing_start_date`, `credit_balance_kes`. This table becomes the source of truth for the corporate account's billing state.

4. **Build a `GLD_Billing` class** (new file in gl-dashboard) to encapsulate proration maths, credit application, and Flutterwave token charging. Triggered by: (a) group leader adds user → calculate prorated days remaining → charge token; (b) group leader removes user → calculate unused days → update `credit_balance_kes`; (c) annual renewal → subtract credit, charge remainder.

5. **Replace Gmail SMTP with Resend or Brevo** — both have generous free tiers and WordPress plugins. Update WP Mail SMTP mailer selection. Update `from_email` to `info@edyouvid.com`. This is a prerequisite for all engagement email work.

6. **Install FluentCRM** for engagement email automation — it integrates natively with LearnDash (course completion triggers, last-login data), supports email sequences, and has an unsubscribe/consent mechanism built in. Inactivity emails and monthly recap can be configured as automations without custom PHP.

7. **Add registration approval flow** — a custom `GLD_Registration` class that: sets new group-leader registrations to a `pending` status (custom user meta), sends admin notification, provides a WP admin page to approve/reject, and on approval triggers a welcome email + generates their checkout link.

8. **Add CSV import to the group leader frontend** — a new REST endpoint `POST /gl-dashboard/v1/groups/{id}/import` that accepts a CSV, creates/matches WP users, enrolls them in the group, and returns a results summary. The Alpine.js frontend gets a file upload component.

9. **Set up a real staging environment** — clone the DB and files to a subdomain (`staging.edyouvid.com`) or a separate droplet. Use WP's `WP_ENVIRONMENT_TYPE` constant and disable payment gateways in staging config.

10. **Fix pre-existing issues before build starts** — deduplicate `FORCE_SSL_ADMIN`, raise PHP `memory_limit` to 512 MB in `php.ini`, update `from_email`, push an offsite backup, and update LearnDash on staging.

---

## Open Questions

1. **Which payment provider will be the primary saved-card gateway?** Flutterwave is the only current option that supports tokenisation + KES. Is the client open to adding Stripe (which has strong tokenisation and Kenya card support via Stripe Kenya)? This decision gates features 3, 5, 6, 7.

2. **Expected corporate group sizes and total seat volume?** (e.g., "groups of 20–200 users, up to 5,000 seats total") — determines whether bulk Flutterwave token charges can be synchronous or must be queued.

3. **Proration basis:** calendar-day pro-rata of a fixed annual fee, or calendar-month rounding? And when does the subscription year start — at the group leader's first payment date, or a fixed annual date?

4. **Credit granularity:** Is unused-portion credit calculated to the nearest day or rounded to full months?

5. **Who creates the LearnDash group for a new group leader?** Currently this is manual. Should it be auto-created on first purchase, or does an admin review the leader registration and manually create the group before approving?

6. **Email consent standard:** Kenya Data Protection Act 2019 requires explicit, informed consent for marketing emails. Should consent be captured at registration only, or also at first engagement email? Is a double opt-in required?

7. **Is Uncanny Groups' existing group management portal acceptable as the base UI**, or does the client want a fully custom portal (as currently being built in gl-dashboard)?

8. **What is the billing currency?** All WooCommerce orders appear to be in KES. Confirm the seat price and whether group leaders are billed in KES exclusively.

9. **Are there plans to upgrade LearnDash** before development begins? Version 4.3.1.2 is significantly outdated and some planned integrations may require a newer version.

10. **Is there a target monthly email volume?** (For choosing and sizing the transactional email provider.)
