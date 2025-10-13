# Application Installation Guide

This guide explains how to set up the application using the web-based installer.

## Installation Steps

### 1. Prerequisites

Before you begin, ensure your server meets the following requirements:
- **PHP Version**: 8.0.0 or higher
- **PHP Extensions**: `pdo_mysql`, `curl`, `mbstring`
- **Database**: A running MySQL or MariaDB server.
- **Web Server**: A web server like Nginx or Apache, with the document root pointing to the `public/` directory of this project.

### 2. Prepare Configuration

1.  **Copy the environment file**: In the project's root directory, copy the `.env.example` file to a new file named `.env`.
    ```bash
    cp .env.example .env
    ```

2.  **Edit the `.env` file**: Open the newly created `.env` file and fill in your database connection details (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) and your `KAKAO_REST_API_KEY`.

### 3. Set Directory Permissions

The web server needs write access to certain directories. Ensure the following directories are writable by the server (e.g., `chmod -R 775 storage`):
- `storage/logs/`
- `public/uploads/`

### 4. Run the Installer

1.  Open your web browser and navigate to the `install/` directory of your project. For example: `http://your-domain.com/install/`
2.  The installer will show a welcome screen. Click the **"설치 시작" (Start Installation)** button.
3.  The installer will automatically check your environment, set up the database, and import all necessary data. You can watch the progress in real-time.
4.  If the installation is successful, you will see a "설치 완료" (Installation Complete) message.

### 5. Final Step: Secure Your Application

**IMPORTANT**: After a successful installation, you **must delete the `install/` directory** from your server to prevent it from being run again.

```bash
rm -rf install/
```

Your application is now ready to use. You can access it from the main URL (e.g., `http://your-domain.com/`).
