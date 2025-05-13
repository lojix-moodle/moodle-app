define(['jquery'], function($) {
    return {
        init: function() {
            // AJAX ile verileri güncellemek için kullanılabilir
            $(document).ready(function() {
                console.log('Depo yönetimi özet grafikleri yüklendi');

                // Grafiklerin boyutunu pencere boyutuna göre ayarla
                function resizeCharts() {
                    $('.chart-container').each(function() {
                        let width = $(this).width();
                        // Minimum 300px yükseklik, maximum genişliğin %80'i
                        let height = Math.max(300, width * 0.8);
                        $(this).css('height', height + 'px');
                    });
                }

                // Sayfa yüklendiğinde ve pencere boyutu değiştiğinde grafikleri yeniden boyutlandır
                $(window).on('load resize', function() {
                    resizeCharts();
                });

                // Veri yenileme butonu işlevselliği (opsiyonel)
                $('#refresh-data').on('click', function() {
                    location.reload();
                });
            });
        }
    };
});