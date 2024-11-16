<?php
// Check if the user is logged in and has the correct role
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['posisi'])) {
    // If not logged in or position not set, redirect to login page
    header("Location: ../../auth/restricted.php");
    exit();
}

// Set the current page for active navigation
$currentPage = isset($currentPage) ? $currentPage : ''; // Default to empty if not set
?>

<nav class="navbar navbar-expand-lg navbar-light navbar-container">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="../../public/img/icon.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            <span class="navbar-title">Pemay</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>"
                        href="/pemay/pages/owner/dashboard.php">Dashboard</a>
                </li>

                <?php if ($_SESSION['posisi'] === 'owner'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'users') ? 'active' : ''; ?>"
                            href="/pemay/pages/owner/users.php">Users</a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'stock') ? 'active' : ''; ?>"
                        href="/pemay/pages/Stock/stock.php">Stok</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'kategori') ? 'active' : ''; ?>"
                        href="/pemay/pages/Kategori/kategori.php">Kategori</a>
                </li>
            </ul>
        </div>

        <form action="../../auth/logout.php" method="post">
            <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
        </form>
    </div>
</nav>
