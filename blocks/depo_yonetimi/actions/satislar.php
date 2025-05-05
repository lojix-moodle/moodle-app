<?php
// Satış verileri statik olarak tanımlanacak
$sales = [
    ['product' => 'Ürün A', 'quantity' => 5, 'date' => '2025-05-05', 'total' => 50, 'branch' => 'Şube 1'],
    ['product' => 'Ürün B', 'quantity' => 2, 'date' => '2025-05-04', 'total' => 20, 'branch' => 'Şube 2'],
    ['product' => 'Ürün C', 'quantity' => 10, 'date' => '2025-05-03', 'total' => 100, 'branch' => 'Şube 1']
];

// Satış ekleme işlemi
if (isset($_POST['add_sale'])) {
    // Kullanıcının girdiği veriler alınıyor
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $date = $_POST['date'];
    $branch = $_POST['branch'];

    // Satış tutarını hesapla
    // Örneğin, ürün fiyatını sabit kabul ediyoruz
    $product_price = 10; // Örnek fiyat, gerçek sistemde ürün fiyatı veritabanından alınır
    $total = $product_price * $quantity;

    // Yeni satış verisini ekle
    $new_sale = ['product' => $product, 'quantity' => $quantity, 'date' => $date, 'total' => $total, 'branch' => $branch];
    $sales[] = $new_sale;
}
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
