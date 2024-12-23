<?php
session_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();

// Pencarian pelanggan
$searchCustomer = isset($_GET['search_customer']) ? $_GET['search_customer'] : '';
$queryCustomer = "SELECT * FROM PEMILIKHEWAN WHERE LOWER(NAMA) LIKE LOWER(:search)";
$db->query($queryCustomer);
$db->bind(':search', '%' . $searchCustomer . '%');
$customers = $db->resultSet();

// Pencarian hewan
$searchPet = isset($_GET['search_pet']) ? $_GET['search_pet'] : '';
$queryPet = "SELECT * FROM HEWAN WHERE LOWER(NAMA) LIKE LOWER(:search)";
$db->query($queryPet);
$db->bind(':search', '%' . $searchPet . '%');
$pets = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer and Pet Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background:rgb(238, 138, 138);
            border: 1px solid #ddd;
            border-bottom: none;
            margin-right: 5px;
        }
        .tab.active {
            background:rgb(19, 182, 231);
            color: white;
        }
        .content {
            display: none;
        }
        .content.active {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
          background:rgb(19, 182, 231);
            color: black;
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .fab-customer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color:rgb(35, 255, 53);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-size: 30px;
            text-decoration: none;
            cursor: pointer;
            z-index: 1000;
        }
        .fab:hover {
            background-color: #0056b3;
        }
        .fab-pet {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color:rgb(35, 255, 53);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            text-decoration: none;
            cursor: pointer;
            z-index: 1000;
        }
        .fab:hover {
            background-color:rgb(11, 124, 54);
        }
       
    </style>
    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.content').forEach(content => content.classList.remove('active'));

            document.getElementById(tabId).classList.add('active');
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
        }
    </script>
</head>
<body>
    <div class="tabs">
        <div class="tab active" data-tab="customers" onclick="showTab('customers')">Customer</div>
        <div class="tab" data-tab="pets" onclick="showTab('pets')">Pet</div>
    </div>

    <div id="customers" class="content active">
        <h2>Customer Information</h2>
        <form method="GET" action="">
            <input type="text" name="search_customer" placeholder="Cari Pelanggan" value="<?php echo htmlspecialchars($searchCustomer); ?>">
            <button type="submit">Cari</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No Telp</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($customers as $row): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['NAMA']); ?></td>
                        <td><?php echo htmlspecialchars($row['EMAIL']); ?></td>
                        <td><?php echo htmlspecialchars($row['NOMORTELPON']); ?></td>
                        <td>
                            <a href="edit-customer.php?id=<?php echo $row['ID']; ?>" class="btn btn-edit">Edit</a>
                            <a href="delete-customer.php?id=<?php echo $row['ID']; ?>" class="btn btn-delete" onclick="return confirm('Yakin ingin menghapus pelanggan ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="add-customer.php" class="fab fab-customer">+</a>
    </div>
    

    <div id="pets" class="content">
        <h2>Pet Information</h2>
        <form method="GET" action="">
            <input type="text" name="search_pet" placeholder="Cari Hewan" value="<?php echo htmlspecialchars($searchPet); ?>">
            <button type="submit">Cari</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama</th>
                    <th>Ras</th>
                    <th>Spesies</th>
                    <th>Gender</th>
                    <th>Berat</th>
                    <th>Tanggal Lahir</th>
                    <th>Tinggi</th>
                    <th>Lebar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($pets as $row): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['NAMA']); ?></td>
                        <td><?php echo htmlspecialchars($row['RAS']); ?></td>
                        <td><?php echo htmlspecialchars($row['SPESIES']); ?></td>
                        <td><?php echo htmlspecialchars($row['GENDER']); ?></td>
                        <td><?php echo htmlspecialchars($row['BERAT']); ?></td>
                        <td><?php echo htmlspecialchars($row['TANGGALLAHIR']); ?></td>
                        <td><?php echo htmlspecialchars($row['TINGGI']); ?></td>
                        <td><?php echo htmlspecialchars($row['LEBAR']); ?></td>
                        <td>
                            <a href="edit-pet.php?id=<?php echo $row['ID']; ?>" class="btn btn-edit">Edit</a>
                            <a href="delete-pet.php?id=<?php echo $row['ID']; ?>" class="btn btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="add-pet.php" class="fab fab-pet">+</a>
    </div>
</body>
</html>
