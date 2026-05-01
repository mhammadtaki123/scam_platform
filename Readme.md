# ScamGuard — AI-Driven Consumer Protection & Scam Awareness Platform

> A PHP-based web platform that uses AI (Anthropic Claude or local Ollama) to analyze scam reports, compute shop risk scores, and help consumers make safer online purchasing decisions.

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Tech Stack](#tech-stack)
4. [Project Structure](#project-structure)
5. [Database Schema](#database-schema)
6. [Risk Score Formula](#risk-score-formula)
7. [AI Integration](#ai-integration)
8. [Setup & Installation](#setup--installation)
9. [User Roles](#user-roles)
10. [Pages & Routes](#pages--routes)
11. [API Endpoint](#api-endpoint)
12. [Security](#security)

---

## Overview

ScamGuard is a community-powered scam detection platform built for CSCI 490. Users can search for online shops, read verified scam reports, submit their own reports with evidence, and leave star ratings. Every report is automatically analyzed by an AI model which assigns a scam probability score. Admins moderate the queue and approve or reject reports — each action instantly recalculates the shop's overall risk score.

The platform supports two AI backends:
- **Anthropic Claude** (cloud) via the `/v1/messages` API
- **Ollama** (local) via `llama3.1:8b` or any compatible local model

---

## Features

| Feature | Description |
|---|---|
| 🔍 Shop Search | Filter shops by name, category, and sort by risk or rating |
| 🤖 AI Scam Analysis | Every submitted report is analyzed for scam probability, flags, and a summary |
| 📊 Risk Score Gauge | SVG arc gauge on each shop page showing a 0–100 risk score |
| ⭐ Star Reviews | Authenticated users can leave 1–5 star ratings with comments |
| 🚨 Report Submission | File upload support (image/PDF) alongside text descriptions |
| 🛡️ Admin Dashboard | Stats overview, pending report queue, high-risk shop list |
| ✅ Report Moderation | Approve/reject reports; each action recalculates the shop risk score |
| 🏪 Shop Management | Admins can add or delete shops from the platform |
| 💡 Flash Messages | Session-based feedback messages across all actions |
| 🌙 Dark UI | Full dark theme built with Tailwind CSS and custom CSS variables |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ |
| Database | MySQL via phpMyAdmin / XAMPP |
| Database Access | PDO (prepared statements) |
| Frontend | HTML5, Tailwind CSS (CDN), vanilla JavaScript |
| Fonts | Google Fonts — Syne (headings), DM Sans (body) |
| AI (cloud) | Anthropic Claude (`claude-sonnet-4-20250514`) |
| AI (local) | Ollama — `llama3.1:8b` |
| HTTP Client | PHP cURL |
| Local Server | XAMPP (Apache + MySQL) |
| IDE | VS Code |

---

## Project Structure

```
scam_platform/
│
├── admin/                      # Admin restricted pages
│   ├── analytics.php           # Visual data charts (Chart.js)
│   ├── claims.php              # Shop ownership claim management
│   ├── dashboard.php           # Platform overview & moderation queue
│   ├── moderate.php            # Report review & approval
│   ├── settings.php            # Risk score algorithm weights
│   └── shops.php               # Shop database management
│
├── api/                        # Public & internal JSON endpoints
│   ├── ai_detect.php           # AI scam analysis endpoint
│   └── check_url.php           # Public risk check API
│
├── auth/                       # Authentication & security
│   ├── change_password.php     # User security settings
│   ├── forgot_password.php     # Password reset request
│   ├── login.php               # Sign in
│   ├── logout.php              # Sign out
│   ├── register.php            # New user signup
│   └── reset_password.php      # Set new password via token
│
├── database/
│   └── scam_db.sql             # Full DB schema with tables and seed data
│
├── includes/
│   ├── db.example.php          # Database configuration template
│   ├── db.php                  # Active DB connection & constants
│   ├── footer.php              # Shared footer
│   ├── functions.php           # Core logic, risk scoring, AI integration
│   └── header.php              # Navigation, auth checks, UI head
│
├── reports/
│   └── submit.php              # Scam reporting form
│
├── shops/
│   ├── claim.php               # Ownership verification form
│   ├── search.php              # Directory search & filters
│   └── view.php                # Shop profile & reviews
│
├── user/
│   └── profile.php             # Personal dashboard & activity tracking
│
├── assets/
│   └── uploads/                # User-submitted evidence (images/PDFs)
│
├── index.php                   # Homepage
├── leaderboard.php             # Community contributor rankings
├── notifications.php           # In-app user alerts
└── faq.php, terms.php, etc.    # Static information pages
```

---

## Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| user_id | INT PK AI | |
| username | VARCHAR(50) | |
| email | VARCHAR(100) | Unique |
| password | VARCHAR(255) | Bcrypt hashed |
| role | ENUM | `user` or `admin` |
| created_at | DATETIME | |

### `shops`
| Column | Type | Notes |
|---|---|---|
| shop_id | INT PK AI | |
| shop_name | VARCHAR(100) | |
| website_url | VARCHAR(255) | |
| description | TEXT | |
| category | VARCHAR(60) | e.g. Electronics, Fashion |
| created_at | DATETIME | |

### `reviews`
| Column | Type | Notes |
|---|---|---|
| review_id | INT PK AI | |
| user_id | INT FK | References `users` |
| shop_id | INT FK | References `shops` |
| rating | TINYINT | 1–5 |
| comment | TEXT | |
| created_at | DATETIME | |

### `reports`
| Column | Type | Notes |
|---|---|---|
| report_id | INT PK AI | |
| user_id | INT FK | References `users` |
| shop_id | INT FK | References `shops` |
| description | TEXT | Min 30 chars enforced |
| evidence_path | VARCHAR(255) | Filename in `assets/uploads/` |
| status | ENUM | `pending`, `approved`, `rejected` |
| ai_score | FLOAT | 0.0–1.0 from AI analysis |
| admin_note | TEXT | Optional moderator note |
| created_at | DATETIME | |
| reviewed_at | DATETIME | Set on approve/reject |

### `risk_scores`
| Column | Type | Notes |
|---|---|---|
| shop_id | INT PK FK | References `shops` |
| risk_score | FLOAT | 0–100, the final computed score |
| rating_avg | FLOAT | Average star rating |
| report_count | INT | Total reports submitted |
| ai_avg_score | FLOAT | Average AI score across all reports |
| last_updated | DATETIME | Auto-updated on recalculation |

---

## Risk Score Formula

The risk score is a weighted composite of three components, each normalized to 0–100:

```
Risk Score = (0.40 × Rating Component)
           + (0.30 × Report Component)
           + (0.30 × AI Component)
```

### Rating Component (40%)
Converts average star rating to a risk value. A 5-star average yields 0 risk; a 1-star average yields 100 risk. Defaults to 50 (neutral) when no reviews exist.

```
rating_component = (1 - ((avg_rating - 1) / 4)) × 100
```

### Report Component (30%)
Combines the approval rate of submitted reports with a volume penalty (capped at 50 points).

```
approval_rate    = approved_reports / total_reports
volume_penalty   = min(total_reports × 5, 50)
report_component = (approval_rate × 50) + volume_penalty
```

### AI Component (30%)
The average `ai_score` (0.0–1.0) across all reports for that shop, multiplied by 100. Defaults to 50 (neutral) when no AI scores exist.

```
ai_component = avg(ai_score) × 100
```

### Risk Labels

| Score Range | Label | Color |
|---|---|---|
| 0 – 30 | Low Risk | Green |
| 31 – 60 | Medium Risk | Amber |
| 61 – 100 | High Risk | Red |

The score is recalculated automatically whenever a review is submitted or a report is approved/rejected by an admin.

---

## AI Integration

### How It Works

When a user submits a scam report, `detectScamText()` in `functions.php` sends the report description to the configured AI backend. The AI returns a structured JSON response:

```json
{
  "score": 0.87,
  "confidence": "high",
  "flags": ["no refund policy", "fake tracking number", "counterfeit goods"],
  "summary": "Report strongly suggests fraudulent behaviour with multiple scam indicators."
}
```

The `score` (0.0–1.0) is stored as `ai_score` on the report and factored into the shop's risk score calculation.

---

### Option A — Anthropic Claude (Cloud)

Set in `includes/db.php`:

```php
define('ANTHROPIC_API_KEY', 'your-key-here');
define('ANTHROPIC_MODEL',   'claude-sonnet-4-20250514');
```

Endpoint: `https://api.anthropic.com/v1/messages`

Requires an internet connection and an API key from [console.anthropic.com](https://console.anthropic.com).

---

### Option B — Ollama (Local, No Internet Required)

Set in `includes/db.php`:

```php
define('OLLAMA_URL',   'http://localhost:11434/api/chat');
define('OLLAMA_MODEL', 'llama3.1:8b');
```

Endpoint: `http://localhost:11434/api/chat`

Requirements:
- Ollama installed and running (`ollama serve`)
- Model pulled: `ollama pull llama3.1:8b`
- No API key needed, no internet required after model download

The response parser includes a regex fallback (`preg_match('/\{.*\}/s', ...)`) to handle cases where the local model wraps its JSON in markdown fences or surrounding text.

---

## Setup & Installation

### Prerequisites
- XAMPP (Apache + MySQL) installed and running
- PHP 8.0+ with cURL enabled
- Ollama installed (for local AI) **or** an Anthropic API key (for cloud AI)

### Steps

**1. Copy the project**
```
C:\xampp\htdocs\scam_platform\
```

**2. Import the database**

Open phpMyAdmin → click **Import** → select `database/schema.sql` → click Go.

This creates the `scam_db` database with all tables and seed data (5 sample shops, 1 admin user).

**3. Configure `includes/db.php`**

```php
// Database (defaults work for standard XAMPP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'scam_db');

// Choose ONE of the following AI configs:

// Option A — Anthropic (cloud)
define('ANTHROPIC_API_KEY', 'sk-ant-...');
define('ANTHROPIC_MODEL',   'claude-sonnet-4-20250514');

// Option B — Ollama (local)
define('OLLAMA_URL',   'http://localhost:11434/api/chat');
define('OLLAMA_MODEL', 'llama3.1:8b');
```

**4. Create the uploads directory**

Make sure `assets/uploads/` exists and is writable by the web server.

**5. Visit the platform**
```
http://localhost/scam_platform/
```

**6. Log in as admin**

Log in using the default admin account provisioned in your database schema.

> ⚠️ Change the admin password immediately after first login.

---

## User Roles

### Guest (not logged in)
- Browse and search shops
- View shop risk scores, reviews, and approved reports

### User (registered)
- Everything a guest can do
- Submit scam reports with optional evidence upload
- Leave star ratings and written reviews on shops

### Admin
- Everything a user can do
- View the admin dashboard with platform-wide stats
- Approve or reject pending reports (triggers risk score recalculation)
- Add or delete shops from the platform
- View the full moderation history

---

## Pages & Routes

| URL | Access | Description |
|---|---|---|
| `/index.php` | Public | Landing page with hero, stats, featured shops |
| `/shops/search.php` | Public | Shop search with filters and sorting |
| `/shops/view.php?id=X` | Public | Shop detail: risk gauge, reviews, reports, owner responses |
| `/leaderboard.php` | Public | Ranked leaderboard of top community contributors |
| `/auth/register.php` | Guest | Registration form |
| `/auth/login.php` | Guest | Login form |
| `/auth/forgot_password.php` | Guest | Request a password reset token |
| `/auth/reset_password.php` | Guest | Set a new password with a valid token |
| `/auth/logout.php` | User | Destroys session, redirects to login |
| `/user/profile.php` | User | Personal dashboard tracking trust score and history |
| `/reports/submit.php` | User | Scam report form with file upload |
| `/shops/claim.php` | User | Form to submit ownership evidence for a shop |
| `/admin/dashboard.php` | Admin | Stats overview + pending queue |
| `/admin/moderate.php` | Admin | Full moderation queue with filters |
| `/admin/shops.php` | Admin | Add/delete shops |
| `/admin/claims.php` | Admin | Review pending shop ownership claims |
| `/admin/analytics.php` | Admin | Charts and reporting trends |
| `/admin/settings.php` | Admin | Dynamic risk score algorithm weighting |
| `/api/ai_detect.php` | User (POST) | JSON endpoint for AI analysis |
| `/api/check_url.php` | Public (GET) | JSON endpoint to check a URL's risk score |

---

## API Endpoint

`POST /api/ai_detect.php`

Requires an active user session. Accepts JSON or form data.

**Request:**
```json
{ "text": "I ordered from this shop and never received my package..." }
```

**Response:**
```json
{
  "score": 0.82,
  "confidence": "high",
  "flags": ["non-delivery", "unresponsive seller"],
  "summary": "Report indicates likely scam with non-delivery and seller silence."
}
```

---


*© 2026 Mhammad Taki. ScamGuard — For client project*
