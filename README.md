# Zeno CRM

**Design and Development of a Secure and Intelligent Web-Based Customer Relationship Management Platform with Workflow Automation and Analytical Reporting**

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Tech Stack](#tech-stack)
3. [Folder Structure](#folder-structure)
4. [How It Works — Architecture](#how-it-works--architecture)
5. [UML Documentation](#uml-documentation)
6. [Functionality](#functionality)
7. [Running in Development](#running-in-development)
8. [Deploying to Render](#deploying-to-render)
9. [Self-Hosted Production](#self-hosted-production)
10. [API Reference](#api-reference)
11. [Database Schema](#database-schema)
12. [Default Credentials](#default-credentials)

---

## Project Overview

Zeno CRM is a cloud-ready, full-stack web application for managing customer relationships, sales pipelines, and business operations. It provides tools for lead tracking, opportunity management (Kanban), meeting scheduling, task assignment, support ticket management, and user administration — all behind a JWT-secured role-based access system.

**Team:**
| Name | ID | Role |
|---|---|---|
| Sumit Basak | 202512124 | Project Lead / System Architect |
| Sagar Bera | 202512107 | Backend Developer |
| Prit Tilala | 202512116 | Frontend Developer |
| Devendrasinh Gohil | 202512118 | Database & Deployment Engineer |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Vue.js 3 (Composition API), Vite, Tailwind CSS, DaisyUI, Pinia, Vue Router, Chart.js |
| Backend | PHP 8.2 (vanilla, no framework), RESTful API architecture |
| Database | MySQL 8.0 / MariaDB |
| Auth | JWT (HS256, pure PHP — no external library) |
| Local Dev | Docker, Docker Compose, Apache (with mod_rewrite) |
| Cloud Deploy | Render (backend: Docker Web Service, frontend: Static Site) |

---

## Folder Structure

```
zeno-crm/
│
├── render.yaml                           # Render blueprint (one-click cloud deploy)
├── docker-compose.yml                    # Local development orchestration
│
├── frontend/                             # Vue.js 3 SPA
│   ├── index.html                        # Vite entry HTML
│   ├── package.json                      # Node dependencies
│   ├── vite.config.js                    # Vite build config (dev proxy + aliases)
│   ├── tailwind.config.js                # Tailwind + DaisyUI config
│   ├── postcss.config.js                 # PostCSS (autoprefixer)
│   └── public/
│       └── _redirects                    # Render SPA fallback: /* → /index.html
│   └── src/
│       ├── main.js                       # App bootstrap — Vue + Pinia + Router
│       ├── App.vue                       # Root component
│       ├── style.css                     # Global styles + Tailwind directives
│       │
│       ├── router/
│       │   └── index.js                  # All routes + navigation guards
│       │
│       ├── composables/
│       │   └── useApi.js                 # Axios instance (JWT header, 401 handler, VITE_API_URL)
│       │
│       ├── stores/                       # Pinia state management
│       │   ├── auth.js                   # Login/logout, JWT token, user profile
│       │   ├── accounts.js               # Accounts CRUD + mock data fallback
│       │   ├── contacts.js               # Contacts CRUD + mock data fallback
│       │   ├── leads.js                  # Leads CRUD + convert + mock data fallback
│       │   ├── opportunities.js          # Opportunities CRUD + mock data fallback
│       │   ├── meetings.js               # Meetings CRUD + mock data fallback
│       │   ├── tasks.js                  # Tasks CRUD + mock data fallback
│       │   ├── support.js                # Support tickets CRUD + lead promotion
│       │   └── users.js                  # Users CRUD + mock data fallback
│       │
│       ├── components/
│       │   ├── layout/
│       │   │   ├── AppLayout.vue         # Main shell: sidebar + router-view
│       │   │   ├── Sidebar.vue           # Dark navigation sidebar with icons
│       │   │   └── Navbar.vue            # Top bar: page title, notifications, user
│       │   └── common/
│       │       ├── DataTable.vue         # Reusable: search, sort, paginate, actions
│       │       ├── Modal.vue             # DaisyUI modal wrapper (teleport to body)
│       │       ├── KanbanBoard.vue       # Drag-and-drop Kanban for opportunities
│       │       ├── StatCard.vue          # KPI card: icon + value + label + trend
│       │       └── StatusBadge.vue       # Colored badge for statuses/priorities
│       │
│       └── views/
│           ├── auth/
│           │   └── Login.vue             # Split-panel login page
│           ├── Dashboard.vue             # KPIs, charts, recent leads, tasks
│           ├── accounts/
│           │   ├── AccountList.vue       # Account table + create/edit/delete
│           │   └── AccountForm.vue       # Account modal form
│           ├── contacts/
│           │   ├── ContactList.vue       # Contact table + CRUD
│           │   └── ContactForm.vue       # Contact modal form
│           ├── leads/
│           │   ├── LeadList.vue          # Lead table with status badges + CRUD
│           │   └── LeadForm.vue          # Lead modal form
│           ├── opportunities/
│           │   ├── OpportunityList.vue   # Kanban board + list toggle + CRUD
│           │   └── OpportunityForm.vue   # Opportunity modal form
│           ├── meetings/
│           │   ├── MeetingList.vue       # Meeting table + CRUD
│           │   └── MeetingForm.vue       # Meeting modal form
│           ├── tasks/
│           │   ├── TaskList.vue          # Task table with priority badges + CRUD
│           │   └── TaskForm.vue          # Task modal form
│           ├── support/
│           │   ├── SupportList.vue       # Support ticket table + CRUD
│           │   └── SupportForm.vue       # Support ticket modal form
│           └── users/
│               ├── UserList.vue          # User management (admin only)
│               └── UserForm.vue          # User modal form
│
├── backend/                              # PHP 8 REST API
│   ├── Dockerfile                        # PHP 8.2 + Apache image for Render
│   ├── docker-entrypoint.sh              # Patches Apache port from $PORT env var
│   ├── composer.json                     # PHP dependencies (PHPUnit for tests)
│   ├── composer.lock
│   └── public/
│       ├── index.php                     # Entry point: autoloader + CORS + router
│       ├── .htaccess                     # Rewrite all requests to index.php
│       ├── setup.php                     # One-time utility: fix password hashes
│       └── generate-hash.php             # Utility: generate bcrypt hash
│   └── src/
│       ├── Config/
│       │   └── Database.php              # PDO singleton (reads DB_* env vars)
│       ├── Middleware/
│       │   └── Auth.php                  # JWT decode + user injection
│       ├── Helpers/
│       │   └── Response.php              # json_encode helpers (success/error)
│       ├── Models/                       # PDO-based models (no ORM)
│       │   ├── User.php
│       │   ├── Account.php
│       │   ├── Contact.php
│       │   ├── Lead.php                  # Includes convert() + promoteSupport()
│       │   ├── Opportunity.php
│       │   ├── Meeting.php
│       │   ├── Task.php
│       │   └── Support.php
│       └── Controllers/                  # Handle request → call model → return JSON
│           ├── AuthController.php        # POST /auth/login, POST /auth/logout
│           ├── DashboardController.php   # GET /dashboard
│           ├── AccountController.php
│           ├── ContactController.php
│           ├── LeadController.php        # Includes /convert + /promote_support
│           ├── OpportunityController.php
│           ├── MeetingController.php
│           ├── TaskController.php
│           ├── SupportController.php
│           └── UserController.php
│
├── database/
│   └── schema.sql                        # Full MySQL schema + seed data
│
└── UML_DIAGRAMS.md                       # PlantUML use case + sequence diagrams
```

---

## How It Works — Architecture

```
Browser (Vue.js SPA)
       │
       │  HTTPS/JSON + JWT Bearer token
       ▼
PHP REST API  (backend/public/index.php)
       │  Parses URI → routes to Controller
       │
       ├── Auth Middleware  (validates JWT on protected routes)
       │
       ├── Controllers  (validate input → call Model → return JSON)
       │
       └── Models  (PDO prepared statements → MySQL)
                          │
                     MySQL 8 Database
```

### Request Lifecycle

1. **Vue Router** intercepts navigation. The `beforeEach` guard checks for a valid JWT in `localStorage`. Unauthenticated users are redirected to `/login`.
2. **Pinia store** action (e.g. `accountsStore.fetchAll()`) calls `useApi.js`, which fires an Axios request with `Authorization: Bearer <token>`. The `baseURL` is read from `VITE_API_URL` at build time (falls back to `/api` for local dev).
3. **PHP `index.php`** receives the request, applies CORS headers (controlled by `FRONTEND_URL` env var), strips the `/api` prefix, and matches the URI + HTTP method to a controller.
4. **Auth middleware** decodes and verifies the JWT signature. On failure it returns `401`.
5. **Controller** reads the request body/params, calls the relevant Model method, and sends back a JSON response via `Response::success()` or `Response::error()`.
6. **Model** executes a PDO prepared statement against MySQL and returns the result array.
7. **Vue component** receives the data, updates the Pinia store state, and re-renders.

### Mock Data Fallback

Every Pinia store catches API errors and falls back to built-in mock data. This means **the frontend works fully without a running backend** — useful for UI development and demos.

### Authentication

- Login sends `email + password` to `POST /api/auth/login`
- Backend verifies password with `password_verify()` (bcrypt), generates a JWT signed with `JWT_SECRET`
- JWT payload: `user_id`, `email`, `role`, `name`, `exp` (24 h expiry)
- JWT is stored in `localStorage` and attached to every subsequent request via Axios interceptor
- Route guard in `router/index.js` redirects unauthenticated users to `/login`
- `/users` route is additionally guarded with `adminOnly` — non-admins redirect to `/dashboard`

---

## UML Documentation

Full UML diagrams are in **`UML_DIAGRAMS.md`** using PlantUML textual syntax (renderable in VS Code with the PlantUML extension, IntelliJ, or at plantuml.com).

| Diagram | Contents |
|---|---|
| Use Case Diagram | 2 actors (Admin, Sales User, Support User), 39 use cases across 9 packages |
| SSD-01 | User Authentication (login + logout) |
| SSD-02 | View KPI Dashboard |
| SSD-03 | Manage Accounts (CRUD) |
| SSD-04 | Manage Contacts (CRUD) |
| SSD-05 | Manage Leads (CRUD + status workflow) |
| SSD-06 | Convert Lead → Contact / Account / Opportunity |
| SSD-07 | Promote Lead → Support Ticket |
| SSD-08 | Manage Opportunities / Kanban (CRUD + drag-and-drop stage move) |
| SSD-09 | Schedule a Meeting |
| SSD-10 | Manage Tasks |
| SSD-11 | Manage Support Tickets (CRUD + resolve) |
| SSD-12 | Admin — User Management |

The visual `.drawio` package (`ZenoCRM_UML.drawio`) covers: Domain Model, Controllers, Use Case, Lead Conversion Sequence, and Activity diagrams.

---

## Functionality

### Dashboard
- **4 KPI cards:** Total Accounts, Total Contacts, Total Leads, Open Opportunities
- **Doughnut chart:** Opportunities broken down by pipeline stage (Chart.js)
- **Recent Leads table:** Last 5 leads with color-coded status badges
- **Upcoming Tasks list:** Next 5 tasks sorted by due date

### Accounts
- Full CRUD with searchable, sortable, paginated table
- Fields: Name, Email, Phone, Industry, Type (Customer / Partner / Prospect / Vendor), Website, Billing Address, Shipping Address, Description

### Contacts
- Full CRUD linked to Accounts
- Fields: First/Last Name, Email, Phone, Title, Department, Account, Address, Birthday, Description

### Leads
- Full CRUD with color-coded status badges
- **Status workflow:** New → In Process → Assigned → Recycled → Converted → Dead
- **Convert Lead:** one action creates a Contact + optional Account + optional Opportunity and marks the lead `converted`
- **Promote to Support:** creates a linked Support Ticket from a lead without changing lead status

### Opportunities
- Full CRUD
- **Kanban Board** with drag-and-drop stage transitions (optimistic update + rollback on failure)
- **List view** toggle
- **Stage pipeline:** Prospecting → Qualification → Proposal → Negotiation → Closed Won / Closed Lost
- Fields: Name, Account, Contact, Stage, Amount, Probability (%), Close Date, Lead Source, Assigned To

### Meetings
- Full CRUD with multi-contact attendees (junction table)
- Fields: Name, Parent (Account / Opportunity / Lead), Status (Planned / Held / Not Held), Start / End DateTime, Duration, Meeting Link, Assigned To

### Tasks
- Full CRUD with color-coded priority badges
- Fields: Name, Status (Not Started / In Progress / Completed / Deferred), Priority (Low / Medium / High / Urgent), Start Date, Due Date, Parent Record, Contact, Assigned To

### Support Tickets
- Full CRUD for customer support tracking
- **Status workflow:** Open → In Progress → Pending → Resolved → Closed
- **Priority levels:** Low / Medium / High / Urgent
- Can be created standalone or promoted directly from a Lead
- Fields: Subject, Status, Priority, Description, Resolution, Linked Contact, Account, Assigned To

### User Management *(Admin only)*
- Full CRUD — Name, Email, Password (bcrypt), Role (Admin / User), Team, Active Status, Page Permissions
- Page permissions control which modules a User-role account can access
- Last login timestamp displayed

---

## Running in Development

### Option A — Frontend Only (no backend required, uses mock data)

**Prerequisites:** Node.js 18+

```bash
cd frontend
npm install
npm run dev
```

Open **http://localhost:5173**.  
Login: `admin@zenocrm.com` / `Admin@123`

> Vite will pick the next available port (5174, 5175 …) if 5173 is busy.

---

### Option B — Full Stack with Docker

**Prerequisites:** Docker + Docker Compose

```bash
docker compose up --build
```

| Service | URL |
|---|---|
| Frontend (Vite dev) | http://localhost:5173 |
| Backend API | http://localhost:8080/api |
| MySQL | localhost:3306 |

```bash
docker compose down        # stop
docker compose down -v     # stop + wipe database
```

---

### Option C — Manual Full Stack (no Docker)

**Prerequisites:** Node.js 18+, PHP 8.2+, MySQL 8.0+

```bash
# 1. Database
mysql -u root -p < database/schema.sql

# 2. Backend
cd backend
export DB_HOST=127.0.0.1
export DB_NAME=zeno_crm
export DB_USER=zeno_user
export DB_PASS=zeno_pass
export JWT_SECRET=local_dev_secret
php -S localhost:8080 -t public

# 3. Frontend (new terminal)
cd frontend
npm install
npm run dev
# No VITE_API_URL needed — dev proxy in vite.config.js forwards /api → localhost:8080
```

---

## Deploying to Render

Render hosts the **backend** as a Docker Web Service and the **frontend** as a Static Site. MySQL is provided by an external free-tier database (Aiven recommended — 300 MB free).

### Step 1 — Provision a free MySQL database

1. Sign up at **[aiven.io](https://aiven.io)** → **Create Service** → **MySQL** → Free plan
2. Once provisioned, copy: **Host**, **Port**, **Username**, **Password**
3. Note the default database name (usually `defaultdb`; you can create `zeno_crm` instead)
4. Import the schema using any MySQL client:

```bash
mysql -h <AIVEN_HOST> -P <PORT> -u <USER> -p <DB_NAME> < database/schema.sql
```

Or use a GUI client (DBeaver, TablePlus) with SSL enabled (Aiven requires it).

> **Alternative free MySQL hosts:** [FreeSQLDatabase.com](https://freesqldatabase.com), [db4free.net](https://db4free.net), [Filess.io](https://filess.io)

---

### Step 2 — Push the repo to GitHub

Make sure the following files are committed:

```bash
git add \
  render.yaml \
  backend/Dockerfile \
  backend/docker-entrypoint.sh \
  backend/src/Config/Database.php \
  backend/public/index.php \
  frontend/src/composables/useApi.js \
  frontend/src/stores/auth.js \
  frontend/public/_redirects

git commit -m "Add Render deployment configuration"
git push
```

---

### Step 3 — Create services on Render

#### Option A — Blueprint (recommended, deploys both services at once)

1. Go to **dashboard.render.com → New → Blueprint**
2. Connect your GitHub repository
3. Render will detect `render.yaml` and show a preview of two services:
   - `zeno-crm-backend` (Docker Web Service)
   - `zeno-crm-frontend` (Static Site)
4. Click **Apply** — Render will start the first build

#### Option B — Manual (create services one by one)

**Backend:**
1. New → **Web Service** → connect repo → **Docker** runtime
2. Set **Dockerfile path:** `./backend/Dockerfile`
3. Set **Docker build context:** `./backend`

**Frontend:**
1. New → **Static Site** → connect repo
2. **Root directory:** `frontend`
3. **Build command:** `npm install && npm run build`
4. **Publish directory:** `dist`

---

### Step 4 — Set environment variables

Set these in the Render dashboard for each service (**Settings → Environment**).

**`zeno-crm-backend`:**

| Variable | Value |
|---|---|
| `DB_HOST` | Your Aiven MySQL host (e.g. `mysql-xxx.aivencloud.com`) |
| `DB_PORT` | Your Aiven MySQL port (e.g. `12345`) |
| `DB_NAME` | `zeno_crm` (or `defaultdb` if you didn't rename) |
| `DB_USER` | Your Aiven username |
| `DB_PASS` | Your Aiven password |
| `JWT_SECRET` | Any long random string — auto-generated if using Blueprint |
| `FRONTEND_URL` | `https://zeno-crm-frontend.onrender.com` *(set after frontend deploys)* |

**`zeno-crm-frontend`:**

| Variable | Value |
|---|---|
| `VITE_API_URL` | `https://zeno-crm-backend.onrender.com/api` |

> `VITE_API_URL` must be set **before** the build runs — Vite bakes it in at build time. If you set it after the first build, click **Manual Deploy → Deploy latest commit**.

---

### Step 5 — Cross-link the two services

Once both services have a public URL:

1. Copy the **frontend URL** (e.g. `https://zeno-crm-frontend.onrender.com`)
2. Paste it into the **backend's** `FRONTEND_URL` environment variable
3. Save → Render will redeploy the backend (CORS will now only allow the frontend origin)

---

### Render environment variable summary

| Service | Variable | Purpose |
|---|---|---|
| backend | `DB_HOST` | MySQL server hostname |
| backend | `DB_PORT` | MySQL server port |
| backend | `DB_NAME` | Database name |
| backend | `DB_USER` | Database username |
| backend | `DB_PASS` | Database password |
| backend | `JWT_SECRET` | JWT signing secret (keep private) |
| backend | `FRONTEND_URL` | Allowed CORS origin |
| frontend | `VITE_API_URL` | Backend API base URL (baked in at build) |

---

### Free tier limitations on Render

| Limit | Detail |
|---|---|
| Spin-down | Free Web Services spin down after 15 min of inactivity; first request after idle takes ~30 s |
| Build minutes | 500 free build-minutes/month (shared across all services) |
| Bandwidth | 100 GB/month outbound |
| Static sites | Always-on, no spin-down |

---

## Self-Hosted Production

### Build the frontend

```bash
cd frontend
VITE_API_URL=https://api.yourdomain.com/api npm run build
```

Outputs a fully static site to `frontend/dist/`. Deploy to any static host (Nginx, Apache, Vercel, Netlify, S3+CloudFront).

### Nginx example (serves SPA + proxies API)

```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;

    # Vue.js SPA
    root /var/www/zeno-crm/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Proxy API requests to PHP backend
    location /api/ {
        proxy_pass http://127.0.0.1:8080/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Production environment variables

| Variable | Dev default | Production recommendation |
|---|---|---|
| `JWT_SECRET` | `zeno_crm_jwt_secret_key_2024` | 32+ character random string |
| `DB_PASS` | `zeno_pass` | Strong unique password |
| `MYSQL_ROOT_PASSWORD` | `rootpassword` | Strong unique password |
| `FRONTEND_URL` | *(open)* | Exact frontend origin URL |
| `VITE_API_URL` | *(dev proxy)* | `https://api.yourdomain.com/api` |

---

## API Reference

All endpoints except `/api/auth/login` require:
```
Authorization: Bearer <jwt_token>
```

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/login` | Login — returns JWT + user profile |
| `POST` | `/api/auth/logout` | Logout |

### Dashboard

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/dashboard` | KPIs, opportunities by stage, recent leads, upcoming tasks |

### Accounts

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/accounts` | List all accounts |
| `POST` | `/api/accounts` | Create account |
| `GET` | `/api/accounts/{id}` | Get single account |
| `PUT` | `/api/accounts/{id}` | Update account |
| `DELETE` | `/api/accounts/{id}` | Delete account |

### Contacts

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/contacts` | List all contacts |
| `POST` | `/api/contacts` | Create contact |
| `GET` | `/api/contacts/{id}` | Get single contact |
| `PUT` | `/api/contacts/{id}` | Update contact |
| `DELETE` | `/api/contacts/{id}` | Delete contact |

### Leads

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/leads` | List all leads |
| `POST` | `/api/leads` | Create lead |
| `GET` | `/api/leads/{id}` | Get single lead |
| `PUT` | `/api/leads/{id}` | Update lead |
| `DELETE` | `/api/leads/{id}` | Delete lead |
| `POST` | `/api/leads/{id}/convert` | Convert lead → Contact + Account + Opportunity |
| `POST` | `/api/leads/{id}/promote_support` | Create Support Ticket from lead |

**Convert lead request body:**
```json
{
  "create_account": true,
  "account_name": "Acme Corp",
  "create_opportunity": true,
  "opportunity_name": "Acme Deal",
  "opportunity_stage": "prospecting"
}
```

### Opportunities

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/opportunities` | List all opportunities |
| `POST` | `/api/opportunities` | Create opportunity |
| `GET` | `/api/opportunities/{id}` | Get single opportunity |
| `PUT` | `/api/opportunities/{id}` | Update opportunity (used for Kanban stage moves) |
| `DELETE` | `/api/opportunities/{id}` | Delete opportunity |

### Meetings

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/meetings` | List all meetings |
| `POST` | `/api/meetings` | Create meeting (include `contact_ids[]` for attendees) |
| `GET` | `/api/meetings/{id}` | Get single meeting |
| `PUT` | `/api/meetings/{id}` | Update meeting |
| `DELETE` | `/api/meetings/{id}` | Delete meeting |

### Tasks

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/tasks` | List all tasks |
| `POST` | `/api/tasks` | Create task |
| `GET` | `/api/tasks/{id}` | Get single task |
| `PUT` | `/api/tasks/{id}` | Update task |
| `DELETE` | `/api/tasks/{id}` | Delete task |

### Support Tickets

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/support` | List all support tickets |
| `POST` | `/api/support` | Create support ticket |
| `GET` | `/api/support/{id}` | Get single ticket |
| `PUT` | `/api/support/{id}` | Update ticket (status, resolution, assignee) |
| `DELETE` | `/api/support/{id}` | Delete ticket |

### Users *(admin only)*

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/users` | List all users |
| `POST` | `/api/users` | Create user |
| `GET` | `/api/users/{id}` | Get single user |
| `PUT` | `/api/users/{id}` | Update user (password, role, permissions) |
| `DELETE` | `/api/users/{id}` | Delete user |

### Standard response shapes

**Success:**
```json
{
  "success": true,
  "data": { "..." },
  "message": "OK"
}
```

**Error:**
```json
{
  "success": false,
  "message": "Unauthorized",
  "error": "..."
}
```

---

## Database Schema

**Tables:** `users`, `accounts`, `contacts`, `leads`, `opportunities`, `meetings`, `tasks`, `support_tickets`, `meeting_contacts`

### Key relationships

```
users (1)
  ├─> accounts.created_by
  ├─> leads.assigned_to
  ├─> opportunities.assigned_to
  ├─> meetings.assigned_to
  ├─> tasks.assigned_to
  └─> support_tickets.assigned_to

accounts (1)
  ├─> contacts (1:M) via account_id
  ├─> opportunities (1:M) via account_id
  └─> support_tickets (1:M) via account_id

contacts (1)
  ├─> opportunities (1:M) via contact_id
  ├─> meetings (M:M) via meeting_contacts junction
  ├─> tasks (1:M) via contact_id
  └─> support_tickets (1:M) via contact_id

leads (1)
  ├─> opportunities via lead_id
  ├─> support_tickets via lead_id
  └─> conversion tracking:
        converted_contact_id / converted_account_id /
        converted_opportunity_id / converted_support_id / converted_at
```

### Seed data included in `schema.sql`

| Table | Rows |
|---|---|
| users | 5 (1 admin + 4 users) |
| accounts | 5 |
| contacts | 10 |
| leads | 8 |
| opportunities | 8 |
| meetings | 5 |
| tasks | 5 |
| support_tickets | 4 |

---

## Default Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@zenocrm.com | Admin@123 |
| Sales User | sarah.connor@zenocrm.com | User@123 |

> Change all passwords before any public deployment.
