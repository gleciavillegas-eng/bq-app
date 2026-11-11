# Burger Queen (Symfony + Docker)

A burger ordering website built with Symfony, Doctrine ORM, Twig, MariaDB, and Docker.

## Features

- **Customers:** register, log in, browse the menu, place orders, view receipts, track orders, account page
- **Staff:** manage their own products (create, edit, delete), view all products, profile and change password
- **Admin:** dashboard (total users, total staff, total records), user management (create, edit, reset password, disable, delete), full product access, orders, customers, messages
- **Security:** hashed passwords, CSRF protection, role-based access (`security.yaml`, controller checks, Twig menus), 403 Access Denied page, disabled accounts cannot log in

## Requirements

- Docker Desktop

## How to run

```bash
docker compose up -d --build
docker compose exec web composer install
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

Then import the sample data in phpMyAdmin:

1. Open http://localhost:8081 (user: `root`, password: `root`)
2. Select the `burger_queen` database
3. Click **Import** and choose `database/bq-data.sql`

## Links

- Website: http://localhost:8082
- phpMyAdmin: http://localhost:8081

## Test accounts

| Role     | Username | Password     |
|----------|----------|--------------|
| Admin    | `admin`  | `admin123`   |
| Staff    | `staff1` | `staff12345` |
| Customer | `julina` | `12345678`   |

## Role rules

| Area                         | Admin | Staff            | Customer |
|------------------------------|-------|------------------|----------|
| Admin dashboard, users, etc. | Yes   | No (403)         | No (403) |
| Products                     | All   | Own only (edit/delete) | View menu |
| Profile / change password    | Yes   | Yes              | Account page |
| Place orders                 | Yes   | Yes              | Yes      |

## Useful commands

```bash
docker compose ps                  # check running containers
docker compose stop                # stop
docker compose exec web php bin/console cache:clear
```
