<?php
// Depo ID'sini URL'den alıyoruz
$depoid = optional_param('depoid', null, PARAM_INT);

// Eğer depo ID'si gelmemişse, hata mesajı göster
if (!$depoid) {
    echo "Geçersiz depo ID'si!";
    exit;
}

// Satış ekleme işlemi yapılacak
if (isset($_POST['add_sale'])) {
    // Kullanıcının girdiği veriler alınır
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $date = $_POST['date'];
    $branch = $_POST['branch'];

    // Ürün fiyatı ve toplam tutarı hesapla (örnek olarak sabit bir fiyat kullanalım)
    $product_price = 10; // Örnek fiyat
    $total = $product_price * $quantity;

    // Satış bilgilerini göster (Bu aşamada veritabanına ekleme işlemi yapılabilir)
    echo "<h3>Satış Başarılı!</h3>";
    echo "Ürün: $product <br>";
    echo "Miktar: $quantity <br>";
    echo "Tarih: $date <br>";
    echo "Tutar: $total TL <br>";
    echo "Şube: $branch <br>";
}

// Satış formu
?>
<form method="POST" action="satis_ekle.php?depoid=<?php echo $depoid; ?>">
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
