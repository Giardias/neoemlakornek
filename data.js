/**
 * NEO YAPI - MASTER DATABASE
 * Bu dosya, site ilk yüklendiğinde "LocalStorage" boşsa devreye girer.
 * GÜNCELLEME: Artık boş array yazılıyor, örnek veri yok.
 */

// BOŞ ARRAY - Örnek veri yok
const defaultData = [];

// SİSTEM KONTROLÜ: Eğer tarayıcıda veri yoksa boş array yükle
window.addEventListener('load', function() {
    if (!localStorage.getItem('estateData')) {
        console.log("✅ LocalStorage boş array ile başlatıldı.");
        localStorage.setItem('estateData', JSON.stringify(defaultData));
    }
});