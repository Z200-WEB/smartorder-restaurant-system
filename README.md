# SmartOrder — AI-Powered Restaurant Ordering System

A QR code-based self-ordering system for restaurants, built with PHP, MySQL, and Gemini AI.

🔗 **Live Demo:** https://smartorder-restaurant-system.onrender.com/index.php?tableNo=1
🔗 **Management Dashboard:** https://smartorder-restaurant-system.onrender.com/management.php

---

## What Makes This Different

It's a QR-based ordering system — but the interesting part is the AI. Customers can ask about the menu in English, Japanese, or Chinese, and the AI responds using real menu data from the database, not hallucinated answers.

このシステムはQRコードで注文できるだけでなく、AIが実際のデータベースのメニューを元に日本語・英語・中国語で質問に答えます。架空のメニューを答えるのではなく、実データに基づいた回答を返します。

---

## Features / 機能一覧

### 🤖 AI Chat (Gemini 2.5 Flash)
- Customers can ask the AI anything about the menu in any language
- AI reads directly from the database — no hallucinated menu items
- Auto language detection: responds in the same language the customer used (JA / EN / ZH)
- Staff can also use the chat to answer customer questions

### 🌐 Multilingual Support
- Language switcher in the header (JP / EN / 中)
- Menu display translates dynamically via Gemini API
- AI chat responds automatically in the customer's language

### 📱 Customer Ordering (QR Code)
- Each table has a unique QR code
- Mobile-first responsive design
- Browse menu by category with search
- Add items to cart, adjust quantities, and place orders
- Real-time wait time estimate based on current orders

### 🎯 Smart UX Features
- **Stamp Card** — customers earn stamps with each order
- **Lucky Roulette** — AI randomly picks a recommended dish
- **Mascot (Live2D)** — animated character on desktop/tablet (hidden on mobile for usability)
- **Floating buttons** — Staff chat and Lucky Roulette accessible at all times
- **Last order alert** — banner appears when closing time approaches

### 🖥️ Staff Management Dashboard
- Real-time order view with auto-refresh every 10 seconds
- Mark orders as paid
- View order details per table

### ⚙️ Admin Panel
- Add, edit, delete menu items and categories
- Set items as popular / new / spicy
- Manage table QR codes

---

## Tech Stack / 技術スタック

| Category | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP 8.2 |
| Database | MySQL / MariaDB |
| AI | Google Gemini 2.5 Flash (via Gemini API) |
| Deployment | Render (Free tier) |
| Mascot | Live2D (desktop/tablet only) |
| Version Control | Git / GitHub |
| QR Code | goqr.me API |

---

## How It Works / システムの仕組み

**Customer Flow**
1. Scan QR code at the table → opens ordering page on smartphone
2. Browse menu by category or use search
3. Ask the AI anything: "What's popular?" / "Do you have vegetarian options?" (in any language)
4. Add items to cart → place order
5. Earn stamps, spin the Lucky Roulette for suggestions

**Staff Flow**
1. Management page shows all active orders in real-time
2. Respond to customer AI chat messages
3. Mark orders as paid when customers check out

---

## System Architecture / アーキテクチャ

```
[Customer Smartphone]
        |
   QR Code Scan
        |
        v
[Render Cloud Server]
   PHP 8.2
        |
        v
[MySQL Database]  ←──→  [Gemini AI API]
        |                      |
        v                      v
[Management Dashboard]   [AI Chat Response]
   (Staff Browser)       (Real menu data)
```

---

## Project Structure / プロジェクト構成

```
smartorder/
├── index.php              # Customer ordering page
├── gemini_chat.php        # AI chat API (Gemini 2.5 Flash)
├── translate_menu.php     # Menu translation API (EN/ZH)
├── logic.php              # Menu data API
├── checkout.php           # Order submission
├── management.php         # Staff order management
├── process_payment.php    # Payment processing
├── admin.php              # Admin dashboard
├── qr.php                 # QR code generator
├── login.php / logout.php # Authentication
├── pdo.php                # Database connection
├── waifu-tips.json        # Live2D mascot config
└── database_utf8.sql      # Database schema & seed data
```

---

## Database Schema / データベース構成

| Table | Description |
|---|---|
| sCategory | Menu categories |
| sItem | Menu items (name, price, description, state, is_popular, is_new, is_spicy) |
| sManagement | Order sessions per table |
| sOrder | Individual order items and quantities |

---

## Local Development / ローカル開発

**Requirements:** XAMPP (PHP 8.x + MariaDB)

```bash
git clone https://github.com/Z200-WEB/smartorder-restaurant-system.git
```

1. Place in XAMPP htdocs: `C:\xampp\htdocs\smartorder\`
2. Import `database_utf8.sql` via phpMyAdmin
3. Set your Gemini API key in `gemini_chat.php` and `translate_menu.php`
4. Access: `http://localhost/smartorder/index.php?tableNo=1`

---

## Tools Used / 使用ツール

- **Google Gemini API** — AI chat and menu translation
- **Live2D** — Animated mascot character (desktop/tablet)
- **GitHub** — Version control
- **Render** — Cloud deployment (free tier)
- **VSCode** — Code editor
- **Claude AI** — AI coding assistant
