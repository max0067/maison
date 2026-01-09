<?php
/**
 * Script de diagnostic de la base de données
 * Vérifie l'état des tables et leur structure
 */

require_once 'config/database.php';

$results = [];
$canConnect = false;

// Test de connexion
try {
    $db = getDB();
    $canConnect = true;
    $results[] = ['success' => true, 'message' => '✓ Connexion à la base de données réussie'];
} catch (PDOException $e) {
    $results[] = ['success' => false, 'message' => '✗ Erreur de connexion : ' . $e->getMessage()];
}

if ($canConnect) {
    // Lister toutes les tables
    try {
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tables)) {
            $results[] = ['success' => false, 'message' => '✗ Aucune table trouvée - Installation nécessaire'];
        } else {
            $results[] = ['success' => true, 'message' => '✓ Tables trouvées : ' . implode(', ', $tables)];

            // Vérifier la structure de chaque table importante
            $expectedTables = ['admin_users', 'chambres', 'reservations', 'contenus'];

            foreach ($expectedTables as $tableName) {
                if (in_array($tableName, $tables)) {
                    try {
                        $stmt = $db->query("DESCRIBE `$tableName`");
                        $columns = $stmt->fetchAll();

                        $columnNames = array_map(function($col) {
                            return $col['Field'];
                        }, $columns);

                        $results[] = [
                            'success' => true,
                            'message' => "✓ Table '$tableName' : " . implode(', ', $columnNames)
                        ];
                    } catch (PDOException $e) {
                        $results[] = [
                            'success' => false,
                            'message' => "✗ Erreur lecture table '$tableName' : " . $e->getMessage()
                        ];
                    }
                } else {
                    $results[] = [
                        'success' => false,
                        'message' => "✗ Table '$tableName' manquante"
                    ];
                }
            }

            // Vérifier si l'admin existe
            if (in_array('admin_users', $tables)) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM admin_users");
                    $count = $stmt->fetch()['count'];

                    if ($count > 0) {
                        $stmt = $db->query("SELECT username, email FROM admin_users");
                        $admins = $stmt->fetchAll();
                        foreach ($admins as $admin) {
                            $results[] = [
                                'success' => true,
                                'message' => "✓ Admin trouvé : {$admin['username']} ({$admin['email']})"
                            ];
                        }
                    } else {
                        $results[] = [
                            'success' => false,
                            'message' => "✗ Aucun utilisateur admin trouvé"
                        ];
                    }
                } catch (PDOException $e) {
                    $results[] = [
                        'success' => false,
                        'message' => "✗ Erreur lecture admin_users : " . $e->getMessage()
                    ];
                }
            }
        }
    } catch (PDOException $e) {
        $results[] = ['success' => false, 'message' => '✗ Erreur : ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic de la base de données</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .result {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.6;
        }

        .result.success {
            background: #e8f5e9;
            border-color: #4caf50;
            color: #2e7d32;
        }

        .result.error {
            background: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }

        .actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196F3;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            color: #1565c0;
            margin-bottom: 10px;
        }

        .info-box ul {
            margin-left: 20px;
            color: #1976d2;
        }

        .info-box li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnostic de la base de données</h1>

        <div class="info-box">
            <h3>Configuration actuelle</h3>
            <ul>
                <li><strong>Serveur :</strong> <?php echo DB_HOST; ?></li>
                <li><strong>Base de données :</strong> <?php echo DB_NAME; ?></li>
                <li><strong>Utilisateur :</strong> <?php echo DB_USER; ?></li>
            </ul>
        </div>

        <div class="results">
            <?php foreach ($results as $result): ?>
                <div class="result <?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($result['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <a href="install.php" class="btn btn-primary">📦 Installer / Réinstaller</a>
            <a href="reset_admin.php" class="btn btn-secondary">🔑 Réinitialiser admin</a>
            <a href="diagnostic.php" class="btn btn-success">🔄 Rafraîchir le diagnostic</a>
        </div>
    </div>
</body>
</html>
