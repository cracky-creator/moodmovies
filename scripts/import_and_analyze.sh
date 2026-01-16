#!/bin/bash

# Script pour importer une page TMDb puis lancer le worker IA

echo "🎬 Import TMDb page 1..."
php src/import/import_tmdb_page.php 1 || { echo "❌ Import TMDb échoué"; exit 1; }

echo "🤖 Lancement IA Worker..."
php src/import/ia_worker.php || { echo "❌ Worker IA échoué"; exit 1; }

echo "✅ Import + Analyse terminés"
