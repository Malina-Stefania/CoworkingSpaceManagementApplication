# CoworkingSpaceManagementApplication

Web application for managing coworking spaces using **PHP** and **Microsoft SQL Server**, developed for a **Database Systems course**.
The project demonstrates relational database design and integration with a web-based interface.

---

# Project Overview

This application was created to demonstrate how a relational database can be designed and integrated into a web application.
The system manages data related to coworking spaces and allows interaction with the database through a simple PHP interface.

The project focuses on the following concepts:

* relational database design
* SQL query implementation
* database connectivity using PHP
* structuring a basic web application
* administration of database records through a web interface

---

# Technologies Used

* **PHP** – backend application logic
* **Microsoft SQL Server** – relational database management
* **PDO (PHP Data Objects)** – database connection layer
* **HTML / CSS** – user interface
* **SQL** – database creation and queries

---

# Project Structure

```
CoworkingSpaceManagementApplication
│
├── app
│   ├── admin/            # Administration pages
│   ├── layout/           # Layout components (header, footer)
│   ├── config.php        # Database connection configuration
│   └── *.php             # Application pages
│
├── database
│   ├── SQLQuery1.sql
│   ├── SQLQuery2.sql
│   └── SQLQuery3.sql
│
├── docs
│   ├── Proiect BD.pdf    # Project documentation
│   └── BD1.png           # Database diagram
│
└── README.md
```

---

# Database

The application uses a **Microsoft SQL Server** database called:

```
SpatiiCoworking
```

The SQL scripts located in the `database` folder contain:

* table creation
* relationships between tables
* queries used in the application

These scripts should be executed before running the application.

---

# Installation and Setup

## 1. Clone the repository

```
git clone https://github.com/username/CoworkingSpaceManagementApplication.git
```

---

## 2. Create the database

Open **SQL Server Management Studio** and create a new database:

```
SpatiiCoworking
```

Then run the SQL scripts located in the `database` folder.

---

## 3. Configure database connection

Open the file:

```
coworking/config.php
```

Modify the database connection settings depending on your local configuration:

```php
$serverName = "localhost\\SQLEXPRESS";
$database   = "SpatiiCoworking";
$username   = "user";
$password   = "password";
```

---

## 4. Run the application

Place the project folder inside your local web server directory (for example **XAMPP**, **Apache**, or **IIS**).

Example:

```
xampp/htdocs/
```

Open the application in your browser:

```
http://localhost/coworking
```

---

# Features

* PHP connection to a SQL Server database
* relational database design
* SQL queries for data manipulation
* organized project structure
* simple administrative interface

---

# Documentation

The complete project documentation is available in:

```
docs/Proiect BD.pdf
```

# Academic Purpose

This project was developed for academic purposes to demonstrate:

* relational database design
* SQL query implementation
* integration of a database with a web application
* basic backend development using PHP

---

# Author

Student project developed for the **Database Systems course**.

