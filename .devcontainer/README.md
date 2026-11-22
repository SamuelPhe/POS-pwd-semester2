# POS — GitHub Codespaces

This setup runs the original PHP application in Apache and restores the POS schema into a local MariaDB container.

## Open the web application

1. Open this repository on GitHub.
2. Select **Code → Codespaces → Create codespace on main** (or resume the existing POS Codespace).
3. Wait for the containers to finish starting.
4. Open the **Ports** tab at the bottom of the Codespaces editor.
5. Find **POS web (private)**, port `8080`, then choose **Open in Browser**.
6. The login page is available at `/login.php`.

The web service serves the original PHP files from the repository root. MariaDB imports `database/schema.sql` into a separate Codespaces database named `pos`.

## Login notes

The SQL schema has no seeded users, so no demo username or password is supplied. The login page can be opened, but successful authentication requires a local account in the `users` table with a PHP `password_hash()` password and a valid `admin` or `kasir` role.

Keep port `8080` **Private**. This database is an isolated coursework copy, not a production POS system.

## Stop and resume

Stop the Codespace when finished. When it resumes, the web and database services start again. The MariaDB named volume remains while the Codespace and volume are retained.
