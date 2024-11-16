<?php
session_start();
include '../config/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $posisi = htmlspecialchars($_POST['posisi']);

    // Prepare SQL to retrieve user info based on username and position
    $sql = "SELECT password, posisi, id FROM Pegawai WHERE username = :username AND posisi = :posisi";
    $stid = oci_parse($conn, $sql);

    // Bind parameters
    oci_bind_by_name($stid, ":username", $username);
    oci_bind_by_name($stid, ":posisi", $posisi);

    // Execute the query
    if (oci_execute($stid)) {
        // Fetch user data
        $user = oci_fetch_assoc($stid);
        
        if ($user && password_verify($password, $user['PASSWORD'])) {
            // Set session variables for authenticated user
            $_SESSION['username'] = $username;
            $_SESSION['posisi'] = $user['POSISI'];
            $_SESSION['pegawai_id'] = $user['ID']; // Store employee ID in session

            // Redirect based on role
            switch ($user['POSISI']) {
                case 'owner':
                    header("Location: ../pages/owner/dashboard.php");
                    break;
                case 'vet':
                    header("Location: ../pages/vet/dashboard.php");
                    break;
                case 'staff':
                    header("Location: ../pages/staff/dashboard.php");
                    break;
                default:
                    echo "Unauthorized access.";
                    exit();
            }
            exit();
        } else {
            // Invalid username, password, or role
            echo "<script>alert('Invalid username, password, or role.'); window.location.href='login.php';</script>";
        }
    } else {
        // SQL execution failed
        echo "Error executing query: " . oci_error($stid)['message'];
    }

    // Free resources and close the connection
    oci_free_statement($stid);
    oci_close($conn);
}
?>
