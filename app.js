/**
 * NEO YAPI - MAIN APPLICATION JS
 * Firebase powered, optimized, modular
 * Version: 2.0.0
 * GÜNCELLEME: Örnek veriler kaldırıldı, LocalStorage temizlendi
 */

import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getDatabase, ref, onValue } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js";

// ==================== FIREBASE CONFIGURATION ====================
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

// ==================== APPLICATION STATE ====================
const AppState = {
    allListings: [],
    currentListing: null,
    currentSlideIndex: 0,
    isLoading: false,
    filters: {
        search: '',
        category: 'all',
        sort: 'new'
    }
};

// ==================== YEDEK ÖRNEK VERİLER ====================
// NOT: Bu array artık BOŞ. Firebase'den veri gelmezse hiç ilan gösterilmez.
// Firebase ana veri kaynağınızdır, lütfen Firebase'den ilan ekleyin.
const SAMPLE_LISTINGS = [];

// ==================== FIREBASE INITIALIZATION ====================
const app = initializeApp(firebaseConfig);
const db = getDatabase(app);

// ==================== DOM ELEMENTS ====================
const DOM = {
    // Containers
    showcaseGrid: document.getElementById('showcaseGrid'),
    mainGrid: document.getElementById('mainGrid'),
    projectGrid: document.getElementById('projectGrid'),
    
    // Filter Elements
    searchInput: document.getElementById('searchInput'),
    filterCategory: document.getElementById('filterCategory'),
    filterSort: document.getElementById('filterSort'),
    
    // Info Elements
    vitrinCounter: document.getElementById('vitrinCounter'),
    resultCountLabel: document.getElementById('resultCountLabel'),
    
    // Modal Elements
    detailModal: document.getElementById('detailModal'),
    modalTitle: document.getElementById('m-title'),
    modalLoc: document.getElementById('m-loc'),
    modalPrice: document.getElementById('m-price'),
    modalDesc: document.getElementById('m-desc'),
    modalImg: document.getElementById('m-img'),
    modalCount: document.getElementById('m-count'),
    modalSpecs: document.getElementById('m-specs'),
    modalMap: document.getElementById('modal-map'),
    
    // Navigation
    navLinks: document.getElementById('navLinks')
};

// ==================== HELPER FUNCTIONS ====================
const Helpers = {
    // Fiyatı sayıya çevir (TL formatından)
    parsePrice: (priceStr) => {
        if (!priceStr) return 0;
        // "18.500.000 ₺" → "18500000"
        const cleanStr = priceStr.toString()
            .replace(/\./g, '')  // noktaları kaldır
            .replace(/[^\d]/g, ''); // sadece sayıları al
        return parseInt(cleanStr) || 0;
    },

    // Fiyatı formatla
    formatPrice: (price) => {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + " ₺";
    },

    // Get tag class based on category
    getTagClass: (category) => {
        const classes = {
            'konut': 'tag-green',
            'villa': 'tag-gold',
            'arsa': 'tag-navy',
            'isyeri': 'tag-red'
        };
        return classes[category] || 'tag-gold';
    },

    // Create loading placeholder
    createLoadingPlaceholder: (message = 'Yükleniyor...') => {
        return `
            <div class="loading-placeholder">
                <i class="fas fa-spinner fa-spin"></i>
                <p>${message}</p>
            </div>
        `;
    },

    // Create empty state message
    createEmptyState: (message = 'İlan bulunamadı') => {
        return `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>${message}</h3>
                <p>Arama kriterlerinize uygun ilan bulunamadı.</p>
                <button class="btn-filter" onclick="window.clearFilters()" style="margin-top: 20px;">
                    <i class="fas fa-times"></i> Filtreleri Temizle
                </button>
            </div>
        `;
    }
};

// ==================== FİLTRELEME SİSTEMİ ====================
const FilterSystem = {
    // Ana filtreleme fonksiyonu
    applyFilters: (listings) => {
        const { search, category, sort } = AppState.filters;
        
        let filtered = [...listings];
        
        // 1. Arama filtresi
        if (search && search.trim() !== '') {
            const searchTerm = search.toLowerCase().trim();
            filtered = filtered.filter(listing => {
                return (
                    listing.title.toLowerCase().includes(searchTerm) ||
                    listing.loc.toLowerCase().includes(searchTerm) ||
                    (listing.desc && listing.desc.toLowerCase().includes(searchTerm)) ||
                    (listing.tags && listing.tags.some(tag => 
                        tag.toLowerCase().includes(searchTerm)
                    ))
                );
            });
        }
        
        // 2. Kategori filtresi (ÖZEL: "featured" için)
        if (category !== 'all') {
            if (category === 'featured') {
                // "featured" özel durumu
                filtered = filtered.filter(listing => listing.featured === true);
            } else {
                // Normal kategori filtresi
                filtered = filtered.filter(listing => listing.category === category);
            }
        }
        
        // 3. Sıralama
        if (sort !== 'new') {
            switch (sort) {
                case 'price-asc':
                    filtered.sort((a, b) => {
                        const priceA = Helpers.parsePrice(a.price);
                        const priceB = Helpers.parsePrice(b.price);
                        return priceA - priceB;
                    });
                    break;
                    
                case 'price-desc':
                    filtered.sort((a, b) => {
                        const priceA = Helpers.parsePrice(a.price);
                        const priceB = Helpers.parsePrice(b.price);
                        return priceB - priceA;
                    });
                    break;
                    
                case 'featured':
                    // Vitrin ilanları önce
                    filtered.sort((a, b) => {
                        if (a.featured && !b.featured) return -1;
                        if (!a.featured && b.featured) return 1;
                        return 0;
                    });
                    break;
            }
        }
        
        return filtered;
    },
    
    // Filtre değerlerini güncelle
    updateFilterValues: () => {
        if (DOM.searchInput) {
            AppState.filters.search = DOM.searchInput.value;
        }
        if (DOM.filterCategory) {
            AppState.filters.category = DOM.filterCategory.value;
        }
        if (DOM.filterSort) {
            AppState.filters.sort = DOM.filterSort.value;
        }
    },
    
    // Filtreleri temizle
    clearFilters: () => {
        if (DOM.searchInput) DOM.searchInput.value = '';
        if (DOM.filterCategory) DOM.filterCategory.value = 'all';
        if (DOM.filterSort) DOM.filterSort.value = 'new';
        
        AppState.filters = {
            search: '',
            category: 'all',
            sort: 'new'
        };
        
        // Kategori butonlarını sıfırla
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.category === 'all') {
                btn.classList.add('active');
            }
        });
        
        // UI'ı güncelle
        UI.updateAll();
    }
};

// ==================== RENDER FUNCTIONS ====================
const Render = {
    // Render single listing card
    createListingCard: (listing) => {
        const img = listing.images && listing.images.length > 0 
            ? listing.images[0] 
            : 'https://via.placeholder.com/400x300/0F172A/FFFFFF?text=NEO+YAPI';
        
        const featuredBadge = listing.featured 
            ? `<span class="featured-badge" style="position:absolute; top:10px; right:10px; background:#C5A059; color:white; padding:6px 12px; border-radius:20px; font-size:11px; font-weight:700; z-index:5;">
                 <i class="fas fa-star"></i> VİTRİN
               </span>` 
            : '';
        
        return `
            <article class="card optimized-card" data-id="${listing.id}" data-category="${listing.category}">
                <div class="card-img-wrap">
                    <img src="${img}" alt="${listing.title} - ${listing.loc}" loading="lazy">
                    ${featuredBadge}
                    <span class="tag ${Helpers.getTagClass(listing.category)}">
                        ${listing.category.toUpperCase()}
                    </span>
                    <div class="overlay">
                        <i class="fas fa-search-plus"></i> İNCELE
                    </div>
                </div>
                <div class="card-body">
                    <div class="price">${listing.price}</div>
                    <h3 class="title">${listing.title}</h3>
                    <p class="loc">
                        <i class="fas fa-map-marker-alt"></i> ${listing.loc}
                    </p>
                    <div class="specs">
                        <span><i class="fas fa-ruler-combined"></i> ${listing.details?.m2 || '-'} m²</span>
                        <span><i class="fas fa-bed"></i> ${listing.details?.oda || '-'}</span>
                        <span><i class="fas fa-layer-group"></i> ${listing.details?.kat || '-'}</span>
                    </div>
                    <button class="btn-card" onclick="window.openModal('${listing.id}')">
                        DETAYLAR
                    </button>
                </div>
            </article>
        `;
    },

    // Render grid with listings
    renderGrid: (listings, containerId) => {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (listings.length === 0) {
            container.innerHTML = Helpers.createEmptyState();
            return;
        }

        container.innerHTML = listings.map(listing => 
            this.createListingCard(listing)
        ).join('');

        // Add click events to cards
        container.querySelectorAll('.card').forEach(card => {
            const listingId = card.dataset.id;
            const listing = listings.find(l => l.id === listingId);
            if (listing) {
                card.addEventListener('click', () => window.openModal(listingId));
            }
        });
    }
};

// ==================== DATA FUNCTIONS ====================
const Data = {
    // Load listings from Firebase
    loadFromFirebase: () => {
        const listingsRef = ref(db, 'listings');
        
        onValue(listingsRef, (snapshot) => {
            const data = snapshot.val();
            AppState.allListings = [];

            if (data && Object.keys(data).length > 0) {
                // Convert Firebase data to array
                Object.keys(data).forEach(key => {
                    AppState.allListings.push({
                        id: key,
                        ...data[key]
                    });
                });
                console.log(`✅ ${AppState.allListings.length} ilan Firebase'den yüklendi`);
            } else {
                // Firebase boşsa, boş array kullan (örnek yok)
                AppState.allListings = [];
                console.log("ℹ️ Firebase'de ilan bulunamadı");
            }

            // Update UI
            UI.updateAll();
            
        }, (error) => {
            console.error("⚠️ Firebase bağlantı hatası:", error);
            // Hata durumunda boş array
            AppState.allListings = [];
            UI.updateAll();
        });
    },

    // Get listing by ID
    getListingById: (id) => {
        return AppState.allListings.find(listing => listing.id === id);
    },

    // Get featured listings
    getFeaturedListings: () => {
        return AppState.allListings.filter(listing => listing.featured === true);
    }
};

// ==================== UI FUNCTIONS ====================
const UI = {
    // Update all UI elements
    updateAll: () => {
        // Filtre değerlerini güncelle
        FilterSystem.updateFilterValues();
        
        // Filtreleme uygula
        const filteredListings = FilterSystem.applyFilters(AppState.allListings);
        const featuredListings = FilterSystem.applyFilters(
            AppState.allListings.filter(listing => listing.featured === true)
        );
        
        // Update counters
        if (DOM.vitrinCounter) {
            DOM.vitrinCounter.textContent = `${featuredListings.length} ilan`;
        }
        
        if (DOM.resultCountLabel) {
            const totalFiltered = filteredListings.length;
            const totalAll = AppState.allListings.length;
            if (totalFiltered === totalAll) {
                DOM.resultCountLabel.textContent = `${totalFiltered} ilan listeleniyor`;
            } else {
                DOM.resultCountLabel.textContent = `${totalFiltered} ilan bulundu (${totalAll} toplam)`;
            }
        }
        
        // Render grids based on current page
        const isProjectPage = window.location.pathname.includes('project.html');
        
        if (DOM.showcaseGrid) {
            Render.renderGrid(featuredListings, 'showcaseGrid');
        }
        
        if (DOM.mainGrid && !isProjectPage) {
            Render.renderGrid(filteredListings, 'mainGrid');
        }
        
        if (DOM.projectGrid && isProjectPage) {
            Render.renderGrid(filteredListings, 'projectGrid');
            
            // Project page stats
            const featuredCount = AppState.allListings.filter(l => l.featured).length;
            const activeFilter = document.getElementById('activeFilter');
            const totalListings = document.getElementById('totalListings');
            const featuredListingsEl = document.getElementById('featuredListings');
            
            if (activeFilter) activeFilter.textContent = filteredListings.length;
            if (totalListings) totalListings.textContent = AppState.allListings.length;
            if (featuredListingsEl) featuredListingsEl.textContent = featuredCount;
        }
    },

    // Show modal
    showModal: (listingId) => {
        const listing = Data.getListingById(listingId);
        if (!listing) return;
        
        AppState.currentListing = listing;
        AppState.currentSlideIndex = 0;
        
        // Modal içeriğini güncelle
        if (DOM.modalTitle) DOM.modalTitle.textContent = listing.title;
        if (DOM.modalLoc) DOM.modalLoc.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${listing.loc}`;
        if (DOM.modalPrice) DOM.modalPrice.textContent = listing.price;
        if (DOM.modalDesc) DOM.modalDesc.textContent = listing.desc;
        
        // Update images
        if (listing.images && listing.images.length > 0 && DOM.modalImg) {
            DOM.modalImg.src = listing.images[0];
            DOM.modalImg.alt = `${listing.title} - Fotoğraf 1`;
            if (DOM.modalCount) DOM.modalCount.textContent = `1 / ${listing.images.length}`;
        }
        
        // Update specifications
        if (DOM.modalSpecs) {
            DOM.modalSpecs.innerHTML = `
                <div class="spec-grid">
                    <div class="spec-item">
                        <i class="fas fa-ruler-combined" style="color:#C5A059;"></i>
                        <span>ALAN</span>
                        <strong>${listing.details?.m2 || '-'} m²</strong>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-bed" style="color:#C5A059;"></i>
                        <span>ODA</span>
                        <strong>${listing.details?.oda || '-'}</strong>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-layer-group" style="color:#C5A059;"></i>
                        <span>KAT</span>
                        <strong>${listing.details?.kat || '-'}</strong>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-bath" style="color:#C5A059;"></i>
                        <span>BANYO</span>
                        <strong>${listing.details?.banyo || '-'}</strong>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-calendar-alt" style="color:#C5A059;"></i>
                        <span>YAŞ</span>
                        <strong>${listing.details?.yas || '-'}</strong>
                    </div>
                    <div class="spec-item">
                        <i class="fas fa-fire" style="color:#C5A059;"></i>
                        <span>ISINMA</span>
                        <strong>${listing.details?.isinma || '-'}</strong>
                    </div>
                </div>
            `;
        }
        
        // Update map
        if (DOM.modalMap) {
            const query = encodeURIComponent(listing.loc + ', Antalya');
            DOM.modalMap.innerHTML = `
                <iframe 
                    width="100%" 
                    height="100%" 
                    frameborder="0" 
                    style="border:0" 
                    src="https://maps.google.com/maps?q=${query}&t=&z=14&ie=UTF8&iwloc=&output=embed"
                    loading="lazy"
                    title="${listing.title} konumu">
                </iframe>
            `;
        }
        
        // Update WhatsApp button
        const whatsappBtn = document.querySelector('.action-buttons a[href*="whatsapp"]');
        if (whatsappBtn) {
            const message = `Merhaba, ${encodeURIComponent(listing.title)} (${listing.price}) ilanı hakkında bilgi almak istiyorum.`;
            whatsappBtn.href = `https://wa.me/905321002030?text=${encodeURIComponent(message)}`;
        }
        
        if (DOM.detailModal) {
            DOM.detailModal.classList.add('active');
            DOM.detailModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    },

    // Hide modal
    hideModal: () => {
        if (DOM.detailModal) {
            DOM.detailModal.classList.remove('active');
            DOM.detailModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
            AppState.currentListing = null;
        }
    },

    // Change slide in modal
    changeSlide: (direction) => {
        if (!AppState.currentListing || !AppState.currentListing.images || 
            AppState.currentListing.images.length <= 1) return;
        
        AppState.currentSlideIndex += direction;
        
        if (AppState.currentSlideIndex < 0) {
            AppState.currentSlideIndex = AppState.currentListing.images.length - 1;
        } else if (AppState.currentSlideIndex >= AppState.currentListing.images.length) {
            AppState.currentSlideIndex = 0;
        }
        
        const img = AppState.currentListing.images[AppState.currentSlideIndex];
        if (DOM.modalImg) {
            DOM.modalImg.src = img;
            DOM.modalImg.alt = `${AppState.currentListing.title} - Fotoğraf ${AppState.currentSlideIndex + 1}`;
        }
        if (DOM.modalCount) {
            DOM.modalCount.textContent = `${AppState.currentSlideIndex + 1} / ${AppState.currentListing.images.length}`;
        }
    },

    // Toggle mobile menu
    toggleMenu: () => {
        if (DOM.navLinks) {
            const isExpanded = DOM.navLinks.classList.toggle('active');
            const menuToggle = document.querySelector('.menu-toggle');
            if (menuToggle) menuToggle.setAttribute('aria-expanded', isExpanded);
        }
    },

    // Kategori butonu ile filtreleme (project.html için)
    filterByCategory: (category) => {
        // Kategori butonlarını güncelle
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.category === category) {
                btn.classList.add('active');
            }
        });
        
        // Dropdown'u güncelle (featured hariç)
        if (category !== 'featured' && DOM.filterCategory) {
            DOM.filterCategory.value = category;
        } else if (category === 'featured' && DOM.filterSort) {
            // Featured için sıralama dropdown'ını güncelle
            DOM.filterSort.value = 'featured';
        }
        
        // Filtreleri uygula
        AppState.filters.category = category;
        UI.updateAll();
    }
};

// ==================== EVENT LISTENERS ====================
const Events = {
    init: () => {
        // Filter events
        if (DOM.searchInput) {
            DOM.searchInput.addEventListener('input', () => {
                // Debounce ekle (performans için)
                clearTimeout(Events.searchTimeout);
                Events.searchTimeout = setTimeout(() => {
                    UI.updateAll();
                }, 300);
            });
        }
        
        if (DOM.filterCategory) {
            DOM.filterCategory.addEventListener('change', UI.updateAll);
        }
        
        if (DOM.filterSort) {
            DOM.filterSort.addEventListener('change', UI.updateAll);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') UI.hideModal();
            if (e.key === 'ArrowLeft' && DOM.detailModal && DOM.detailModal.classList.contains('active')) {
                UI.changeSlide(-1);
            }
            if (e.key === 'ArrowRight' && DOM.detailModal && DOM.detailModal.classList.contains('active')) {
                UI.changeSlide(1);
            }
        });
        
        // Close modal on outside click
        document.addEventListener('click', (e) => {
            if (e.target === DOM.detailModal) {
                UI.hideModal();
            }
        });
        
        // Add optimized-card class to all cards
        setTimeout(() => {
            document.querySelectorAll('.card').forEach(card => {
                card.classList.add('optimized-card');
            });
        }, 500);
    },
    
    searchTimeout: null
};

// ==================== GLOBAL WINDOW FUNCTIONS ====================
// These are exposed to the window object for inline event handlers
window.openModal = (listingId) => UI.showModal(listingId);
window.closeModal = () => UI.hideModal();
window.changeSlide = (direction) => UI.changeSlide(direction);
window.applyFilter = () => UI.updateAll();
window.toggleMenu = () => UI.toggleMenu();
window.clearFilters = () => FilterSystem.clearFilters();
window.filterByCategory = (category) => UI.filterByCategory(category);

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', () => {
    console.log("🚀 NEO YAPI uygulaması başlatılıyor...");
    
    // Eski LocalStorage verilerini temizle (sessizce)
    try {
        if (localStorage.getItem('estateData')) {
            localStorage.removeItem('estateData');
            console.log("🧹 Eski LocalStorage verileri temizlendi");
        }
    } catch (e) {
        // Hata olursa görmezden gel
    }
    
    // Initialize Firebase connection
    Data.loadFromFirebase();
    
    // Set up event listeners
    Events.init();
    
    // Initial UI update
    UI.updateAll();
    
    console.log("✅ Sistem hazır! (Örnek veriler kaldırıldı)");
});

// Export for testing/development
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AppState,
        Data,
        UI,
        Render,
        Helpers,
        FilterSystem,
        SAMPLE_LISTINGS
    };
}