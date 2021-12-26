# How to install

**Design for Ubuntu OS** (Maybe support other linux system too)

## Method 1: Manually Install On Ubuntu System

### Requirement

- Ubuntu Server

### Instruction

- Copy Install.sh, PrinterRoomSystem to Installation Path
- In Installation Path
  - Run `chmod +x ./Install.sh`
  - Run `sudo ./Install.sh`
  - Configuration As Prompt
- System will available on localhost:80

## Method 2: Use Docker Compose

### Requirement

- Docker (with Docker Compose)

### Instruction

- Copy PrinterRoomSystem, Dockerfile, docker-compose.yml and .env.template to Installation Path
- Rename .env.template to .env and set Environment Variable (Warning: If Change Value in .env After run docker-compose Must Update Corresponding Data e.g. Database Host, Database Password in "/home/PrinterRoomSystem/.env", MySQL Database And PhpMyAdmin Container Name In apache2 PrinterRoomSystem Site Configuration Manually)
- Run `docker-compose up -d`
- System will available on localhost port 80

## Default System Admin Password

**User:** `root`

**Password:** `Suankularb138`

**Warning: Please Change Password From Default Password Immediately**
**Warning: HTTPS is not enable please enable with Certificate**
