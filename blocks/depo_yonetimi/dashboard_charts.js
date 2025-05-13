/**
 * Dashboard grafikleri için JavaScript
 *
 * @package    block_depo_yonetimi
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/chartjs'], function($, Chart) {
    return {
        /**
         * Grafikleri başlat
         */
        init: function() {
            this.initStockLevelsChart();
        },

        /**
         * Stok seviyeleri grafiğini başlat
         */
        initStockLevelsChart: function() {
            var stockChartContainer = document.getElementById('stockLevelsChart');

            if (!stockChartContainer) {
                return;
            }

            // AJAX ile stok seviyelerini al
            $.ajax({
                url: M.cfg.wwwroot + '/blocks/depo_yonetimi/ajax/get_stock_data.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        this.renderStockLevelsChart(stockChartContainer, response.data);
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Stok verileri alınırken hata oluştu:', error);
                    // Hata durumunda varsayılan veri ile göster
                    this.renderStockLevelsChart(stockChartContainer, this.getDummyStockData());
                }.bind(this)
            });
        },

        /**
         * Stok seviyeleri grafiğini oluştur
         *
         * @param {HTMLElement} container Grafik container elementi
         * @param {Array} stockData Stok verileri
         */
        renderStockLevelsChart: function(container, stockData) {
            var labels = stockData.map(function(item) {
                return item.name;
            });

            var currentStockData = stockData.map(function(item) {
                return item.current_stock;
            });

            var criticalLevelData = stockData.map(function(item) {
                return item.critical_level;
            });

            var backgroundColors = stockData.map(function(item) {
                if (item.stock_status === 'critical') {
                    return 'rgba(220, 53, 69, 0.6)'; // Kırmızı
                } else if (item.stock_status === 'warning') {
                    return 'rgba(255, 193, 7, 0.6)'; // Sarı
                } else {
                    return 'rgba(58, 110, 255, 0.6)'; // Mavi
                }
            });

            var chart = new Chart(container, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Mevcut Stok',
                            data: currentStockData,
                            backgroundColor: backgroundColors,
                            borderColor: backgroundColors.map(function(color) {
                                return color.replace('0.6', '1');
                            }),
                            borderWidth: 1
                        },
                        {
                            label: 'Kritik Seviye',
                            data: criticalLevelData,
                            type: 'line',
                            fill: false,
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Stok Miktarı'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Ürünler'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    }
                }
            });
        },

        /**
         * Varsayılan stok verilerini oluştur (hata durumunda kullanmak için)
         *
         * @return {Array} Örnek stok verileri
         */
        getDummyStockData: function() {
            return [
                {
                    name: 'Ürün A',
                    current_stock: 120,
                    critical_level: 50,
                    stock_status: 'normal'
                },
                {
                    name: 'Ürün B',
                    current_stock: 45,
                    critical_level: 60,
                    stock_status: 'warning'
                },
                {
                    name: 'Ürün C',
                    current_stock: 15,
                    critical_level: 30,
                    stock_status: 'critical'
                },
                {
                    name: 'Ürün D',
                    current_stock: 200,
                    critical_level: 100,
                    stock_status: 'normal'
                },
                {
                    name: 'Ürün E',
                    current_stock: 80,
                    critical_level: 75,
                    stock_status: 'normal'
                }
            ];
        }
    };
});