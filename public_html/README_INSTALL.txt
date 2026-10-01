========================================================================
           ALIXDEAL SHOPPING - 1-CLICK WEB & HOSTING INSTALL GUIDE
========================================================================

Aapke cPanel ya Web Hosting par is web package ko install karne ke 3 simple steps:

STEP 1: Files Upload Karein
---------------------------------------------
1. AI Studio se "Export to ZIP" download karein.
2. ZIP ke andar se `public_html` folder ke sabhi files aur folders ko apne 
   cPanel File Manager me jakar apne domain ke `public_html` me upload / extract karein.

Files structure aisa dikhna chahiye:
public_html/
   ├── index.php
   ├── install/
   │     ├── index.php
   │     └── schema.sql
   ├── admin/
   │     ├── index.php
   │     ├── login.php
   │     ├── products.php
   │     ├── categories.php
   │     ├── orders.php
   │     └── settings.php
   ├── api/
   │     ├── products.php
   │     ├── categories.php
   │     └── orders.php
   └── uploads/

STEP 2: MySQL Database Banayein
---------------------------------------------
1. cPanel me "MySQL Databases" ya "MySQL Database Wizard" me jayein.
2. Ek naya database banayein (Jaise: `user_alixdeal`).
3. Ek naya user banayein (Jaise: `user_dbuser`) aur uska strong password set karein.
4. User ko Database se jodkar "ALL PRIVILEGES" grant karein.

STEP 3: Browser Me Domain Open Karein
---------------------------------------------
1. Apne browser me domain kholein: `https://yourdomain.com`
2. Auto-Installer khul jayega:
   - Step 1: System requirements & file permissions auto check hongi (Green tick aayega).
   - Step 2: Database Host (localhost), Database Name, DB User, DB Password aur apna naya Admin Username & Password enter karein.
   - Click "Start 1-Click Install".
3. System automatic saare SQL tables, initial products, categories aur settings import kar dega!

ADMIN PANEL:
---------------------------------------------
Aapka Admin Panel is URL par live rahega:
`https://yourdomain.com/admin/`

Yahan se aap:
- Naye products add/edit/delete kar sakte hain (Images, Pricing, Deals, Stock).
- Categories manage kar sakte hain.
- Website aur Android app se aane wale sabhi orders dekh sakte hain.
- Order status update kar sakte hain (Pending, Processing, Shipped, Delivered).

ANDROID APP INTEGRATION (REST API):
---------------------------------------------
Aapke Android App ke liye REST APIs ready hain:
- Products: `https://yourdomain.com/api/products.php`
- Categories: `https://yourdomain.com/api/categories.php`
- Order Place: `https://yourdomain.com/api/orders.php`
========================================================================
