# Surplus Food Rescue & Donation Network

This is the **Web Programming Project** developed by:

- **Badhan Roy** — ID: `0112420160`
- **Nuzaima Hasan** — ID: `0112420662`
- **Aheer Afreed Ronon** — ID: `0112420051`
- **Tanzila Nousin Saba** — ID: `0112420072`

---

## About the Project

Surplus Food Rescue is a web-based platform designed to reduce food waste by connecting food donors, charity organizations, and volunteer riders. Restaurants and caterers often have surplus food that could still be used, while many people and communities need food support. This project aims to make it easier to connect those who have extra food with organizations that can distribute it to people in need.

The platform brings food donation posts, pickup scheduling, delivery tracking, and communication into one place. It also includes features such as expiry countdowns and OTP-based handover verification to help make the food rescue process more organized.

## Features

### Admin

- Approve food donor and charity accounts.
- Add or remove volunteer riders.
- Manage food categories.
- Set food safety rules and expiry windows.
- Monitor food rescue activity and food waste saved.
- Generate monthly impact reports in PDF format.
- Send urgent surplus food alerts and awareness campaigns.

### Food Donors

- Register and log in.
- Post surplus food with quantity, photos, pickup location, and available time.
- Set an expiry window for each food post.
- Track claims and pickup status.
- View donation history and impact summaries.
- Download donation receipts in PDF format.
- Chat with charities and volunteer riders.

### Charity Organizations

- Browse and claim available food donations.
- Schedule pickups within the food's expiry window.
- Confirm received quantities and food quality.
- Record distribution details and the number of people served.
- Report spoiled food, missing quantities, or delivery issues.
- Generate distribution reports in PDF format.
- Chat with donors and administrators.

### Volunteer Riders

- Register and log in.
- View assigned pickup and delivery tasks.
- Update pickup and delivery status.
- Verify handovers using OTP.
- Report delays and vehicle issues.
- Communicate with donors and charities through chat.

### Guests

- Read awareness content about food waste.
- View the total meals rescued counter.
- Access campaign information and the contact page.

### Additional Features

- **Expiry countdown:** Displays the remaining time before a food donation expires.
- **OTP verification:** Helps verify food handovers between the involved parties.
- **Chat system:** Supports communication between donors, charities, riders, and administrators.

## Technologies Used

- **HTML, CSS** — page structure and styling
- **JavaScript** — frontend interactions
- **PHP** — backend logic
- **MySQL** — database
- **XAMPP** — local development environment
- **Apache** — local web server
- **phpMyAdmin** — database setup and management

## Running the Project Locally

The project is designed to run locally using XAMPP.

### 1. Start XAMPP

Install [XAMPP](https://www.apachefriends.org/) if you haven't already. Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### 2. Put the Project in `htdocs`

Copy or extract the project folder into:

```text
C:\xampp\htdocs\SurplusFoodRescue
```

### 3. Import the Database

1. Open [phpMyAdmin](http://localhost/phpmyadmin).
2. Select the database and open the **Import** tab.
3. Choose the project's `.sql` file and import it.

### 4. Check the Database Connection

Open the project's database configuration file and make sure the connection settings match your local MySQL setup. For example, if the file uses these variables:

```php
$host = 'localhost';
$dbname = 'surplusfoodrescue';
$user = 'root';
$pass = '';
```

Update the values if your database configuration is different.

### 5. Open the Project

With Apache and MySQL running, visit:

[http://localhost/SurplusFoodRescue/](http://localhost/SurplusFoodRescue/)

The project should now open in your browser. If necessary, open the main page, such as `index.php` or `index.html`.



## Our Goal

The goal of Surplus Food Rescue is to make surplus food easier to donate and distribute before it goes to waste. By connecting donors, charities, and volunteer riders through a single platform, we hope to make food rescue more organized and help more people access available food.

---


