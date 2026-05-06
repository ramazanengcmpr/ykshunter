<?php

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn = getenv('DATABASE_URL');

            if ($dsn) {
                $dsn = preg_replace('/^postgres:\/\//', 'pgsql://', $dsn);
                self::$instance = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } else {
                $path = __DIR__ . '/../../ykshunter.sqlite';
                self::$instance = new PDO("sqlite:$path", null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec('PRAGMA journal_mode=WAL;');
            }

            self::migrate(self::$instance);
        }
        return self::$instance;
    }

    private static function migrate(PDO $db): void {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $auto   = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'SERIAL PRIMARY KEY';
        $now    = $driver === 'sqlite' ? "datetime('now')" : 'NOW()';

        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id $auto,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT DEFAULT ($now)
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS categories (
            id $auto,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            color TEXT DEFAULT '#6366f1',
            icon TEXT DEFAULT '📚'
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS quizzes (
            id $auto,
            category_id INTEGER,
            title TEXT NOT NULL,
            description TEXT,
            difficulty TEXT DEFAULT 'orta',
            time_limit INTEGER DEFAULT 1800,
            created_at TEXT DEFAULT ($now)
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS questions (
            id $auto,
            quiz_id INTEGER NOT NULL,
            body TEXT NOT NULL,
            option_a TEXT NOT NULL,
            option_b TEXT NOT NULL,
            option_c TEXT NOT NULL,
            option_d TEXT NOT NULL,
            option_e TEXT NOT NULL,
            correct TEXT NOT NULL,
            explanation TEXT,
            sort_order INTEGER DEFAULT 0
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS sessions (
            id $auto,
            user_id INTEGER,
            quiz_id INTEGER NOT NULL,
            answers TEXT DEFAULT '{}',
            question_order TEXT,
            started_at TEXT DEFAULT ($now),
            finished_at TEXT,
            score REAL,
            total INTEGER,
            correct_count INTEGER
        )");

        // Add question_order to existing tables (idempotent)
        try { $db->exec("ALTER TABLE sessions ADD COLUMN question_order TEXT"); } catch (\Throwable $e) { /* already exists */ }

        $count = (int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        if ($count === 0) {
            self::seed($db);
        }
    }

    private static function seed(PDO $db): void {

        // ── Kategoriler ──────────────────────────────────────────────
        $catStmt = $db->prepare('INSERT INTO categories (name, slug, color, icon) VALUES (?,?,?,?)');
        foreach ([
            ['Matematik', 'matematik', '#f59e0b', '🔢'],
            ['Türkçe',    'turkce',    '#10b981', '📖'],
            ['Fizik',     'fizik',     '#3b82f6', '⚛️'],
            ['Kimya',     'kimya',     '#8b5cf6', '🧪'],
            ['Biyoloji',  'biyoloji',  '#ef4444', '🧬'],
            ['Tarih',     'tarih',     '#f97316', '🏛️'],
            ['Coğrafya',  'cografya',  '#06b6d4', '🗺️'],
            ['Felsefe',   'felsefe',   '#ec4899', '🤔'],
        ] as $c) $catStmt->execute($c);

        $qStmt = $db->prepare(
            'INSERT INTO questions (quiz_id,body,option_a,option_b,option_c,option_d,option_e,correct,explanation,sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );

        $addQuiz = function(int $catId, string $title, string $desc, string $diff, int $time) use ($db): int {
            $s = $db->prepare('INSERT INTO quizzes (category_id,title,description,difficulty,time_limit) VALUES (?,?,?,?,?)');
            $s->execute([$catId, $title, $desc, $diff, $time]);
            return (int)$db->lastInsertId();
        };

        $addQs = function(int $qid, array $qs) use ($qStmt): void {
            foreach ($qs as $i => $q) {
                $qStmt->execute(array_merge([$qid], $q, [$i]));
            }
        };

        // ═══════════════════════════════════════════
        // 1. MATEMATİK
        // ═══════════════════════════════════════════
        $id = $addQuiz(1, 'TYT Matematik – Temel Kavramlar', 'Sayılar, işlemler ve temel cebir soruları', 'orta', 1500);
        $addQs($id, [
            ['2^10 değeri kaçtır?','512','1024','2048','256','128','b','2^10 = 1024'],
            ['x² − 5x + 6 = 0 denkleminin kökleri nelerdir?','x=1, x=6','x=2, x=3','x=−2, x=−3','x=1, x=5','x=3, x=4','b','(x−2)(x−3)=0 → x=2 veya x=3'],
            ['log₁₀(1000) kaçtır?','2','4','10','100','3','e','log₁₀(10³) = 3'],
            ['(a+b)² açılımı nedir?','a²+b²','a²−2ab+b²','2a+2b','a²+ab+b²','a²+2ab+b²','e','(a+b)² = a²+2ab+b²'],
            ['0! kaçtır?','0','−1','Tanımsız','Sonsuz','1','e','Tanım gereği 0! = 1'],
            ['12 ve 18\'in EBOB\'u kaçtır?','3','6','9','36','12','b','12=2²·3, 18=2·3² → EBOB = 6'],
            ['Bir dairenin alanı formülü nedir?','2πr','πd','πr','2πr²','πr²','e','A = πr²'],
            ['√144 kaçtır?','11','13','12','14','10','c','12² = 144'],
            ['3/4 + 5/6 toplamı kaçtır?','8/10','7/12','19/12','2/3','1','c','9/12 + 10/12 = 19/12'],
            ['Bir sayının %25\'i 15 ise sayı kaçtır?','45','55','60','75','80','c','x · 0,25 = 15 → x = 60'],
            ['5! (beş faktöriyel) kaçtır?','20','60','100','120','24','d','5! = 5·4·3·2·1 = 120'],
            ['(-3)² kaçtır?','−9','−6','6','9','3','d','(-3)² = 9'],
        ]);

        $id = $addQuiz(1, 'AYT Matematik – Fonksiyon ve Türev', 'Limit, türev ve integral soruları', 'zor', 2400);
        $addQs($id, [
            ['f(x)=x²+3x için f\'(x) nedir?','x+3','2x+3','2x−3','x²+3','3x','b','d/dx(x²)=2x, d/dx(3x)=3 → 2x+3'],
            ['∫2x dx ifadesinin sonucu nedir?','x+C','2+C','x³+C','x²+C','2x²+C','d','∫2x dx = x²+C'],
            ['lim(x→2) (x²−4)/(x−2) limiti kaçtır?','0','2','4','8','Tanımsız','c','x²−4=(x−2)(x+2) → lim = 4'],
            ['f(x)=sin(x) için f\'(x) nedir?','−sin(x)','tan(x)','cos(x)','−cos(x)','1','c','d/dx[sin(x)] = cos(x)'],
            ['e^0 kaçtır?','0','e','−1','∞','1','e','Her sayının 0. kuvveti 1\'dir'],
            ['Bir fonksiyonun tersi varsa nasıl olmalıdır?','Sürekli','Türevlenebilir','Birebir ve örten','Monoton artan','Pozitif','c','Ters fonksiyon için birebir ve örten (bijeksiyon) olmalıdır'],
            ['f(x)=x³ için f\'\'(x) nedir?','3x','6x','x²','3x²','6','b','f\'(x)=3x², f\'\'(x)=6x'],
            ['sin²(x)+cos²(x) kaçtır?','0','sin(2x)','cos(2x)','2','1','e','Pisagor özdeşliği: sin²x+cos²x=1'],
            ['∫₀¹ x dx kaçtır?','0','1','2','1/4','1/2','e','[x²/2]₀¹ = 1/2'],
            ['log₂(8) kaçtır?','2','4','1','8','3','e','2³=8 → log₂8=3'],
        ]);

        // ═══════════════════════════════════════════
        // 2. TÜRKÇE
        // ═══════════════════════════════════════════
        $id = $addQuiz(2, 'TYT Türkçe – Dil Bilgisi', 'Ses, yapı ve cümle bilgisi soruları', 'orta', 1200);
        $addQs($id, [
            ['"Bıçak kemiğe dayandı" cümlesinde hangi söz sanatı var?','Teşbih','Telmih','Mecaz-ı Mürsel','Deyim','Kinaye','d','Tahammülün son noktasını anlatan bir deyimdir'],
            ['Aşağıdakilerden hangisi bileşik cümledir?','Kitabı okudu.','Güzel bir gündü.','Eve gelince uyudu.','Kapıyı kapattı.','Koşarak geldi.','c','Zarf-fiil bağlantılı bileşik cümle'],
            ['"Kırmızı elma" tamlamasının türü nedir?','Belirtisiz ad','Belirtili ad','Sıfat tamlaması','Zarf tamlaması','Takısız ad','c','sıfat + isim yapısı → sıfat tamlaması'],
            ['Hangi sözcük yapım eki almıştır?','geldim','kitapçı','okula','evde','geldi','b','"-çı" yapım ekidir'],
            ['Türkçede ünlü uyumu kaç çeşittir?','1','3','4','2','5','d','Büyük ünlü uyumu + küçük ünlü uyumu = 2 çeşit'],
            ['"Güzellik" sözcüğünde kaç ek vardır?','0','1','2','3','4','b','"güzel" kök + "-lik" yapım eki = 1 ek'],
            ['Aşağıdakilerden hangisi sıfat-fiildir?','koşarak','okuyunca','gelen','bakıp','yürürken','c','"gelen" sıfat-fiil ekiyle yapılmıştır'],
            ['Türk alfabesinde kaç harf vardır?','26','28','30','31','29','e','Türk alfabesi 29 harften oluşur'],
            ['Aşağıdakilerden hangisi atasözüdür?','Bıçak kemiğe dayandı','Taşıma su ile değirmen dönmez','Göz gördü gönül sevdi','Başını taştan taşa vurdu','Dili varmıyor','b','Atasözleri evrensel öğüt içeren kalıplaşmış sözlerdir'],
            ['Hangisi fiilimsidir?','güzellik','koşmak','evcilik','taşlık','kitaplık','b','"koşmak" mastar eki almış isim-fiildir'],
            ['Noktalama işaretlerinden hangisi soru anlamı taşır?','Ünlem','Virgül','Noktalı virgül','Soru işareti','İki nokta','d','Soru işareti (?) soru cümlelerinin sonuna konur'],
            ['"Çalışkan öğrenci başardı" cümlesinde "çalışkan" hangi görevdedir?','Zarf','Yüklem','Sıfat','Özne','Nesne','c','İsmi nitelediği için sıfattır'],
        ]);

        $id = $addQuiz(2, 'TYT Türkçe – Anlam Bilgisi', 'Anlam, paragraf ve söz sanatları soruları', 'orta', 1200);
        $addQs($id, [
            ['Mecaz anlam nedir?','Sözcüğün ilk anlamı','Sözcüğün gerçek anlamı','Sözcüğün değiştirilmiş yan anlamı','Sözcüğün sözlük anlamı','Sözcüğün terim anlamı','c','Mecaz: gerçek anlamından uzaklaşarak kazanılan yeni anlam'],
            ['"Ağır" sözcüğü hangi cümlede mecaz anlamda kullanılmıştır?','Taş çok ağırdı.','Kova ağır geldi.','Ağır davranma, gecikirsin.','Çanta ağır.','Yük ağırdı.','c','"Ağır davranmak" mecaz olarak "yavaş hareket etmek" demektir'],
            ['Paragrafın ana fikri nerede bulunur?','Yalnızca başında','Yalnızca sonunda','Ortasında','Başında, sonunda veya ortasında','Her cümlede','d','Ana fikir paragrafın herhangi bir yerinde olabilir'],
            ['Terim nedir?','Kalıplaşmış söz','Bir bilim dalına özgü özel anlam taşıyan sözcük','Argo sözcük','Yabancı sözcük','İkileme','b','Terim: belirli bir alanda özel anlamı olan sözcüktür'],
            ['İkileme ne anlam katar?','Zayıflatma','Soru','Pekiştirme veya zenginleştirme','Olumsuzluk','Geçmiş zaman','c','İkilemeler anlama pekiştirme veya zenginlik katar'],
            ['"Göz" sözcüğü hangi cümlede çok anlamlı kullanılmıştır?','İki gözüm','Gözüm ağrıdı','Çantanın gözü','Gözlerim kapalı','Göz hekimi','c','"Çantanın gözü" başka bir anlam taşır'],
            ['Eş anlamlı (anlamdaş) için doğru örnek hangisidir?','soğuk – sıcak','hızlı – çabuk','büyük – küçük','güzel – çirkin','açık – kapalı','b','"hızlı" ve "çabuk" eş anlamlıdır'],
            ['Karşıt anlamlı için doğru örnek hangisidir?','araba – otomobil','güzel – hoş','aydınlık – karanlık','tez – çabuk','yürek – kalp','c','"aydınlık" ile "karanlık" zıt anlamlıdır'],
            ['Aşağıdakilerden hangisi deyim değildir?','Kulak vermek','Göz atmak','El ele vermek','Taşıma su ile değirmen dönmez','Dil dökmek','d','Bu bir atasözüdür, deyim değildir'],
            ['Nesnel yargı nedir?','Kişisel görüş','Duyguya dayalı','Tahmine dayalı','İspatsız','Kanıtlanabilir, herkes tarafından doğrulanabilir','e','Nesnel yargı ölçülebilir ve kanıtlanabilirdir'],
        ]);

        // ═══════════════════════════════════════════
        // 3. FİZİK
        // ═══════════════════════════════════════════
        $id = $addQuiz(3, 'TYT Fizik – Kuvvet ve Hareket', 'Newton yasaları, enerji ve iş soruları', 'orta', 1500);
        $addQs($id, [
            ['Newton\'un 1. Yasası nedir?','F=ma','Kuvvet kütleyle orantılı','Her etkiye eşit zıt tepki','Net kuvvet sıfırsa cisim hareketi değişmez','Enerji korunur','d','Eylemsizlik yasası: dış kuvvet yoksa cisim durumunu korur'],
            ['F=ma formülünde "a" neyi temsil eder?','Alan','Açısal hız','İvme','Ağırlık','Yoğunluk','c','a: ivme (m/s²)'],
            ['Serbest düşüşte yer çekimi ivmesi yaklaşık kaçtır?','5 m/s²','15 m/s²','9,8 m/s²','0 m/s²','1 m/s²','c','g ≈ 9,8 m/s²'],
            ['Enerji birimi nedir?','Newton','Watt','Joule','Pascal','Amper','c','Enerji SI birimi Joule (J)'],
            ['Kinetik enerji formülü nedir?','mgh','Fd','mv','Pt','½mv²','e','KE = ½mv²'],
            ['İş-enerji teoremi neyi ifade eder?','Güç sabittir','Momentum korunur','Net iş = kinetik enerji değişimi','Potansiyel enerji sabittir','Kuvvet sabittir','c','W_net = ΔKE'],
            ['Sürtünme kuvvetinin yönü nasıldır?','Harekete dik','Harekete aynı yönde','Harekete karşı','Her zaman aşağı','Her zaman yukarı','c','Sürtünme harekete karşı yönde etkir'],
            ['1 Newton kaç kg·m/s²\'dir?','10','100','0,1','0,01','1','e','1 N = 1 kg·m/s²'],
            ['Potansiyel enerji formülü nedir?','½mv²','Fd','mv','Pt','mgh','e','PE = mgh'],
            ['Güç birimi nedir?','Joule','Newton','Pascal','Coulomb','Watt','e','Güç birimi Watt (W = J/s)'],
            ['Ses dalgaları hangi tür dalgadır?','Elektromanyetik','Enine (transvers)','Boyuna (longitudinal)','Duran dalga','Yüzey dalgası','c','Ses mekanik boyuna (longitudinal) dalgadır'],
            ['Momentum nasıl hesaplanır?','F·t','m·a','½mv²','m·g·h','m·v','e','p = mv (kütle × hız)'],
        ]);

        $id = $addQuiz(3, 'AYT Fizik – Elektrik ve Manyetizma', 'Devre, manyetik alan ve elektromanyetizma soruları', 'zor', 2000);
        $addQs($id, [
            ['Ohm Yasası nedir?','P=VI','W=Fd','F=ma','E=mc²','V=IR','e','V = IR (Voltaj = Akım × Direnç)'],
            ['Elektrik akımının birimi nedir?','Volt','Ohm','Watt','Amper','Coulomb','d','Akım birimi Amper (A)'],
            ['Seri bağlı dirençlerde toplam direnç nasıl bulunur?','R=1/R₁+1/R₂','R=R₁·R₂','R=R₁−R₂','R=R₁/R₂','R=R₁+R₂','e','Seri: R_top = R₁+R₂+...'],
            ['Paralel bağlı dirençlerde toplam direnç nasıl bulunur?','R=R₁+R₂','R=R₁·R₂/(R₁+R₂)','R=(R₁+R₂)/2','R=R₁−R₂','R=1/(R₁+R₂)','b','1/R = 1/R₁+1/R₂'],
            ['Elektrik gücü formülü nedir?','P=IR','P=V/I','P=I/V','P=R/V','P=VI','e','P = VI = I²R = V²/R'],
            ['Manyetik alan birimi nedir?','Amper','Volt','Ohm','Coulomb','Tesla','e','Manyetik alan birimi Tesla (T)'],
            ['Faraday İndüksiyon Yasası neyi ifade eder?','Yük korunur','Enerji korunur','Değişen manyetik alan EMK üretir','Akım sabittir','Direnç artarsa akım artar','c','Değişen manyetik akı EMK doğurur'],
            ['Elektrik yükünün birimi nedir?','Amper','Volt','Watt','Ohm','Coulomb','e','Yük birimi Coulomb (C)'],
            ['Kapasitörün görevi nedir?','Akımı artırmak','Direnci azaltmak','Enerji depolamak','Voltajı artırmak','Manyetik alan üretmek','c','Kapasitörler elektrik enerjisini depolar'],
            ['Elektromanyetik dalgaların vakumdaki hızı kaçtır?','3×10⁸ m/s','3×10⁶ m/s','3×10⁴ m/s','3×10¹⁰ m/s','1,5×10⁸ m/s','a','Işık hızı c ≈ 3×10⁸ m/s'],
        ]);

        // ═══════════════════════════════════════════
        // 4. KİMYA
        // ═══════════════════════════════════════════
        $id = $addQuiz(4, 'TYT Kimya – Atom ve Periyodik Sistem', 'Atom yapısı ve periyodik tablo soruları', 'orta', 1500);
        $addQs($id, [
            ['Atom numarası neyi gösterir?','Nötron sayısı','Proton sayısı','Elektron sayısı','Kütle numarası','Nükleon sayısı','b','Z = proton sayısı'],
            ['Soy gazlar periyodik tablonun kaçıncı grubundadır?','1','14','16','17','18','e','Nobel gazlar 18. gruptadır'],
            ['Nötr atomda elektron sayısı neye eşittir?','Nötron sayısı','Kütle numarası','Atom numarası','Periyot numarası','Grup numarası','c','Nötr atomda elektron = proton = atom numarası'],
            ['Su molekülünün formülü nedir?','H₂O₂','HO','H₃O','OH','H₂O','e','2 H + 1 O → H₂O'],
            ['Elektronegatiflik periyotta soldan sağa nasıl değişir?','Azalır','Önce artar sonra azalır','Değişmez','Önce azalır sonra artar','Artar','e','Periyotta sağa gidildikçe elektronegatiflik artar'],
            ['Kütle numarası neye eşittir?','Proton sayısı','Elektron sayısı','Nötron sayısı','Proton + Nötron','Elektron + Nötron','d','A = Z + N'],
            ['İzotop atomlar hangi bakımdan farklıdır?','Proton sayısı','Elektron sayısı','Atom numarası','Nötron sayısı','Kimyasal özellikler','d','İzotoplar: aynı proton, farklı nötron sayısı'],
            ['Halojenler hangi gruptadır?','1','14','16','17','18','d','F, Cl, Br, I, At → 17. grup'],
            ['Periyodik tabloda sıralama ölçütü nedir?','Kütle numarası','Nötron sayısı','Atom numarası','Elektron sayısı','Erime noktası','c','Modern tabloda sıralama atom numarasına göredir'],
            ['En hafif element hangisidir?','Helyum','Lityum','Oksijen','Karbon','Hidrojen','e','Hidrojen (H, A=1) en hafif elementtir'],
        ]);

        $id = $addQuiz(4, 'AYT Kimya – Kimyasal Tepkimeler', 'Denklem, asit-baz ve redoks soruları', 'zor', 2000);
        $addQs($id, [
            ['Asit + Baz tepkimesi sonucu ne oluşur?','Asit + Baz','Element + Bileşik','Su + Tuz','Yalnızca Su','Yalnızca Tuz','c','Nötrleşme: Asit + Baz → Su + Tuz'],
            ['pH=7 ne anlama gelir?','Asidik','Kuvvetli bazik','Kuvvetli asidik','Çok bazik','Nötr çözelti','e','pH=7 nötr çözeltidir (saf su)'],
            ['Redoks tepkimesinde oksidasyon nedir?','Elektron alınması','Proton kaybı','Enerji kaybı','Elektron verilmesi','Yük artışı','d','Oksidasyon: elektron kaybı'],
            ['Kimyasal denklem dengelenirken ne korunur?','Yalnızca kütle','Yalnızca mol','Kütle ve enerji','Yalnızca yük','Kütle ve yük (atom sayısı)','e','Her elementin atom sayısı iki tarafta eşitlenir'],
            ['Katı çözünürlüğü genellikle sıcaklıkla nasıl değişir?','Azalır','Önce artar sonra azalır','Değişmez','Önce azalır sonra artar','Artar','e','Çoğu katı için sıcaklık ↑ → çözünürlük ↑'],
            ['Molar kütle nedir?','1 atom kütlesi','1 mol maddenin gram kütlesi','1 L çözeltinin kütlesi','Molekül sayısı','Avogadro sayısı','b','Molar kütle: 1 molün gram kütlesi (g/mol)'],
            ['Avogadro sayısı yaklaşık kaçtır?','3,14×10²³','6,02×10²³','1,38×10²³','9,11×10²³','2,71×10²³','b','N_A ≈ 6,02×10²³ parçacık/mol'],
            ['Organik kimyanın temel elementi hangisidir?','Oksijen','Azot','Kükürt','Karbon','Fosfor','d','Organik bileşiklerin iskeleti Karbondur'],
            ['Endotermik tepkime nedir?','Isı veren','Renk değiştiren','Gaz oluşturan','Isı alan','Çökelti oluşturan','d','Endotermik: ortamdan ısı alır → ΔH > 0'],
            ['Katalizör ne yapar?','Tepkimeye girer','Denge sabitini değiştirir','Aktivasyon enerjisini düşürür','Ürün miktarını artırır','Reaktan azaltır','c','Katalizör Ea\'yı düşürür, kendisi değişmez'],
        ]);

        // ═══════════════════════════════════════════
        // 5. BİYOLOJİ
        // ═══════════════════════════════════════════
        $id = $addQuiz(5, 'TYT Biyoloji – Hücre ve Canlılar', 'Hücre yapısı ve canlıların özellikleri', 'orta', 1500);
        $addQs($id, [
            ['Hücrenin kontrol merkezi hangisidir?','Mitokondri','Ribozom','Hücre zarı','Çekirdek','Kloroplast','d','Çekirdek genetik bilgiyi ve hücre faaliyetlerini yönetir'],
            ['Fotosentez hangi organelde gerçekleşir?','Mitokondri','Çekirdek','Ribozom','Koful','Kloroplast','e','Fotosentez kloroplastlarda gerçekleşir'],
            ['ATP nedir?','Amino asit','Şeker','Hücrenin enerji para birimi','Protein','Yağ asidi','c','ATP hücresel enerji taşıyıcısıdır'],
            ['DNA nerede bulunur?','Yalnızca sitoplazmada','Ribozomda','Yalnızca çekirdekte','Hücre zarında','Çekirdek ve mitokondrida','e','DNA çekirdek ve mitokondride bulunur'],
            ['Prokaryot ile ökaryot hücrenin temel farkı nedir?','Ribozom varlığı','Hücre zarı','Zarlı çekirdek varlığı','Hücre duvarı','Metabolizma','c','Prokaryotlarda zarlı çekirdek yoktur'],
            ['Mitoz bölünmenin amacı nedir?','Genetik çeşitlilik','Üreme hücresi üretimi','Büyüme ve onarım','Kromozom sayısını azaltmak','Enerji üretimi','c','Mitoz: büyüme, onarım ve eşeysiz üreme'],
            ['Protein sentezi hangi organelde yapılır?','Mitokondri','Kloroplast','Koful','Ribozom','Çekirdek','d','Proteinler ribozomlarda sentezlenir'],
            ['Hücre zarının temel yapısı nedir?','Selüloz','Kitin','Fosfolipid çift tabaka','Glikojen','Protein katmanı','c','Hücre zarı fosfolipid çift tabakadan oluşur'],
            ['Hayvan hücrelerinde bulunmayan organel hangisidir?','Mitokondri','Ribozom','Kloroplast','Golgi','Lizozom','c','Kloroplast yalnızca bitki hücrelerinde bulunur'],
            ['Osmoz nedir?','Aktif taşıma','Çözünen madde hareketi','Yarı geçirgen zardan suyun difüzyonu','Büyük molekül taşıma','Enerji gerektiren geçiş','c','Osmoz: düşük derişimden yüksek derişime su geçişi'],
            ['Solunum hangi organelde gerçekleşir?','Kloroplast','Çekirdek','Ribozom','Golgi','Mitokondri','e','Hücresel solunum mitokondride gerçekleşir'],
            ['Bitki hücresini hayvan hücresinden ayıran yapı hangisidir?','Mitokondri','Ribozom','Hücre zarı','Çekirdek','Hücre duvarı','e','Hücre duvarı yalnızca bitki hücrelerinde bulunur'],
        ]);

        $id = $addQuiz(5, 'AYT Biyoloji – Genetik ve Evrim', 'Kalıtım, DNA ve evrim soruları', 'zor', 2000);
        $addQs($id, [
            ['Mendel\'in 1. Yasası nedir?','Bağımsız dağılım','Çaprazlama prensibi','Ayrılma (Segregasyon) Yasası','Kalıtım yasası','Gen akışı','c','Alel genler üreme hücresi oluşumunda birbirinden ayrılır'],
            ['DNA\'da Adenin hangi bazla çiftleşir?','Sitozin','Guanin','Urasil','Timin','Adenin','d','DNA: A-T ve G-C çiftleri oluşur'],
            ['Genotip nedir?','Organizmanın görünümü','Canlının ortamı','Organizmanın genetik yapısı','Fenotip değişimi','Çevre etkisi','c','Genotip: organizmanın alel kombinasyonu'],
            ['Darwin\'e göre hayatta kalanlar kimlerdir?','En büyükler','En hızlılar','En zekiler','Ortama en iyi uyum sağlayanlar','En güçlüler','d','Doğal seçilim: uyumlu bireyler hayatta kalır ve ürer'],
            ['Mutasyon nedir?','Çevre değişimi','Protein katlanması','DNA dizisindeki kalıcı değişim','Hücre bölünmesi','Gen ifadesi','c','Mutasyon DNA dizisinde kalıcı değişikliğe yol açar'],
            ['Mayoz bölünme neden önemlidir?','Hücre onarımı','Büyüme','Üreme hücrelerinde genetik çeşitlilik','ATP üretimi','Protein sentezi','c','Mayoz: haploid üreme hücreleri + genetik çeşitlilik'],
            ['RNA\'da Timin yerine hangi baz bulunur?','Adenin','Sitozin','Guanin','Urasil','Timin','d','RNA\'da Urasil (U) bulunur'],
            ['Hangi kan grubu evrensel alıcıdır?','A','B','O','O negatif','AB','e','AB: hem A hem B antijenine sahip → evrensel alıcı'],
            ['Evrimi hızlandıran faktör hangisidir?','Büyük popülasyon','İzolasyon ve doğal seçilim','Sabit çevre','Düşük mutasyon','Göçsüz topluluk','b','Coğrafi izolasyon + doğal seçilim evrimi hızlandırır'],
            ['Homeostazi nedir?','Hücre bölünmesi','Evrimsel değişim','Genetik aktarım','Organizmanın iç ortamını dengede tutması','Sindirim süreci','d','Homeostazi: iç ortamın (sıcaklık, pH vb.) sabit tutulmasıdır'],
        ]);

        // ═══════════════════════════════════════════
        // 6. TARİH
        // ═══════════════════════════════════════════
        $id = $addQuiz(6, 'TYT Tarih – Osmanlı ve Cumhuriyet', 'Osmanlı devleti ve Türkiye Cumhuriyeti tarihi', 'orta', 1500);
        $addQs($id, [
            ['Osmanlı Devleti hangi yılda kurulmuştur?','1071','1243','1453','1071','1299','e','Osman Bey tarafından 1299\'da kurulmuştur'],
            ['İstanbul\'un fethinde Osmanlı hükümdarı kimdi?','I. Murat','Yıldırım Bayezid','I. Süleyman','II. Murat','II. Mehmed (Fatih)','e','İstanbul 1453\'te Fatih Sultan Mehmed tarafından fethedildi'],
            ['Kurtuluş Savaşı ne zaman başladı?','1919','1921','1923','1918','1920','a','19 Mayıs 1919 - Samsun\'a çıkış'],
            ['Türkiye Cumhuriyeti hangi yılda ilan edildi?','1920','1921','1924','1922','1923','e','29 Ekim 1923'],
            ['Lozan Antlaşması hangi yılda imzalandı?','1921','1922','1924','1920','1923','e','24 Temmuz 1923'],
            ['Tanzimat Fermanı hangi yılda ilan edildi?','1856','1839','1876','1908','1829','b','1839 - Sultan Abdülmecid dönemi'],
            ['Soyadı Kanunu hangi yılda çıktı?','1931','1932','1935','1938','1934','e','1934 - Atatürk "Atatürk" soyadını aldı'],
            ['I. Dünya Savaşı hangi yılda sona erdi?','1916','1917','1919','1920','1918','e','11 Kasım 1918 - Almanya\'nın teslimiyeti'],
            ['Osmanlı\'da ilk anayasa hangi yılda ilan edildi?','1839','1856','1878','1908','1876','e','Kanun-u Esasi 1876 - I. Meşrutiyet'],
            ['Atatürk kaç yıl cumhurbaşkanlığı yaptı?','10','12','14','16','15','e','1923-1938: 15 yıl cumhurbaşkanlığı yaptı'],
        ]);

        $id = $addQuiz(6, 'AYT Tarih – Dünya ve Yakın Çağ Tarihi', 'Fransız Devrimi, Dünya Savaşları ve 20. yüzyıl', 'zor', 2000);
        $addQs($id, [
            ['Fransız Devrimi hangi yılda başladı?','1776','1848','1815','1803','1789','e','Fransız Devrimi 1789\'da başladı'],
            ['II. Dünya Savaşı hangi ülkenin Polonya\'yı işgaliyle başladı?','İtalya','Japonya','Sovyetler','Avusturya','Almanya','e','Almanya 1 Eylül 1939\'da Polonya\'yı işgal etti'],
            ['Sovyetler Birliği hangi yılda dağıldı?','1985','1989','1993','1990','1991','e','SSCB 25 Aralık 1991\'de dağıldı'],
            ['Magna Carta hangi yılda imzalandı?','1215','1415','1066','1315','1115','a','Magna Carta 1215\'te imzalandı'],
            ['I. Dünya Savaşı\'nın fitilini ateşleyen olay hangisidir?','Almanya\'nın Fransa\'ya saldırısı','Rusya\'nın seferberliği','Arşidük Franz Ferdinand suikastı','Japonya\'nın savaşa girmesi','İtalya\'nın taraf değiştirmesi','c','Haziran 1914 - Saraybosna suikastı'],
            ['Sanayi Devrimi ilk hangi ülkede başladı?','Fransa','Almanya','Amerika','Rusya','İngiltere','e','18. yüzyıl sonunda İngiltere\'de başladı'],
            ['Atom bombası hangi ülkeye atıldı?','Almanya','Çin','Kore','Vietnam','Japonya','e','1945 - Hiroşima ve Nagazaki'],
            ['Soğuk Savaş hangi iki güç arasındaydı?','İngiltere – Fransa','Çin – ABD','Almanya – Japonya','ABD – SSCB','Türkiye – Yunanistan','d','1947–1991 ABD ve SSCB arasında'],
            ['Osmanlı I. Dünya Savaşı\'nda hangi blokta savaştı?','İtilaf Devletleri','Bağlantısız','Müttefik Devletler','Mihver Devletleri','İttifak Devletleri','e','Osmanlı, Almanya ve Avusturya ile İttifak bloğunda'],
            ['Batı Roma İmparatorluğu hangi yılda yıkıldı?','395','410','455','480','476','e','Batı Roma 476\'da yıkıldı'],
        ]);

        // ═══════════════════════════════════════════
        // 7. COĞRAFYA
        // ═══════════════════════════════════════════
        $id = $addQuiz(7, 'TYT Coğrafya – Türkiye Coğrafyası', 'İklim, dağlar, nehirler ve nüfus soruları', 'orta', 1500);
        $addQs($id, [
            ['Türkiye\'nin en uzun nehri hangisidir?','Sakarya','Yeşilırmak','Fırat','Seyhan','Kızılırmak','e','Kızılırmak 1355 km ile en uzun nehirdir'],
            ['Türkiye\'nin en yüksek dağı hangisidir?','Erciyes','Nemrut','Uludağ','Kaçkar','Ağrı','e','Ağrı Dağı 5137 m'],
            ['Türkiye\'nin en büyük gölü hangisidir?','Eğirdir','Burdur','Tuz','Beyşehir','Van','e','Van Gölü 3713 km²'],
            ['En fazla yağış alan Türkiye bölgesi hangisidir?','Doğu Anadolu','İç Anadolu','Ege','Akdeniz','Karadeniz (Rize)','e','Rize en fazla yağış alan ilimizdir'],
            ['Türkiye\'nin başkenti neresidir?','İstanbul','İzmir','Bursa','Konya','Ankara','e','Ankara Türkiye\'nin başkentidir'],
            ['Türkiye kaç ülkeyle sınır paylaşır?','5','6','7','8','9','d','Yunanistan, Bulgaristan, Gürcistan, Ermenistan, Azerbaycan (Nahçıvan), İran, Irak, Suriye = 8 ülke'],
            ['Türkiye\'nin en kalabalık şehri hangisidir?','Ankara','Bursa','İzmir','Antalya','İstanbul','e','İstanbul 15 milyonu aşan nüfusuyla en büyük şehirdir'],
            ['Çukurova hangi ilde yer alır?','Hatay','Antalya','Konya','Kayseri','Adana','e','Çukurova verimli ovası Adana\'dadır'],
            ['Türkiye güney batısında hangi deniz yer alır?','Karadeniz','Marmara','Ege','Akdeniz','Hazar','d','Güneybatıda Akdeniz bulunur'],
            ['Türkiye hangi yarımkürelerde yer alır?','Güney-Batı','Güney-Doğu','Kuzey-Doğu','Kuzey-Batı','Yalnızca Kuzey','d','Kuzey ve Doğu yarımküre'],
            ['Türkiye\'nin en geniş ovası hangisidir?','Ergene Ovası','Gediz Ovası','Çukurova','Harran Ovası','Konya Ovası','e','Konya Ovası Türkiye\'nin en geniş ovasıdır'],
            ['Karadeniz ikliminin temel özelliği nedir?','Yazları sıcak kışları soğuk','Dört mevsim kurak','Yazları kurak kışları yağışlı','Her mevsim yağışlı','Karasal','d','Karadeniz ikliminde her mevsim yağış düzenli ve bol'],
        ]);

        $id = $addQuiz(7, 'AYT Coğrafya – Dünya Coğrafyası', 'Kıtalar, iklim kuşakları ve dünya nüfusu', 'zor', 2000);
        $addQs($id, [
            ['Dünyanın en uzun nehri hangisidir?','Amazon','Ganj','Mississippi','Yangtze','Nil','e','Nil ≈ 6650 km'],
            ['Dünyanın en büyük okyanusu hangisidir?','Atlas','Hint','Arktik','Antarktika','Pasifik','e','Pasifik en büyük okyanustur'],
            ['Ekvatoral iklimin temel özelliği nedir?','Sıcak ve kuru','Dört mevsim belirgin','Soğuk ve nemli','Yazları serin kışları ılık','Yıl boyu sıcak ve yağışlı','e','Ekvatoral: yüksek sıcaklık + yıl boyu yağış'],
            ['Dünyanın en büyük çölü hangisidir (sıcak)?','Gobi','Arabistan','Atacama','Antarktika','Sahra','e','Sıcak çöl olarak Sahra en büyüktür'],
            ['Muson yağmurları en yoğun hangi kıtayı etkiler?','Afrika','Avustralya','Avrupa','Amerika','Asya','e','Güney ve Güneydoğu Asya en çok etkilenir'],
            ['Nüfus yoğunluğu en yüksek kıta hangisidir?','Afrika','Kuzey Amerika','Avustralya','Avrupa','Asya','e','Asya dünya nüfusunun ~%60\'ını barındırır'],
            ['El Niño hangi olayı tanımlar?','Volkanik faaliyet','Pasifik\'te periyodik deniz suyu ısınması','Atlantik kasırgaları','Kutup soğuması','Amazon selleri','b','El Niño: Pasifik yüzey sularının anormal ısınması'],
            ['Tropikal yağmur ormanları için doğru olan hangisidir?','Düşük biyoçeşitlilik','Az yağış','Yalnızca Afrika\'da','Sezonluk iklim','Yüksek biyoçeşitlilik ve sürekli yağış','e','Tropikal yağmur ormanları en yüksek biyoçeşitliğe sahiptir'],
            ['Kutup bölgelerinin iklim tipi hangisidir?','Çöl','Muson','Akdeniz','Tundra ve kutup iklimi','Savan','d','Kutup bölgeleri tundra ve kutup (buz) iklimine sahiptir'],
            ['Dünyanın en yüksek dağı hangisidir?','K2','Kangchenjunga','Makalu','Lhotse','Everest','e','Everest 8849 m ile dünyanın en yüksek noktasıdır'],
        ]);

        // ═══════════════════════════════════════════
        // 8. FELSEFE
        // ═══════════════════════════════════════════
        $id = $addQuiz(8, 'TYT Felsefe – Temel Kavramlar', 'Felsefenin temel soruları, filozoflar ve akımlar', 'orta', 1500);
        $addQs($id, [
            ['"Kendini bil" özdeyişiyle tanınan antik Yunan filozofu kimdir?','Platon','Aristoteles','Epiktetos','Thales','Sokrates','e','"Gnothi seauton" - Sokrates\'in temel öğretisidir'],
            ['Epistemoloji nedir?','Varlık felsefesi','Ahlak felsefesi','Bilgi felsefesi','Sanat felsefesi','Siyaset felsefesi','c','Epistemoloji bilginin kaynağı ve sınırlarını inceler'],
            ['"Cogito ergo sum" (Düşünüyorum öyleyse varım) kime aittir?','Kant','Locke','Hume','Spinoza','Descartes','e','René Descartes bu önermeyle kendi varlığını ispat etti'],
            ['Etik neyi inceler?','Güzelliği','Siyasal iktidarı','Doğru ve yanlış davranışları','Mantık yasalarını','Varlığın temelini','c','Etik: ahlaki değerleri ve eylemleri inceleyen felsefe dalı'],
            ['Platon\'un meşhur alegorisi hangisidir?','Achilles ve Kaplumbağa','Theseus\'un gemisi','Saçmal Kahraman','Mağara alegorisi','Güneş alegorisi','d','Mağara Alegorisi gerçeklik ve algı üzerine bir düşünce deneyidir'],
            ['Empirizm nedir?','Bilgi doğuştandır','Tüm bilgi deneyden kaynaklanır','Akıl tek bilgi kaynağıdır','Anlam yoktur','Tanrı merkezli düşünce','b','Empirizm: bilginin kaynağı deneydir (Locke, Hume)'],
            ['Rasyonalizm nedir?','Bilgi duyumdan gelir','Bilgi çevreseldir','Bilgi deney ve akıldan üretilir','Anlam yoktur','Aklın temel bilgi kaynağı olduğu görüşü','e','Rasyonalizm: akıl bilginin birincil kaynağıdır (Descartes, Spinoza)'],
            ['"Varoluş özden önce gelir" hangi akıma aittir?','Realizm','Nihilizm','Rasyonalizm','Empirizm','Varoluşçuluk','e','Sartre\'ın bu sözü varoluşçu (egzistansiyalist) felsefenin temelidir'],
            ['Kant\'ın "kategorik imperatif" ilkesi ne anlama gelir?','Çoğunluğun iyiliğini maksimize et','Tanrının buyruklarına uy','Öyle davran ki ilken evrensel yasa olabilsin','Acıdan kaç','Sezgiyi takip et','c','Kant: eylemin ilkesi evrensel bir yasa olabilmeli'],
            ['Ontoloji nedir?','Bilgi felsefesi','Ahlak felsefesi','Estetik','Varlık felsefesi','Siyaset felsefesi','d','Ontoloji: varlık, varoluş ve gerçekliğin doğasını inceler'],
            ['Stoacılık hangi temel görüşü savunur?','Zevki maksimize et','Hiçbir şey bilinmez','Akıl ve erdemle iç huzura ulaşılır','Tanrı her şeyin merkezidir','Deneyim her şeydir','c','Stoacılar: aklı ve erdemi ön planda tutar, dış olaylara önem vermez'],
            ['Ahlak felsefesinde faydacılık (utilitarizm) nedir?','Tanrıya itaat','En çok kişiye en çok mutluluğu sağlamak','Ödev ahlakı','Erdem etiği','Sözleşme ahlakı','b','Bentham ve Mill: en büyük mutluluk ilkesi'],
        ]);

    } // end seed()
}
