#!/usr/bin/env php
<?php

/**
 * Script de validación de migraciones
 * Verifica que todas las migraciones estén correctamente estructuradas
 */

$migrationsPath = __DIR__ . '/database/migrations';
$errors = [];
$warnings = [];

if (!is_dir($migrationsPath)) {
    echo "❌ Error: No se encuentra el directorio de migraciones\n";
    exit(1);
}

$migrationFiles = glob($migrationsPath . '/*.php');

if (empty($migrationFiles)) {
    echo "⚠️  Advertencia: No se encontraron archivos de migración\n";
    exit(0);
}

echo "🔍 Validando " . count($migrationFiles) . " migraciones...\n\n";

foreach ($migrationFiles as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    // Verificar que tenga la clase Migration
    if (!preg_match('/extends\s+Migration/', $content)) {
        $errors[] = "$filename: No extiende de Migration";
        continue;
    }
    
    // Verificar que tenga método up()
    if (!preg_match('/public\s+function\s+up\(\)/', $content)) {
        $errors[] = "$filename: No tiene método up()";
    }
    
    // Verificar que tenga método down()
    if (!preg_match('/public\s+function\s+down\(\)/', $content)) {
        $warnings[] = "$filename: No tiene método down() (recomendado para rollback)";
    }
    
    // Verificar uso de Schema
    if (preg_match('/Schema::(create|table)/', $content)) {
        if (!preg_match('/use\s+Illuminate\\\Database\\\Migrations\\\Migration/', $content)) {
            $errors[] = "$filename: Usa Schema pero no importa Migration";
        }
        if (!preg_match('/use\s+Illuminate\\\Support\\\Facades\\\Schema/', $content)) {
            $errors[] = "$filename: Usa Schema pero no importa Schema facade";
        }
    }
}

echo "\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ Todas las migraciones están correctamente estructuradas\n";
    exit(0);
}

if (!empty($errors)) {
    echo "❌ Errores encontrados:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  Advertencias:\n";
    foreach ($warnings as $warning) {
        echo "   - $warning\n";
    }
    echo "\n";
}

exit(empty($errors) ? 0 : 1);
