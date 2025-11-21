# Docker Stack Deployment Guide

This guide covers deploying the ITV Telephone API Docker stack locally for development.

## 🏗️ Stack Overview

The Docker stack consists of the following services:

- **itv-api-init**: Initialization container (runs migrations)
- **itv-api-web**: PHP-FPM application server
- **itv-api-worker**: Laravel Horizon queue worker with supervisor
- **itv-api-nginx**: Nginx web server (reverse proxy)
- **itv-api-mysql**: MySQL 8.0 database
- **itv-api-redis**: Redis 7 cache/queue backend

## 📋 Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git
- 4GB+ RAM (recommended)
- 10GB+ disk space

## 🚀 Quick Start

### 1. Clone and Setup
```bash
# Navigate to project root
cd /path/to/itv-telephone-api

# Copy environment file
cp docker/.env.example docker/.env  # If .env doesn't exist

# Navigate to docker directory
cd docker
```

### 2. Configure Environment
Edit the `.env` file with your local settings:

```bash
# Application Configuration
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=itv-api-mysql
DB_PORT=3306
DB_DATABASE=telephone_api_multi
DB_USERNAME=admin
DB_PASSWORD="your_secure_password_here"
DB_HOST_READ_ONLY=itv-api-mysql

# Redis Configuration
REDIS_HOST=itv-api-redis
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis

# MySQL Docker Environment
MYSQL_ROOT_PASSWORD=secret
MYSQL_DATABASE=telephone_api_multi
MYSQL_USER=admin
MYSQL_PASSWORD="your_secure_password_here"
```

### 3. Deploy Stack
```bash
# Build and start all services
docker-compose up -d

# Watch logs (optional)
docker-compose logs -f
```

### 4. Verify Deployment
```bash
# Check all services are running
docker-compose ps

# Test application
curl http://localhost
```

## 📚 Detailed Deployment Steps

### Step 1: Environment Preparation

1. **Check Docker Installation**
   ```bash
   docker --version
   docker-compose --version
   ```

2. **Verify Resources**
   ```bash
   # Check available disk space
   df -h

   # Check available memory
   free -h
   ```

### Step 2: Configuration Review

1. **Database Configuration**
   - Default database: `telephone_api_multi`
   - Default user: `admin`
   - Port exposed: `3306` (for external tools)

2. **Application Ports**
   - Web Application: `http://localhost:80`
   - PHP-FPM: `localhost:9000` (internal)
   - MySQL: `localhost:3306`
   - Redis: `localhost:6379`

3. **Volume Mounts**
   - Application files: `itv-api-app-files`
   - PHP logs: `itv-api-php-logs`
   - Worker logs: `itv-api-worker-logs`
   - Configuration files: `./config/*` (mounted for development)

### Step 3: Build and Deploy

1. **Clean Build (if needed)**
   ```bash
   # Remove existing containers and volumes
   docker-compose down -v
   
   # Remove images (forces rebuild)
   docker-compose build --no-cache
   ```

2. **Standard Build**
   ```bash
   # Build images
   docker-compose build
   
   # Start services
   docker-compose up -d
   ```

3. **Monitor Initialization**
   ```bash
   # Watch init container (runs migrations)
   docker-compose logs -f itv-api-init
   
   # Should see: "Migrations completed!"
   ```

### Step 4: Post-Deployment Verification

1. **Service Health Check**
   ```bash
   # Check all containers are healthy
   docker-compose ps
   
   # Expected status: "Up" for all services except init (Exited 0)
   ```

2. **Application Tests**
   ```bash
   # Test web server
   curl -I http://localhost
   
   # Test database connection
   docker-compose exec itv-api-web php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK\n';"
   
   # Test Redis connection
   docker-compose exec itv-api-web php artisan tinker --execute="Redis::ping(); echo 'Redis OK\n';"
   ```

3. **Queue Worker Verification**
   ```bash
   # Check Horizon status
   docker-compose logs itv-api-worker | grep horizon
   
   # Should see Laravel Horizon workers starting
   ```

## 🛠️ Common Operations

### Starting/Stopping Services

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart itv-api-web

# View service logs
docker-compose logs -f itv-api-web
```

### Database Operations

```bash
# Access MySQL CLI
docker-compose exec itv-api-mysql mysql -u admin -p telephone_api_multi

# Run migrations
docker-compose exec itv-api-web php artisan migrate

# Seed database
docker-compose exec itv-api-web php artisan db:seed

# Reset database
docker-compose exec itv-api-web php artisan migrate:fresh --seed
```

### Development Workflow

```bash
# Access application container
docker-compose exec itv-api-web bash

# Install Composer dependencies
docker-compose exec itv-api-web composer install

# Clear application cache
docker-compose exec itv-api-web php artisan cache:clear
docker-compose exec itv-api-web php artisan config:clear
docker-compose exec itv-api-web php artisan route:clear

# Generate application key
docker-compose exec itv-api-web php artisan key:generate
```

### Queue Management

```bash
# Monitor Horizon dashboard
# Visit: http://localhost/horizon

# Check queue status
docker-compose exec itv-api-web php artisan horizon:status

# Restart Horizon workers
docker-compose restart itv-api-worker
```

## 🔧 Configuration Management

### Updating Configuration Files

Configuration files are mounted from `./config/` directory:

```bash
# Edit PHP configuration
nano config/php.ini

# Edit PHP-FPM configuration  
nano config/php-fpm.conf

# Edit Nginx configuration
nano config/nginx-local.conf

# Edit Supervisor configuration
nano config/supervisord.conf

# Restart affected services
docker-compose restart itv-api-web itv-api-nginx itv-api-worker
```

### Environment Variables

```bash
# Edit environment file
nano .env

# Restart application containers to pick up changes
docker-compose restart itv-api-web itv-api-worker
```

## 🐛 Troubleshooting

### Common Issues

1. **Port Already in Use**
   ```bash
   # Check what's using port 80
   sudo lsof -i :80
   
   # Kill process or change port in docker-compose.yml
   ```

2. **Database Connection Failed**
   ```bash
   # Check MySQL container logs
   docker-compose logs itv-api-mysql
   
   # Verify environment variables
   docker-compose exec itv-api-web env | grep DB_
   ```

3. **Nginx 502 Bad Gateway**
   ```bash
   # Check PHP-FPM is running
   docker-compose logs itv-api-web
   
   # Verify PHP-FPM is listening on correct port
   docker-compose exec itv-api-web netstat -tlnp | grep 9000
   ```

4. **Queue Jobs Not Processing**
   ```bash
   # Check worker container
   docker-compose logs itv-api-worker
   
   # Verify Redis connection
   docker-compose exec itv-api-web php artisan queue:monitor
   ```

### Health Checks

```bash
# Comprehensive health check script
#!/bin/bash

echo "=== Docker Compose Services ==="
docker-compose ps

echo "=== Application Health ==="
curl -s -o /dev/null -w "%{http_code}" http://localhost

echo "=== Database Connection ==="
docker-compose exec -T itv-api-web php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB: OK'; } catch(Exception \$e) { echo 'DB: FAILED - ' . \$e->getMessage(); }"

echo "=== Redis Connection ==="
docker-compose exec -T itv-api-web php artisan tinker --execute="try { Redis::ping(); echo 'Redis: OK'; } catch(Exception \$e) { echo 'Redis: FAILED - ' . \$e->getMessage(); }"

echo "=== Queue Status ==="
docker-compose exec -T itv-api-web php artisan horizon:status
```

### Log Locations

```bash
# Application logs
docker-compose logs itv-api-web

# Worker/Horizon logs
docker-compose logs itv-api-worker

# Nginx logs
docker-compose logs itv-api-nginx

# MySQL logs
docker-compose logs itv-api-mysql

# Redis logs
docker-compose logs itv-api-redis

# Volume-mounted logs
docker-compose exec itv-api-web ls -la /var/log/
```

## 🔄 Maintenance

### Updating Dependencies

```bash
# Update Composer dependencies
docker-compose exec itv-api-web composer update

# Update NPM dependencies
docker-compose exec itv-api-web npm update

# Rebuild assets
docker-compose exec itv-api-web npm run build
```

### Backup and Restore

```bash
# Backup database
docker-compose exec itv-api-mysql mysqldump -u admin -p telephone_api_multi > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
docker-compose exec -T itv-api-mysql mysql -u admin -p telephone_api_multi < backup_file.sql

# Backup volumes
docker run --rm -v itv-api-app-files:/backup-source -v $(pwd):/backup ubuntu tar czf /backup/app-files-backup.tar.gz -C /backup-source .
```

### Cleanup

```bash
# Remove stopped containers
docker-compose down

# Remove volumes (CAUTION: This deletes all data!)
docker-compose down -v

# Clean up unused Docker resources
docker system prune -a
```

## 📊 Performance Tuning

### Current Configuration (Optimized for High Load)

This setup is optimized for handling **500-1000 concurrent API calls** on:
- **Hardware**: 2-core CPU, 4GB RAM per instance
- **Deployment**: 2 replicas (total: 4 cores, 8GB RAM)
- **Target**: ~50 concurrent PHP-FPM processes per instance

### PHP Configuration Optimizations

**Memory Management:**
- `memory_limit = 128M` (allows ~25-30 processes per 4GB)
- `pm.max_children = 25` per instance (50 total across replicas)
- Process recycling: `pm.max_requests = 1000`

**Performance Tuning:**
- **OPcache enabled** with 256MB cache, 20k files
- **Realpath cache**: 4MB, 600s TTL
- **Execution timeouts**: 15s max execution, 30s input timeout
- **FastCGI optimizations**: 64k buffers, connection pooling

**Security Hardening:**
- `expose_php = Off` (hide PHP version)
- `display_errors = Off` (production safety)
- Process isolation and file extension restrictions
- Environment variable clearing

### Monitoring and Health Checks

**Built-in Monitoring:**
```bash
# PHP-FPM Status
curl http://localhost/fmp-status?json

# Health Check
curl http://localhost/health

# Performance Monitoring
./monitor-performance.sh

# Load Testing
./load-test.sh 100 60  # 100 concurrent users, 60 seconds
```

**Key Metrics to Watch:**
- **Memory Usage**: Should stay <80% of available RAM
- **Active Processes**: Should not reach `pm.max_children`
- **Queue Length**: Should remain at 0 under normal load
- **Response Time**: Target <100ms for API endpoints

### Resource Limits

Current configuration is optimized for:
- **CPU**: 2 cores minimum
- **RAM**: 4GB minimum  
- **PHP-FPM**: 25 max children per instance (optimized for 128MB memory_limit)
- **Concurrent Requests**: ~50 per instance, 100 total across 2 replicas

### Performance Testing

Run load tests to validate configuration:

```bash
# Light load test
./load-test.sh 50 30

# Medium load test  
./load-test.sh 100 60

# High load test (target capacity)
./load-test.sh 200 120

# Monitor during testing
./monitor-performance.sh 2
```

### Scaling Recommendations

**Vertical Scaling (per instance):**
- 6GB RAM → `pm.max_children = 35`
- 8GB RAM → `pm.max_children = 50`

**Horizontal Scaling:**
- Add more replicas rather than increasing instance size
- Each replica can handle ~25-50 concurrent requests optimally

**Database Optimization:**
- Use read replicas for heavy read workloads
- Implement connection pooling
- Optimize slow queries (check `slowlog`)

### Troubleshooting High Load

**If experiencing issues at 500+ concurrent requests:**

1. **Memory Issues:**
   ```bash
   # Check memory usage
   docker stats
   
   # Reduce memory per process
   # In php.ini: memory_limit = 96M
   # In php-fpm.conf: pm.max_children = 32
   ```

2. **Connection Issues:**
   ```bash
   # Increase connection limits
   # In php-fpm.conf: listen.backlog = 2048
   
   # Check system limits
   ulimit -n  # File descriptors
   sysctl net.core.somaxconn  # Socket backlog
   ```

3. **Response Time Issues:**
   ```bash
   # Check slow queries
   tail -f /var/log/php-fpm-slow.log
   
   # Monitor OPcache hit rate
   # Should be >95% in production
   ```

## 🚀 Production Considerations

This setup is optimized for **local development**. For production deployment:

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Use external database/Redis services
3. Implement proper SSL/TLS
4. Configure log aggregation
5. Set up health checks and monitoring
6. Use secrets management
7. Implement backup strategies

## 📞 Support

For issues with this Docker setup:

1. Check the troubleshooting section above
2. Review container logs: `docker-compose logs [service-name]`
3. Verify configuration files in `./config/`
4. Ensure all prerequisites are met

---

**Last Updated**: September 10, 2025  
**Docker Compose Version**: 2.0+  
**Tested Environment**: Ubuntu 24.04, Docker 27.0+
