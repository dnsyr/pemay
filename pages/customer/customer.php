<?php
session_start();
include '../../config/connection.php';

// Include role-specific headers
switch ($_SESSION['posisi']) {
    case 'owner':
        include '../owner/header.php';
        break;
    case 'vet':
        include '../vet/header.php';
        break;
    case 'staff':
        include '../staff/header.php';
        break;
}

// Contoh data pelanggan
$customers = [
  ['name' => 'Pemay', 'email' => 'pemay@pnj.ac.id', 'phone' => '0812-3456-7890']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer & Pet Information</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }
    .tabs {
      display: flex;
      border-bottom: 2px solid #ccc;
    }
    .tab {
      padding: 10px 20px;
      cursor: pointer;
      border: 1px solid #ccc;
      border-bottom: none;
      background-color: #EFBB99;
      margin-right: 5px;
    }
    .tab.active {
      background-color: #ADD8E6;
      font-weight: bold;
      border-top: 2px solid #4CAF50;
    }
    .content {
      display: none;
      padding: 20px;
      border: 1px solid #ccc;
      border-top: none;
    }
    .content.active {
      display: block;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    th {
      background-color: #ADD8E6;
    }
    .action-buttons button {
      margin-right: 5px;
      padding: 5px 10px;
      border: none;
      color: #fff;
      cursor: pointer;
    }
    .edit-btn {
      background-color: #4CAF50;
    }
    .delete-btn {
      background-color: #f44336;
    }
    .add-button {
      background-color: #4CAF50;
      color: #fff;
      border: none;
      padding: 10px 15px;
      cursor: pointer;
      border-radius: 50%;
      font-size: 20px;
      position: fixed;
      bottom: 20px;
      right: 20px;
    }
  </style>
</head>
<body>
  <div class="tabs">
    <div class="tab active" onclick="switchTab('customer')">Customer</div>
    <div class="tab" onclick="switchTab('pet')">Pet</div>
  </div>

  <!-- Tab Customer -->
  <div id="customer" class="content active">
    <h2>Customer Information</h2>
    <input class="search-bar" type="text" placeholder="Cari Pelanggan" oninput="searchCustomer(this.value)">
    <table id="customer-table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>No Telp</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $customer): ?>
        <tr>
          <td><?= htmlspecialchars($customer['name']) ?></td>
          <td><?= htmlspecialchars($customer['email']) ?></td>
          <td><?= htmlspecialchars($customer['phone']) ?></td>
          <td class="action-buttons">
            <button class="edit-btn" onclick="editCustomer(this)">Edit</button>
            <button class="delete-btn" onclick="deleteCustomer(this)">Hapus</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <a href="add-customer.php">
      <button class="add-button">+</button>
    </a>
  </div>

  <!-- Tab Pet -->
  <div id="pet" class="content">
    <h2>Pet Information</h2>
    <table>
      <thead>
        <tr>
          <th>Nama Hewan</th>
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
        <tr>
          <td>Max</td>
          <td>Golden Retriever</td>
          <td>Dog</td>
          <td>Male</td>
          <td>30kg</td>
          <td>2020-01-15</td>
          <td>60cm</td>
          <td>20cm</td>
          <td class="action-buttons">
            <button class="edit-btn" onclick="editPet()">Edit</button>
            <button class="delete-btn" onclick="deletePet()">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button class="add-button" onclick="addPet()">+</button>
  </div>

  <script>
    function switchTab(tabId) {
      document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
      document.querySelectorAll('.content').forEach(content => content.classList.remove('active'));

      document.querySelector(`.tab[onclick="switchTab('${tabId}')"]`).classList.add('active');
      document.getElementById(tabId).classList.add('active');
    }

    function editCustomer(button) {
      const row = button.closest("tr");
      const cells = row.querySelectorAll("td");
      const name = prompt("Edit Nama:", cells[0].textContent);
      const email = prompt("Edit Email:", cells[1].textContent);
      const phone = prompt("Edit No Telp:", cells[2].textContent);

      if (name) cells[0].textContent = name;
      if (email) cells[1].textContent = email;
      if (phone) cells[2].textContent = phone;
    }

    function deleteCustomer(button) {
      const row = button.closest("tr");
      if (confirm("Apakah Anda yakin ingin menghapus pelanggan ini?")) {
        row.remove();
      }
    }

    function searchCustomer(query) {
      const rows = document.querySelectorAll("#customer-table tbody tr");
      rows.forEach(row => {
        const name = row.children[0].textContent.toLowerCase();
        row.style.display = name.includes(query.toLowerCase()) ? "" : "none";
      });
    }

    function editPet() {
      alert("Edit Pet");
    }

    function deletePet() {
      alert("Hapus Pet");
    }

    function addPet() {
      alert("Tambah Pet");
    }
  </script>
</body>
</html>
