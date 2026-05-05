# 🎯 YKS Hunter – Online Test Platformu

YKS sınavına hazırlık için online test çözme uygulaması.

- **Backend**: PHP (SQLite / PostgreSQL) → Render
- **Frontend**: React + Vite → Vercel

---

## 📁 Proje Yapısı

```
ykshunter/
├── backend/          ← PHP API (Render'a deploy edilir)
│   ├── index.php
│   ├── src/
│   │   ├── Router.php
│   │   ├── Database.php
│   │   └── controllers/
│   │       ├── QuizController.php
│   │       ├── AuthController.php
│   │       └── ResultController.php
│   └── composer.json
└── frontend/         ← React (Vercel'e deploy edilir)
    ├── src/
    ├── package.json
    └── vercel.json
```

---

## 🚀 Deploy Adımları

### 1. Backend – Render

1. [render.com](https://render.com) adresine git
2. **New → Web Service** seç
3. GitHub reposunu bağla, **Root Directory** = `backend`
4. Ayarlar:
   - **Environment**: PHP
   - **Build Command**: `echo ok`  
   - **Start Command**: `php -S 0.0.0.0:$PORT index.php`
5. **Environment Variables** ekle:
   - `DATABASE_URL` → PostgreSQL bağlantı string'i  
     (ya da boş bırak → SQLite kullanır; ancak Render'da dosya sistemi geçici!)
6. **Deploy** et → URL'i kopyala (örn. `https://ykshunter-api.onrender.com`)

> **Ücretsiz plan için not:** SQLite Render'ın geçici dosya sisteminde çalışır, restart sonrası veriler silinir. Üretim için ücretsiz PostgreSQL ekle: **New → PostgreSQL** → `DATABASE_URL`'i kopyala.

---

### 2. Frontend – Vercel

1. [vercel.com](https://vercel.com) adresine git
2. **Add New Project** → GitHub reposunu seç
3. **Root Directory** = `frontend`
4. **Framework Preset**: Vite
5. **Environment Variables** ekle:
   - `VITE_API_URL` = `https://ykshunter-api.onrender.com/api`
6. **Deploy** et!

---

## 💻 Yerel Geliştirme

### Backend başlat:
```bash
cd backend
php -S localhost:8000 index.php
```

### Frontend başlat:
```bash
cd frontend
npm install
npm run dev
```

Frontend `localhost:3000`'de, API proxy `localhost:8000`'e yönlendirilir.

---

## 📡 API Endpoints

| Method | Path | Açıklama |
|--------|------|----------|
| GET | /api/quiz/categories | Ders kategorileri |
| GET | /api/quiz/list?category=matematik | Testleri listele |
| POST | /api/quiz/start | Test başlat |
| GET | /api/quiz/question/:sessionId::index | Soru getir |
| POST | /api/quiz/submit | Cevap gönder |
| POST | /api/quiz/finish | Testi bitir |
| POST | /api/auth/register | Kayıt ol |
| POST | /api/auth/login | Giriş yap |
| GET | /api/auth/me | Profil |
| GET | /api/results/list | Sonuçlarım |
| GET | /api/results/detail/:id | Sonuç detayı |

---

## ➕ Yeni Soru Ekleme

Veritabanına doğrudan SQL ile ekleyebilirsin:

```sql
-- Yeni quiz ekle
INSERT INTO quizzes (category_id, title, description, difficulty, time_limit)
VALUES (1, 'Test Adı', 'Açıklama', 'orta', 1800);

-- Soru ekle (quiz_id = üstteki quizin id'si)
INSERT INTO questions (quiz_id, body, option_a, option_b, option_c, option_d, option_e, correct, explanation, sort_order)
VALUES (3, 'Soru metni?', 'A şık', 'B şık', 'C şık', 'D şık', 'E şık', 'b', 'Açıklama', 0);
```
