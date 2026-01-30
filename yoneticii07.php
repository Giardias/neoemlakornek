<?php
session_start();

// Giriş bilgileri
$correct_username = "yöneticipan07";
$correct_password = "yöneticipas15";

// Hata mesajı değişkeni
$error_message = "";

// Form gönderildiyse kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    
    if ($username === $correct_username && $password === $correct_password) {
        // Giriş başarılı - session başlat ve yönlendir
        $_SESSION["neo_admin"] = true;
        $_SESSION["admin_username"] = $username;
        $_SESSION["login_time"] = time();
        
        // 30 dakikalık session süresi
        $_SESSION["expiry_time"] = time() + (30 * 60);
        
        // Yönetici paneline yönlendir
        header("Location: yönetic15.php");
        exit();
    } else {
        // Giriş başarısız
        $error_message = "Hatalı kullanıcı adı veya şifre!";
    }
}

// Eğer zaten giriş yapmışsa yönetici paneline yönlendir
if (isset($_SESSION["neo_admin"]) && $_SESSION["neo_admin"] === true) {
    // Session süresi kontrolü
    if (isset($_SESSION["expiry_time"]) && time() < $_SESSION["expiry_time"]) {
        header("Location: yönetic15.php");
        exit();
    } else {
        // Session süresi dolmuş
        session_destroy();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Giriş | NEO YAPI</title>
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/609/609803.png">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* Ek CSS - PHP ile entegre */
        .php-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #EF4444;
            color: #FCA5A5;
            padding: 14px;
            border-radius: 10px;
            margin-top: 20px;
            display: <?php echo !empty($error_message) ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: shake 0.5s ease;
        }
    </style>
</head>
<body class="login-page-body">
    <div class="security-overlay"></div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-user-shield"></i></div>
                <div class="logo-text">NEO<span>YAPI</span></div>
            </div>
            <h1>GÜVENLİ GİRİŞ</h1>
            <p style="color:#10B981; font-weight:bold;">Sistem Durumu: Aktif</p>
            <p style="color:#94A3B8; font-size:12px; margin-top:5px;">PHP Güvenlik Modu</p>
        </div>
        
        <form id="loginForm" method="POST" action="" autocomplete="off">
            <div class="form-group">
                <label class="form-label"><i class="fas fa-user"></i> Kullanıcı Adı</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Kullanıcı adınız" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label"><i class="fas fa-lock"></i> Yönetici Şifresi</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Şifreniz" required>
                    <button type="button" class="toggle-password" id="togglePassword"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            
            <!-- PHP Error Message -->
            <?php if (!empty($error_message)): ?>
            <div class="php-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <!-- JS Error Message (eskisi gibi) -->
            <div class="error-message" id="errorMessage"></div>
            
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-sign-in-alt"></i> GİRİŞ YAP
            </button>
        </form>
        
        <div class="security-info">
            <h4><i class="fas fa-shield-alt"></i> GÜVENLİK NOTU</h4>
            <ul>
                <li>PHP Session ile güvenlik artırıldı</li>
                <li>Oturum süresi: 30 dakika</li>
                <li>Güvenli giriş doğrulaması</li>
                <li>IP bazlı koruma (isteğe bağlı)</li>
            </ul>
        </div>
        
        <div class="back-link">
            <a href="index.html"><i class="fas fa-home"></i> Ana Sayfaya Dön</a>
        </div>
    </div>
    
    <script>
        // --- ACİL DURUM KİLİT KİRMA ---
        // Sayfa her yüklendiğinde eski LocalStorage kilitlerini temizler
        localStorage.removeItem('neoLoginLockout');
        console.log("🔓 LocalStorage kilitleri temizlendi.");
        // ------------------------------

        // Mevcut JS kodunu koru (tasarım için)
        const DOM = {
            form: document.getElementById('loginForm'),
            usernameInput: document.getElementById('username'),
            passwordInput: document.getElementById('password'),
            btn: document.getElementById('submitBtn'),
            error: document.getElementById('errorMessage'),
            toggle: document.getElementById('togglePassword')
        };

        // Şifre Göster/Gizle
        if (DOM.toggle) {
            DOM.toggle.addEventListener('click', () => {
                const type = DOM.passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                DOM.passwordInput.setAttribute('type', type);
                DOM.toggle.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        }

        // Client-side validation (isteğe bağlı)
        if (DOM.form) {
            DOM.form.addEventListener('submit', function(e) {
                // Sadece temel validation
                const username = DOM.usernameInput.value.trim();
                const password = DOM.passwordInput.value.trim();
                
                if (!username || !password) {
                    e.preventDefault();
                    DOM.error.style.display = 'flex';
                    DOM.error.innerHTML = '<i class="fas fa-exclamation-circle"></i> Lütfen tüm alanları doldurun!';
                    return false;
                }
                
                // Buton durumunu güncelle
                DOM.btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kontrol Ediliyor...';
                DOM.btn.disabled = true;
                
                // Form zaten PHP'ye submit olacak
            });
        }
        
        // Sayfa yüklendiğinde username'e focus
        window.addEventListener('DOMContentLoaded', () => {
            if (DOM.usernameInput) {
                DOM.usernameInput.focus();
            }
        });
    </script>
</body>
</html>