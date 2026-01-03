# FileRun Docker

This repository contains a Docker setup for FileRun (version 20220519), the last available free version of the product.

This project is based on the original work by [mrizkihidayat66](https://github.com/mrizkihidayat66). This fork introduces significant improvements for stability, security, and ease of use.

## Key Changes and Improvements

This version has been heavily refactored from the original to incorporate modern Docker best practices.
- **Multi-Stage Builds**: The `Dockerfile` now uses a multi-stage build to create a smaller, cleaner final image by separating build-time dependencies from runtime requirements.
- **Dynamic User ID/GID Mapping**: The setup now automatically maps the container's internal user (`www-data`) to your host user's UID and GID. This completely solves file permission issues on bind-mounted volumes.
- **Simplified `docker-compose.yaml`**: The compose file has been simplified to a minimal, working setup.
  - The `web` service is now built locally from the `Dockerfile` instead of using a pre-built image.
  - The `db` service now uses the `linuxserver/mariadb` image, which natively handles UID/GID mapping for database files.
  - Unnecessary services (`tika`, `elasticsearch`) have been removed for a leaner setup.
- **Nginx Fixes**:
  - The "413 Request Entity Too Large" error has been fixed by setting `client_max_body_size`.
  - SSL handling has been removed from the Nginx configuration, making the container ideal for deployment behind a reverse proxy that handles HTTPS.

## Configuration

Before running the project, you **must** customize the following in your `docker-compose.yml` file:

1.  **Database Passwords and Credentials**:
    *   Update `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, and `MYSQL_PASSWORD` in the `db` service.
    *   Set `FR_DB_PASS` in the `web` service to match the `MYSQL_PASSWORD` from the `db` service.

2.  **User ID (PUID) and Group ID (PGID)**:
    *   `PUID` and `PGID` define the user and group ownership for files created by the containers on your host system (in bind-mounted volumes).
    *   The default `1000` is common, but it's strongly recommended to set these to your actual host user's UID and GID to avoid permission conflicts. You can find your UID/GID by running `id -u` and `id -g` in your terminal.

3.  **Volume Paths**:
    *   `db` service: `- ./db:/config` maps your local `./db` directory (where MariaDB will store its data) to the container's `/config` directory. Adjust `./db` to your preferred host path.
    *   `web` service: `- ./user-files:/user-files` and `- ./config:/config` map FileRun's persistent data and configuration files. Adjust `./user-files` and `./config` to your preferred host paths.

4.  **Port Mapping**:
    *   `web` service: `ports: - 5003:80` maps port `80` inside the container to port `5003` on your host. Change `5003` if it conflicts with another service or you prefer a different port.

5.  **Reverse Proxy Requirement**:
    *   This setup expects to run **behind an SSL-terminating reverse proxy**. The `web` container itself serves traffic over plain HTTP on port 80. Ensure your reverse proxy is correctly configured to forward requests (e.g., from `https://yourdomain.com`) to `http://localhost:5003` (or your chosen host port).

## Running the Project

1.  Ensure you have Docker and Docker Compose installed.
2.  Clone the repository.
3.  After customizing your `docker-compose.yml` based on the Configuration section, run the following command from the project's root directory:

```bash
docker-compose up --build -d
```
This command will build the `web` image and start all services in detached mode.

The FileRun instance will be available via your configured reverse proxy. If accessing directly (not recommended for production without a reverse proxy), it will be at `http://localhost:5003`.

### Docker Compose Configuration

Below is the current `docker-compose.yml` configuration.

```yaml
services:
  db:
    image: lscr.io/linuxserver/mariadb:11.4.8
    container_name: mariadb
    environment:
      PUID: 1000
      PGID: 1000
      TZ: Etc/UTC
      MYSQL_ROOT_PASSWORD: YOUR_ROOT_DATABASE_PASSWORD
      MYSQL_DATABASE: filerun-db
      MYSQL_USER: filerun-user
      MYSQL_PASSWORD: YOUR_DATABASE_USER_PASSWORD
    volumes:
      - ./db:/config
    restart: unless-stopped

  web:
    build: .
    container_name: filerun_web
    restart: always
    environment:
      FR_DB_HOST: db
      FR_DB_PORT: 3306
      FR_DB_NAME: filerun-db
      FR_DB_USER: filerun-user
      FR_DB_PASS: YOUR_DATABASE_USER_PASSWORD
      PUID: 1000
      PGID: 1000
    depends_on:
      - db
    ports:
      - 5003:80
    volumes:
      - ./user-files:/user-files
      - ./config:/config
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost"]
      interval: 30s
      timeout: 10s
      retries: 3
```

### Gratitude and Support
We extend our sincere gratitude to FileRun, LDA for their hard work and dedication in developing such a useful platform. If you find FileRun beneficial, we highly recommend supporting them by purchasing a license for the latest version. For pricing details, please visit the [FileRun Pricing Page](https://filerun.com/pricing).
