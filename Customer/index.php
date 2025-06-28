<?php
session_start();
include 'view/header.php';
?>

<section class="hero-section">
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title">Best Coffee ☕ - nghe tưởng ngông, nhưng là thật</h1>
            <h2 class="hero-subtitle">Cà phê không chỉ để uống. Nó là thứ nghi thức - dành cho những kẻ biết chậm lại
                giữa dòng đời nhanh như deadline</h2>
            <p class="hero-description">
                Cà phê không cố gắng làm bạn tỉnh, nó khiến bạn muốn thức. Thức với mùi đất, vị khói, với âm nhạc văng
                vẳng và cuộc đời không cần gấp gáp.
            </p>
            <div class="hero-buttons">
                <a href="menu.php" class="hero-btn btn-primary">Đặt ngay</a>
                <a href="#contact" class="hero-btn btn-secondary">Liên hệ</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="images/coffee-hero-section.png" alt="Coffee Splash">
        </div>
    </div>
</section>

<?php include 'view/footer.php'; ?>