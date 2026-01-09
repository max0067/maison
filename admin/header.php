<header class="admin-header">
    <div class="header-content">
        <div class="logo">
            <h2>🏡 Maison du Soleil</h2>
        </div>
        <nav class="admin-nav">
            <a href="dashboard.php">Tableau de bord</a>
            <a href="reservations.php">Réservations</a>
            <a href="chambres.php">Chambres</a>
            <a href="contenus.php">Contenus</a>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </nav>
        <div class="user-info">
            👤 <?php echo e($_SESSION['admin_username']); ?>
        </div>
    </div>
</header>
