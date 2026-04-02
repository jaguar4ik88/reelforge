#!/usr/bin/env bash
set -e

echo "🎬 ReelForge – Setup"
echo "===================="

# --- Backend ---
echo ""
echo "→ Setting up Laravel backend..."
cd backend

if [ ! -f .env ]; then
  cp .env.example .env
  echo "  ✓ .env created from .env.example"
fi

echo "  Installing Composer dependencies..."
composer install --no-interaction --prefer-dist

php artisan key:generate

echo ""
echo "→ Backend ready."
cd ..

# --- Frontend ---
echo ""
echo "→ Setting up React frontend..."
cd frontend

if [ ! -f .env ]; then
  echo "VITE_API_URL=http://localhost:8000" > .env
  echo "  ✓ frontend/.env created"
fi

echo "  Installing npm dependencies..."
npm install

echo ""
echo "→ Frontend ready."
cd ..

echo ""
echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "  1. Start services:  docker-compose up -d"
echo "  2. Run migrations:  docker-compose exec app php artisan migrate --seed"
echo "  3. Open app:        http://localhost:5173"
echo "  4. API:             http://localhost:8000/api"
echo ""
echo "Configure S3 credentials in backend/.env before generating videos."
