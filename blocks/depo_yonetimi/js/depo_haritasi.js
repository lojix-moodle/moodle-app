// blocks/depo_yonetimi/amd/src/depo_haritasi.js
define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates'],
    function($, Ajax, Notification, Str, Templates) {

        return {
            init: function() {
                const self = this;
                const container = $('#depo-haritasi');
                const depoId = container.data('depo-id');

                if (!depoId) return;

                // Harita verilerini getir
                self.loadMapData(depoId).then(function(data) {
                    self.renderMap(container, data);
                    self.initEvents();
                }).catch(Notification.exception);
            },

            loadMapData: function(depoId) {
                return Ajax.call([{
                    methodname: 'block_depo_yonetimi_get_yerlesim',
                    args: { depo_id: depoId }
                }])[0].then(function(data) {
                    return data;
                });
            },

            renderMap: function(container, data) {
                const self = this;
                const width = container.width();
                const height = 600; // Sabit yükseklik veya ihtiyaca göre ayarlayın

                // SVG oluştur
                container.html(`
                <svg width="${width}" height="${height}" class="depo-svg">
                    <g class="yerlesim-group"></g>
                </svg>
            `);

                const svg = container.find('svg');
                const group = svg.find('.yerlesim-group');

                // Tüm yerleşim öğelerini çiz
                if (data && data.items) {
                    data.items.forEach(function(item) {
                        self.drawRaf(group, item);
                    });
                }
            },

            drawRaf: function(container, rafData) {
                // Raf elemanını oluştur
                const raf = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                raf.setAttribute('x', rafData.x_konum);
                raf.setAttribute('y', rafData.y_konum);
                raf.setAttribute('width', rafData.genislik);
                raf.setAttribute('height', rafData.yukseklik);
                raf.setAttribute('class', 'raf');
                raf.setAttribute('data-id', rafData.id);
                raf.setAttribute('data-raf-kodu', rafData.raf_kodu);

                // Eğer rafta ürün varsa farklı renk kullan
                if (rafData.urun_sayisi && rafData.urun_sayisi > 0) {
                    raf.setAttribute('class', 'raf dolu-raf');
                }

                container.append(raf);

                // Raf etiketi
                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', parseInt(rafData.x_konum) + parseInt(rafData.genislik) / 2);
                text.setAttribute('y', parseInt(rafData.y_konum) + parseInt(rafData.yukseklik) / 2);
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('dominant-baseline', 'middle');
                text.setAttribute('class', 'raf-etiket');
                text.textContent = rafData.raf_kodu;

                container.append(text);
            },

            initEvents: function() {
                const self = this;
                const editBtn = $('#edit-mode-btn');
                const editPanel = $('#edit-panel');
                const rafForm = $('#raf-form');
                const cancelBtn = $('#cancel-btn');

                // Düzenleme modunu aç/kapa
                editBtn.on('click', function() {
                    const isEditMode = $(this).data('edit-mode');
                    if (isEditMode) {
                        $(this).data('edit-mode', false);
                        $(this).text('Düzenleme Modunu Aç');
                        editPanel.addClass('d-none');
                        $('.raf').removeClass('edit-mode');
                    } else {
                        $(this).data('edit-mode', true);
                        $(this).text('Düzenleme Modunu Kapat');
                        editPanel.removeClass('d-none');
                        $('.raf').addClass('edit-mode');
                    }
                });

                // Raf tıklama işlemi
                $(document).on('click', '.raf', function() {
                    if (editBtn.data('edit-mode')) {
                        // Düzenleme modu açıksa, formu doldur
                        const id = $(this).data('id');
                        const rafKodu = $(this).data('raf-kodu');

                        $('#raf-id').val(id);
                        $('#raf_kodu').val(rafKodu);
                    } else {
                        // Düzenleme modu kapalıysa, raf detaylarını göster
                        const rafKodu = $(this).data('raf-kodu');
                        self.showRafDetails(rafKodu);
                    }
                });

                // Raf form işlemleri
                rafForm.on('submit', function(e) {
                    e.preventDefault();
                    self.saveRafData($(this).serialize());
                });

                // İptal butonu
                cancelBtn.on('click', function() {
                    rafForm[0].reset();
                    $('#raf-id').val(0);
                });
            },

            showRafDetails: function(rafKodu) {
                const detayPanel = $('#raf-detay');
                const rafBaslik = $('#raf-baslik');
                const rafUrunler = $('#raf-urunler');

                rafBaslik.text(rafKodu);

                // Raftaki ürünleri getir
                Ajax.call([{
                    methodname: 'block_depo_yonetimi_get_raf_urunleri',
                    args: { raf_kodu: rafKodu }
                }])[0].then(function(data) {
                    if (data && data.length > 0) {
                        let html = '<ul class="list-group">';
                        data.forEach(function(urun) {
                            html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ${urun.urun_adi} (${urun.stok_kodu})
                                <span class="badge badge-primary badge-pill">${urun.miktar}</span>
                            </li>
                        `;
                        });
                        html += '</ul>';
                        rafUrunler.html(html);
                    } else {
                        rafUrunler.html('<div class="alert alert-info">Bu rafta ürün bulunmamaktadır.</div>');
                    }
                    detayPanel.removeClass('d-none');
                }).catch(Notification.exception);
            },

            saveRafData: function(formData) {
                Ajax.call([{
                    methodname: 'block_depo_yonetimi_save_yerlesim',
                    args: { form_data: formData }
                }])[0].then(function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        Notification.alert('Hata', response.message);
                    }
                }).catch(Notification.exception);
            }
        };
    });