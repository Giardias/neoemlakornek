<?php
session_start();

// GÜVENLİK KONTROLÜ - EN BAŞTA
$allowed_pages = ['yönetic15.php', 'admin.php', 'yoneticipas15.html']; // İzin verilen sayfalar

// Session kontrolü
if (!isset($_SESSION["neo_admin"]) || $_SESSION["neo_admin"] !== true) {
    // Giriş yapılmamış - giriş sayfasına yönlendir
    header("Location: yoneticii07.php");
    exit();
}

// Session süresi kontrolü (30 dakika)
if (isset($_SESSION["expiry_time"]) && time() > $_SESSION["expiry_time"]) {
    // Session süresi dolmuş - çıkış yap
    session_destroy();
    header("Location: yoneticii07.php?error=session_expired");
    exit();
}

// Her sayfa yüklendiğinde session süresini yenile (opsiyonel)
$_SESSION["expiry_time"] = time() + (30 * 60);

// Çıkış yap butonu kontrolü
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Tüm session değişkenlerini temizle
    $_SESSION = array();
    
    // Cookie'yi sil
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Session'ı yok et
    session_destroy();
    
    // Giriş sayfasına yönlendir
    header("Location: yoneticii07.php?logout=success");
    exit();
}

// Admin bilgilerini al
$admin_username = $_SESSION["admin_username"] ?? "Yönetici";
$login_time = isset($_SESSION["login_time"]) ? date("d.m.Y H:i", $_SESSION["login_time"]) : "Bilinmiyor";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YÖNETİCİ PANELİ | NEO YAPI</title>
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/609/609803.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* PHP Session Bilgi Banner'ı */
        .php-session-info {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-left: 4px solid #10B981;
            font-size: 13px;
        }
        .session-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
        }
        .session-user i {
            color: #C5A059;
        }
        .session-time {
            color: #94A3B8;
            font-size: 12px;
        }
        .session-actions {
            display: flex;
            gap: 10px;
        }
        .btn-session-refresh {
            background: #3B82F6;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-session-refresh:hover {
            background: #2563EB;
        }
    </style>
</head>
<body>
    
    <!-- Çıkış Yap Butonu - PHP ile güncellendi -->
    <button id="logoutBtn" onclick="window.location.href='?action=logout'">
        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
    </button>
    
    <!-- PHP Session Bilgisi -->
    <div class="php-session-info">
        <div>
            <div class="session-user">
                <i class="fas fa-user-shield"></i>
                <span>Hoş geldiniz, <?php echo htmlspecialchars($admin_username); ?></span>
            </div>
            <div class="session-time">
                <i class="far fa-clock"></i> Giriş: <?php echo $login_time; ?> | 
                Oturum: <?php echo floor(($_SESSION["expiry_time"] - time()) / 60); ?> dakika kaldı
            </div>
        </div>
        <div class="session-actions">
            <a href="?action=refresh" class="btn-session-refresh">
                <i class="fas fa-sync-alt"></i> Yenile
            </a>
            <a href="?action=logout" class="btn-session-refresh" style="background:#EF4444;">
                <i class="fas fa-power-off"></i> Çıkış
            </a>
        </div>
    </div>
    
    <div class="admin-container">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="color: #0F172A; font-family: 'Playfair Display', serif;">
                <i class="fas fa-user-shield" style="color: #C5A059;"></i> Yönetici Paneli
            </h1>
            <p style="color: #64748B; font-size: 14px; margin-top: 5px;">
                <i class="fas fa-server" style="color: #10B981;"></i> PHP Session Güvenliği Aktif
            </p>
            <a href="index.html" target="_blank" style="display:inline-block; margin-top:10px; color:#3B82F6; text-decoration:none; font-weight:bold;">
                <i class="fas fa-external-link-alt"></i> Siteyi Görüntüle
            </a>
        </div>

        <!-- ORJİNAL HTML KODU - DEĞİŞMEDEN KALDI -->
        <div class="panel" id="formPanel">
            <h2 id="formTitle"><i class="fas fa-plus-circle" style="color: #10B981;"></i> Yeni İlan Ekle</h2>
            
            <form id="listingForm">
                <input type="hidden" id="editId">
                <div class="form-grid">
                    <div>
                        <label>İlan Başlığı</label>
                        <input type="text" id="title" placeholder="Örn: Denize Sıfır Villa" required>
                    </div>
                    <div>
                        <label>Fiyat (Sadece Rakam)</label>
                        <input type="text" id="price" placeholder="Örn: 2500000" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        <small style="color:#10B981; font-weight:600; font-size:12px;">* Otomatik formatlanır (Örn: 2.500.000 ₺)</small>
                    </div>
                    <div>
                        <label>Kategori</label>
                        <select id="category">
                            <option value="konut">Konut</option>
                            <option value="villa">Villa</option>
                            <option value="arsa">Arsa</option>
                            <option value="isyeri">İşyeri</option>
                        </select>
                    </div>
                    <div>
                        <label>Konum</label>
                        <input type="text" id="loc" placeholder="Örn: Konyaaltı, Liman" required>
                    </div>
                    
                    <div><label>Alan (m²)</label><input type="text" id="m2" placeholder="Örn: 120"></div>
                    <div><label>Oda Sayısı</label><input type="text" id="oda" placeholder="Örn: 3+1"></div>
                    <div><label>Bulunduğu Kat</label><input type="text" id="kat" placeholder="Örn: 2. Kat"></div>
                    
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="featured">
                        <label for="featured">Bu ilanı Vitrin'de (Ana Sayfa) Göster</label>
                    </div>
                    
                    <div style="grid-column: 1/-1;">
                        <label>Fotoğraflar (CTRL tuşu ile birden fazla seçebilirsiniz)</label>
                        <div class="file-upload-wrapper">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 30px; color:#C5A059; margin-bottom:10px;"></i>
                            <p style="font-weight:600; color:#64748B;">Fotoğrafları seçmek için buraya tıklayın</p>
                            <input type="file" id="imageInput" accept="image/*" multiple>
                        </div>
                        <div class="preview-gallery" id="previewGallery"></div>
                        <small style="color:#64748B; display:block; margin-top:5px;">* Fotoğrafın üzerindeki kırmızı X işaretine basarak silebilirsiniz.</small>
                    </div>
                    
                    <div style="grid-column: 1/-1;">
                        <label>Açıklama</label>
                        <textarea id="desc" rows="3" placeholder="İlan hakkında detaylı açıklama..."></textarea>
                    </div>
                </div>
                
                <button type="submit" id="submitBtn" class="btn-submit"><i class="fas fa-paper-plane"></i> İLANI YAYINLA</button>
                <button type="button" id="cancelBtn" class="btn-cancel" onclick="cancelEdit()">İPTAL ET</button>
            </form>
        </div>

        <div class="panel">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                <h2 style="margin:0;"><i class="fas fa-list" style="color: #3B82F6;"></i> Tüm İlanlar</h2>
                
                <button type="button" id="resetBtn" class="btn-reset">
                    <i class="fas fa-sync-alt"></i> Örnek İlanları Yükle (Full Paket)
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table id="listTable">
                    <thead>
                        <tr>
                            <th>Kapak</th>
                            <th>Başlık</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="adminTable">
                        <tr><td colspan="5" style="text-align:center;">Veriler Google'dan yükleniyor...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script type="module">
        // ESKİ GÜVENLİK KONTROLÜNÜ KALDIR - ARTIK PHP SESSION VAR
        // Sadece Firebase bağlantısı için JS kodu çalıştır
        
        console.log("PHP Session güvenliği aktif. Firebase'e bağlanılıyor...");

        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getDatabase, ref, push, set, onValue, remove } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js";

        // FIREBASE AYARLARI (DEĞİŞMEDİ)
        const firebaseConfig = {
            apiKey: "AIzaSyBF5hsTqXl5hSJk3z-_kuECRrQzaVl-aj8",
            authDomain: "neoproject-e1cdd.firebaseapp.com",
            databaseURL: "https://neoproject-e1cdd-default-rtdb.europe-west1.firebasedatabase.app",
            projectId: "neoproject-e1cdd",
            storageBucket: "neoproject-e1cdd.firebasestorage.app",
            messagingSenderId: "900464535576",
            appId: "1:900464535576:web:5182d851532d84d21ffb6d",
            measurementId: "G-C47VH3DEDG"
        };

        const app = initializeApp(firebaseConfig);
        const db = getDatabase(app);

        let estateData = [];
        let currentImages = [];
        let isEditMode = false;

        // 1. RESİM YÜKLEME (DEĞİŞMEDİ)
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            if (files.length === 0) return;

            files.forEach(file => {
                if(file.size > 5 * 1024 * 1024) {
                    alert("⚠️ " + file.name + " dosyası 5MB'dan büyük!");
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        const maxWidth = 800;
                        const scaleSize = maxWidth / img.width;
                        canvas.width = maxWidth;
                        canvas.height = img.height * scaleSize;

                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        
                        currentImages.push(canvas.toDataURL('image/jpeg', 0.8));
                        renderGallery();
                    }
                };
                reader.readAsDataURL(file);
            });
        });

        function renderGallery() {
            const gallery = document.getElementById('previewGallery');
            gallery.innerHTML = '';
            
            currentImages.forEach((imgSrc, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'thumb-wrapper';
                
                const img = document.createElement('img');
                img.src = imgSrc;
                
                const btn = document.createElement('button');
                btn.className = 'btn-remove-img';
                btn.innerHTML = '✕';
                
                btn.onclick = function(e) {
                    e.preventDefault();
                    if(confirm("Bu fotoğrafı silmek istiyor musunuz?")) {
                        currentImages.splice(index, 1);
                        renderGallery();
                    }
                };

                wrapper.appendChild(img);
                wrapper.appendChild(btn);
                gallery.appendChild(wrapper);
            });
        }

        // 2. VERİLERİ FIREBASE'DEN ÇEKME (DEĞİŞMEDİ)
        onValue(ref(db, 'listings'), (snapshot) => {
            const tbody = document.getElementById('adminTable');
            tbody.innerHTML = '';
            estateData = [];

            const data = snapshot.val();
            if (data) {
                Object.keys(data).forEach(key => {
                    estateData.push({ id: key, ...data[key] });
                });
                
                estateData.reverse();

                estateData.forEach(item => {
                    let img = (item.images && item.images.length > 0) ? item.images[0] : 'https://via.placeholder.com/60x60?text=Resim+Yok';
                    
                    let statusBadge = item.featured ? 
                        '<span style="background:#DCFCE7; color:#166534; padding:5px 10px; border-radius:4px; font-weight:700; font-size:11px;"><i class="fas fa-star"></i> VİTRİN</span>' : 
                        '<span style="background:#F1F5F9; color:#64748B; padding:5px 10px; border-radius:4px; font-size:11px;">NORMAL</span>';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><img src="${img}" class="list-thumb"></td>
                        <td style="font-weight:600;">${item.title}</td>
                        <td style="font-weight:700; color:#C5A059;">${item.price}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-edit" onclick="window.editItem('${item.id}')"><i class="fas fa-edit"></i> DÜZENLE</button>
                                <button class="btn-delete" onclick="window.deleteItem('${item.id}')"><i class="fas fa-trash"></i> SİL</button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:40px; color:#64748B;">Henüz ilan yok. "Örnek İlanları Yükle" butonuna basın.</td></tr>';
            }
        });

        // 3. KAYDETME İŞLEMİ (DEĞİŞMEDİ)
        document.getElementById('listingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İŞLENİYOR...';
            btn.disabled = true;

            const rawPrice = document.getElementById('price').value;
            const formattedPrice = new Intl.NumberFormat('tr-TR').format(rawPrice) + ' ₺';

            const newItem = {
                title: document.getElementById('title').value,
                price: formattedPrice,
                loc: document.getElementById('loc').value,
                category: document.getElementById('category').value,
                desc: document.getElementById('desc').value,
                images: currentImages,
                featured: document.getElementById('featured').checked,
                details: {
                    m2: document.getElementById('m2').value,
                    oda: document.getElementById('oda').value,
                    kat: document.getElementById('kat').value
                },
                date: new Date().toISOString()
            };

            if (isEditMode) {
                const id = document.getElementById('editId').value;
                set(ref(db, 'listings/' + id), newItem)
                    .then(() => {
                        alert("✅ İlan başarıyla güncellendi!");
                        window.cancelEdit();
                    })
                    .catch(err => alert("Hata: " + err.message))
                    .finally(() => {
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> İLANI YAYINLA';
                        btn.disabled = false;
                    });
            } else {
                if(currentImages.length === 0) {
                    alert("⚠️ Lütfen en az 1 fotoğraf yükleyin!");
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> İLANI YAYINLA';
                    btn.disabled = false;
                    return;
                }

                push(ref(db, 'listings'), newItem)
                    .then(() => {
                        alert("✅ İlan başarıyla eklendi!");
                        document.getElementById('listingForm').reset();
                        currentImages = [];
                        renderGallery();
                    })
                    .catch(err => alert("Hata: " + err.message))
                    .finally(() => {
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> İLANI YAYINLA';
                        btn.disabled = false;
                    });
            }
        });

        // 4. ÖRNEK İLAN YÜKLEME (DEĞİŞMEDİ)
        document.getElementById('resetBtn').addEventListener('click', function() {
            if(confirm("DİKKAT: Veritabanına örnek ilanlar eklenecek. Onaylıyor musun?")) {
                const exampleData = [
                    { title: "Lara Fener Deniz Manzaralı", price: "18.500.000 ₺", loc: "Muratpaşa", category: "konut", featured: true, images: ["https://images.unsplash.com/photo-1512918760513-95f192972701?w=800"], details: { m2: "220", oda: "4+1", kat: "8. Kat" }, desc: "Özel tasarım daire." },
                    { title: "Konyaaltı Lüks Daire", price: "6.750.000 ₺", loc: "Konyaaltı", category: "konut", featured: true, images: ["https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800"], details: { m2: "110", oda: "2+1", kat: "3. Kat" }, desc: "Site içi lüks daire." },
                    { title: "Altınkale İmarlı Arsa", price: "8.500.000 ₺", loc: "Döşemealtı", category: "arsa", featured: false, images: ["https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800"], details: { m2: "500", oda: "-", kat: "-" }, desc: "Villa imarlı arsa." }
                ];

                exampleData.forEach(item => {
                    item.date = new Date().toISOString();
                    push(ref(db, 'listings'), item);
                });
                alert("✅ Örnek ilanlar yüklendi!");
            }
        });

        // SİLME VE DÜZENLEME FONKSİYONLARI (DEĞİŞMEDİ)
        window.deleteItem = function(id) {
            if(confirm("Silmek istediğine emin misin?")) {
                remove(ref(db, 'listings/' + id));
            }
        }

        window.editItem = function(id) {
            const item = estateData.find(x => x.id === id);
            if (!item) return;

            document.getElementById('editId').value = id;
            document.getElementById('title').value = item.title;
            document.getElementById('price').value = item.price.replace(/[^0-9]/g, '');
            document.getElementById('category').value = item.category;
            document.getElementById('loc').value = item.loc;
            document.getElementById('desc').value = item.desc;
            document.getElementById('m2').value = item.details?.m2 || '';
            document.getElementById('oda').value = item.details?.oda || '';
            document.getElementById('kat').value = item.details?.kat || '';
            document.getElementById('featured').checked = item.featured || false;

            if (item.images) {
                currentImages = [...item.images];
                renderGallery();
            }

            isEditMode = true;
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> GÜNCELLE';
            btn.classList.add('update-mode');
            document.getElementById('cancelBtn').style.display = 'block';
            document.getElementById('formTitle').innerHTML = 'İlanı Düzenle';
            document.getElementById('formPanel').scrollIntoView({behavior: 'smooth'});
        }

        window.cancelEdit = function() {
            isEditMode = false;
            document.getElementById('listingForm').reset();
            currentImages = [];
            renderGallery();
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> İLANI YAYINLA';
            btn.classList.remove('update-mode');
            document.getElementById('cancelBtn').style.display = 'none';
            document.getElementById('formTitle').innerHTML = 'Yeni İlan Ekle';
        }

        // ÇIKIŞ YAP FONKSİYONU - PHP İLE GÜNCELLENDİ
        // Artık direkt PHP logout sistemine yönlendiriyor
        window.logout = function() {
            window.location.href = '?action=logout';
        }
        
        // Session yenileme
        window.refreshSession = function() {
            window.location.href = '?action=refresh';
        }
        
        // Session süresi kontrolü (her 5 dakikada bir kontrol)
        setInterval(() => {
            const timeLeft = <?php echo $_SESSION["expiry_time"] - time(); ?>;
            if (timeLeft < 300) { // 5 dakikadan az kaldıysa
                if (confirm("Oturum süreniz dolmak üzere. Yenilemek ister misiniz?")) {
                    window.location.href = '?action=refresh';
                }
            }
        }, 60000); // Her dakika kontrol et
    </script>
</body>
</html>