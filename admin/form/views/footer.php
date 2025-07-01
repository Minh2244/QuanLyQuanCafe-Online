    </div><!-- end .content -->

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Best Coffee. Tất cả quyền được bảo lưu.</p>
    </footer>

    <script>
        // Xử lý đóng thông báo
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.querySelector('.notification-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    const banner = document.querySelector('.notification-banner');
                    banner.style.display = 'none';
                });
            }
        });
    </script>
    </body>

    </html>