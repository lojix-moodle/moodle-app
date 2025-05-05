<?php
// Veritabanı bağlantısı
$host = 'localhost';  // Veritabanı sunucusu
$dbname = 'depo_db';  // Veritabanı adı
$username = 'root';   // Veritabanı kullanıcı adı
$password = '';       // Veritabanı şifresi

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

// Satış ekleme işlemi
if (isset($_POST['add_sale'])) {
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $date = $_POST['date'];
    $branch = $_POST['branch'];

    // Satış tutarını hesapla
    // Örneğin, ürün fiyatını sabit kabul edebiliriz, ama genelde ürün fiyatı veritabanından çekilir
    $product_price = 10; // Örnek fiyat, veritabanından ürün fiyatı çekilebilir
    $total = $product_price * $quantity;

    // Veritabanına ekleme
    $sql = "INSERT INTO sales (product, quantity, date, total, branch) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product, $quantity, $date, $total, $branch]);
}

// Satışları al
$sql = "SELECT * FROM sales ORDER BY date DESC";
$stmt = $pdo->query($sql);
$sales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satışlar</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">
    <h1>Satış Ekle</h1>
    <form action="sales_page.php" method="POST">
        <label for="product">Ürün:</label>
        <input type="text" id="product" name="product" required><br>

        <label for="quantity">Miktar:</label>
        <input type="number" id="quantity" name="quantity" required><br>

        <label for="date">Tarih:</label>
        <input type="date" id="date" name="date" required><br>

        <label for="branch">Şube:</label>
        <input type="text" id="branch" name="branch" required><br>

        <button type="submit" name="add_sale">Satışı Ekle</button>
    </form>

    <h2>Satışlar Listesi</h2>
    <table>
        <thead>
        <tr>
            <th>Ürün</th>
            <th>Miktar</th>
            <th>Tarih</th>
            <th>Tutar</th>
            <th>Şube</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($sales as $sale): ?>
            <tr>
                <td><?php echo htmlspecialchars($sale['product']); ?></td>
                <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                <td><?php echo htmlspecialchars($sale['date']); ?></td>
                <td><?php echo htmlspecialchars($sale['total']); ?> TL</td>
                <td><?php echo htmlspecialchars($sale['branch']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
