// Depo Yönetimi için JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Tooltip'leri başlat
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Dropdown menüleri başlat
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Ürün arama fonksiyonu
    const urunArama = document.getElementById('urunArama');
    if (urunArama) {
        urunArama.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const urunSatirlar = document.querySelectorAll('.urun-tablosu tbody tr');

            urunSatirlar.forEach(function(satir) {
                const urunAdi = satir.querySelector('td:first-child strong').textContent.toLowerCase();

                if (urunAdi.includes(searchTerm)) {
                    satir.style.display = '';
                } else {
                    satir.style.display = 'none';
                }
            });
        });
    }

    // Kategori filtreleme
    const kategoriFiltre = document.getElementById('kategoriFiltre');
    if (kategoriFiltre) {
        kategoriFiltre.addEventListener('change', function() {
            const secilenKategori = this.value.toLowerCase();
            const urunSatirlar = document.querySelectorAll('.urun-tablosu tbody tr');

            if (secilenKategori === '') {
                // Tüm kategorileri göster
                urunSatirlar.forEach(function(satir) {
                    satir.style.display = '';
                });
            } else {
                // Sadece seçilen kategoriyi göster
                urunSatirlar.forEach(function(satir) {
                    const kategori = satir.getAttribute('data-kategori').toLowerCase();

                    if (kategori === secilenKategori) {
                        satir.style.display = '';
                    } else {
                        satir.style.display = 'none';
                    }
                });
            }
        });
    }

    // Stok durumu göstergeleri için renk sınıfları
    const stokDurumlari = document.querySelectorAll('.stok-durumu');
    if (stokDurumlari) {
        stokDurumlari.forEach(function(durum) {
            const stokAdedi = parseInt(durum.getAttribute('data-adet'));

            if (stokAdedi <= 3) {
                durum.classList.add('bg-danger');
            } else if (stokAdedi <= 10) {
                durum.classList.add('bg-warning');
            } else {
                durum.classList.add('bg-success');
            }
        });
    }

    // Animasyonlu giriş efektleri
    const animate = function() {
        const elements = document.querySelectorAll('.animate-fade-in');

        elements.forEach(function(element, index) {
            setTimeout(function() {
                element.classList.add('visible');
            }, index * 100);
        });
    };

    animate();

    // Depo kartları için hover efekti
    const depoKartlari = document.querySelectorAll('.depo-card');
    if (depoKartlari) {
        depoKartlari.forEach(function(kart) {
            kart.addEventListener('mouseenter', function() {
                this.querySelector('.btn').classList.remove('btn-outline-primary');
                this.querySelector('.btn').classList.add('btn-primary');
            });

            kart.addEventListener('mouseleave', function() {
                this.querySelector('.btn').classList.remove('btn-primary');
                this.querySelector('.btn').classList.add('btn-outline-primary');
            });
        });
    }

    // Form doğrulama
    const formlar = document.querySelectorAll('.needs-validation');
    if (formlar) {
        Array.from(formlar).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            }, false);
        });
    }
});


document.addEventListener('DOMContentLoaded', function() {
    // Modal HTML'ini ekle (sayfada bir kez eklenir)
    if (!document.getElementById('barcodeModal')) {
        document.body.insertAdjacentHTML('beforeend', `
        <div class="modal fade" id="barcodeModal" tabindex="-1" aria-labelledby="barcodeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="barcodeModalLabel">Barkod Görüntüle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
              </div>
              <div class="modal-body text-center">
                <svg id="popup-barcode-svg"></svg>
                <div id="popup-barcode-value" class="mt-2 text-muted"></div>
              </div>
            </div>
          </div>
        </div>
        `);
    }

    // Barkod ikonlarına tıklama eventi ekle
    document.querySelectorAll('.barcode-scan-icon').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Barkod değerini bul
            const barkod = this.closest('.barcode-display').querySelector('strong').textContent.trim();
            // Modal içindeki SVG ve değer alanını bul
            const svg = document.getElementById('popup-barcode-svg');
            svg.innerHTML = '';
            JsBarcode(svg, barkod, {
                format: "CODE128",
                lineColor: "#000",
                width: 3,
                height: 120,
                displayValue: true
            });
            document.getElementById('popup-barcode-value').textContent = barkod;
            // Bootstrap modalı aç
            const modal = new bootstrap.Modal(document.getElementById('barcodeModal'));
            modal.show();
        });
    });
});