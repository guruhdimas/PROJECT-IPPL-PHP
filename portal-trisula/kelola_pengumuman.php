<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/ekskul.php';
require __DIR__ . '/includes/pembimbing_layout.php';

$session = require_role('pembimbing');
$pdo     = db();
$me      = pembimbing_user($pdo, (int) $session['id']);
$pid     = (int) $me['id'];

function pg_url(array $p = []): string
{
    $q = http_build_query(array_filter($p, static fn ($v) => $v !== '' && $v !== null && $v !== 0));
    return 'kelola_pengumuman.php' . ($q !== '' ? '?' . $q : '');
}

function pg_waktu(string $dt): string
{
    return tanggal_id(substr($dt, 0, 10)) . ', ' . date('H.i', strtotime($dt));
}

// Ekskul yang diampu pembimbing ini
$st = $pdo->prepare('SELECT id, nama FROM ekskul WHERE pembimbing_id = ? ORDER BY nama');
$st->execute([$pid]);
$ekskulku = $st->fetchAll();
$idEkskul = array_map(static fn ($r) => (int) $r['id'], $ekskulku);

// Pastikan pengumuman milik ekskul yang diampu; kembalikan barisnya atau null
function pg_milik(PDO $pdo, int $id, int $pid): ?array
{
    $st = $pdo->prepare('SELECT a.id FROM info_pengumuman a JOIN ekskul e ON e.id = a.ekskul_id
                         WHERE a.id = ? AND e.pembimbing_id = ?');
    $st->execute([$id, $pid]);
    return $st->fetch() ?: null;
}

// ===================== AKSI (POST) =====================
$errBuat   = [];
$in        = ['tujuan' => 'semua', 'judul' => '', 'isi' => ''];
$bukaBuat  = isset($_GET['buat']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'buat') {
        $bukaBuat = true;
        $in = [
            'tujuan' => (string) ($_POST['tujuan'] ?? ''),
            'judul'  => trim((string) ($_POST['judul'] ?? '')),
            'isi'    => trim((string) ($_POST['isi'] ?? '')),
        ];
        if (!csrf_valid($_POST['csrf'] ?? null)) $errBuat[] = 'Sesi tidak valid, muat ulang halaman.';
        $target = [];
        if ($in['tujuan'] === 'semua') {
            $target = $idEkskul;
        } elseif (in_array((int) $in['tujuan'], $idEkskul, true)) {
            $target = [(int) $in['tujuan']];
        }
        if (!$target) $errBuat[] = 'Pilih tujuan pengumuman.';
        if (mb_strlen($in['judul']) < 3 || mb_strlen($in['judul']) > 150) $errBuat[] = 'Judul harus 3–150 karakter.';
        if (mb_strlen($in['isi']) < 3 || mb_strlen($in['isi']) > 2000)    $errBuat[] = 'Isi pengumuman harus 3–2000 karakter.';

        if (!$errBuat) {
            try {
                $pdo->beginTransaction();
                $ins = $pdo->prepare('INSERT INTO info_pengumuman (ekskul_id, penulis, judul, isi) VALUES (?,?,?,?)');
                foreach ($target as $eid) {
                    $ins->execute([$eid, (string) $me['nama'], $in['judul'], $in['isi']]);
                }
                $pdo->commit();
                $n = count($target);
                $_SESSION['flash'] = ['ok', $n > 1 ? "Pengumuman dikirim ke $n ekskul." : 'Pengumuman berhasil dikirim.'];
                header('Location: ' . pg_url());
                exit;
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errBuat[] = 'Gagal menyimpan pengumuman. Pastikan database/pengumuman.sql sudah dijalankan.';
            }
        }
    } elseif ($aksi === 'komentar') {
        $aid = (int) ($_POST['pengumuman_id'] ?? 0);
        try {
            if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
            $isi = trim((string) ($_POST['isi'] ?? ''));
            if ($isi === '')           throw new RuntimeException('Komentar tidak boleh kosong.');
            if (mb_strlen($isi) > 500) throw new RuntimeException('Komentar maksimal 500 karakter.');
            if (!pg_milik($pdo, $aid, $pid)) throw new RuntimeException('Pengumuman tidak ditemukan.');
            $pdo->prepare("INSERT INTO info_komentar (pengumuman_id, user_id, nama, peran, isi) VALUES (?,?,?,'pembimbing',?)")
                ->execute([$aid, $pid, (string) $me['nama'], $isi]);
            $_SESSION['flash'] = ['ok', 'Komentar terkirim.'];
        } catch (RuntimeException $ex) {
            $_SESSION['flash'] = ['err', $ex->getMessage()];
        } catch (PDOException $ex) {
            $_SESSION['flash'] = ['err', 'Gagal menyimpan komentar, coba lagi.'];
        }
        header('Location: ' . pg_url(['lihat' => $aid]));
        exit;
    } elseif ($aksi === 'hapus') {
        try {
            if (!csrf_valid($_POST['csrf'] ?? null)) throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
            $aid = (int) ($_POST['pengumuman_id'] ?? 0);
            if (!pg_milik($pdo, $aid, $pid)) throw new RuntimeException('Pengumuman tidak ditemukan.');
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM info_komentar WHERE pengumuman_id = ?')->execute([$aid]);
            $pdo->prepare('DELETE FROM info_pengumuman WHERE id = ?')->execute([$aid]);
            $pdo->commit();
            $_SESSION['flash'] = ['ok', 'Pengumuman dihapus.'];
        } catch (RuntimeException $ex) {
            $_SESSION['flash'] = ['err', $ex->getMessage()];
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = ['err', 'Gagal menghapus pengumuman, coba lagi.'];
        }
        header('Location: ' . pg_url());
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ===================== DATA TAMPILAN =====================
$st = $pdo->prepare('SELECT a.id, a.judul, a.isi, a.penulis, a.created_at, e.nama AS ekskul,
                            (SELECT COUNT(*) FROM info_komentar k WHERE k.pengumuman_id = a.id) AS kom
                     FROM info_pengumuman a JOIN ekskul e ON e.id = a.ekskul_id
                     WHERE e.pembimbing_id = ?
                     ORDER BY a.created_at DESC, a.id DESC LIMIT 100');
$st->execute([$pid]);
$daftar = $st->fetchAll();

$lihat = (int) ($_GET['lihat'] ?? 0);
$detail = null;
foreach ($daftar as $r) { if ((int) $r['id'] === $lihat) { $detail = $r; break; } }
$komen = [];
if ($detail) {
    $st = $pdo->prepare('SELECT nama, peran, isi, created_at FROM info_komentar WHERE pengumuman_id = ? ORDER BY created_at, id');
    $st->execute([$detail['id']]);
    $komen = $st->fetchAll();
}
$batasBaru = strtotime('-3 days');

pembimbing_header('Pengumuman', 'pengumuman', $me);

if ($flash): ?>
    <div class="pb-flash <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<section class="pb-panel">
    <div class="pb-pg-head">
        <div><h2>Pengumuman</h2><p>Buat pengumuman dan balas komentar siswa</p></div>
        <?php if ($ekskulku): ?><a class="pb-pg-add" href="<?= e(pg_url(['buat' => 1])) ?>">＋ Buat Pengumuman</a><?php endif; ?>
    </div>

    <div class="pb-pg-grid">
        <?php foreach ($daftar as $a): $baru = strtotime((string) $a['created_at']) >= $batasBaru; ?>
            <a class="pb-pg-card" href="<?= e(pg_url(['lihat' => $a['id']])) ?>">
                <?php if ($baru): ?><i class="pb-pg-new">Baru</i><?php endif; ?>
                <h3><?= e($a['judul']) ?></h3>
                <p class="pb-pg-prev"><?= e(mb_strimwidth((string) $a['isi'], 0, 110, '…')) ?></p>
                <dl>
                    <div><dt>Ekskul:</dt><dd><?= e($a['ekskul']) ?></dd></div>
                    <div><dt>Pembimbing:</dt><dd><?= e($a['penulis'] !== '' ? $a['penulis'] : $me['nama']) ?></dd></div>
                    <div><dt>Diterbitkan:</dt><dd><?= e(tanggal_id(substr((string) $a['created_at'], 0, 10))) ?></dd></div>
                    <div><dt>Komentar:</dt><dd><?= (int) $a['kom'] ?></dd></div>
                </dl>
            </a>
        <?php endforeach; ?>
        <?php if (!$daftar): ?>
            <p class="pb-empty"><?= $ekskulku ? 'Belum ada pengumuman. Klik “Buat Pengumuman” untuk membuat yang pertama.' : 'Anda belum mengampu ekskul apa pun.' ?></p>
        <?php endif; ?>
    </div>
</section>

<?php /* ---------- Modal: detail + komentar ---------- */
if ($detail): $tutupUrl = e(pg_url()); $baru = strtotime((string) $detail['created_at']) >= $batasBaru; ?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal--detail" role="dialog" aria-modal="true" aria-label="Detail pengumuman">
            <div class="pb-pg-mh">
                <div>
                    <?php if ($baru): ?><span class="pb-pg-chip">Pengumuman baru</span><?php endif; ?>
                    <h3><?= e($detail['judul']) ?></h3>
                </div>
                <a href="<?= $tutupUrl ?>" class="pb-pg-x" aria-label="Tutup">✕</a>
            </div>

            <div class="pb-pg-cols">
                <div class="pb-pg-left">
                    <p class="pb-pg-isi"><?= nl2br(e((string) $detail['isi'])) ?></p>
                    <div class="pb-pg-info">
                        <div><small>Pembimbing</small><b><?= e($detail['penulis'] !== '' ? $detail['penulis'] : $me['nama']) ?></b></div>
                        <div><small>Ekskul</small><b><?= e($detail['ekskul']) ?></b></div>
                        <div><small>Diterbitkan</small><b><?= e(tanggal_id(substr((string) $detail['created_at'], 0, 10))) ?></b></div>
                        <div><small>Pukul</small><b><?= e(date('H.i', strtotime((string) $detail['created_at']))) ?> WIB</b></div>
                    </div>
                    <form method="post" action="<?= e(pg_url()) ?>" onsubmit="return confirm('Hapus pengumuman ini beserta semua komentarnya?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="pengumuman_id" value="<?= (int) $detail['id'] ?>">
                        <button type="submit" class="pb-pg-del">Hapus pengumuman</button>
                    </form>
                </div>

                <div class="pb-pg-right">
                    <div class="pb-pg-ch"><h4>Komentar</h4><span><?= count($komen) ?> komentar</span></div>
                    <div class="pb-pg-list">
                        <?php foreach ($komen as $k): ?>
                            <div class="pb-pg-c<?= $k['peran'] === 'pembimbing' ? ' me' : '' ?>">
                                <div><b><?= e($k['nama']) ?></b><small><?= e(pg_waktu((string) $k['created_at'])) ?></small></div>
                                <p><?= nl2br(e((string) $k['isi'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$komen): ?><p class="pb-pg-none">Belum ada komentar.</p><?php endif; ?>
                    </div>

                    <form method="post" action="<?= e(pg_url(['lihat' => $detail['id']])) ?>" class="pb-pg-form">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="aksi" value="komentar">
                        <input type="hidden" name="pengumuman_id" value="<?= (int) $detail['id'] ?>">
                        <label for="pgk">Tambahkan komentar</label>
                        <textarea id="pgk" name="isi" rows="3" maxlength="500" placeholder="Tulis pertanyaan atau komentar..." required></textarea>
                        <button type="submit" class="pb-save">Kirim Komentar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php /* ---------- Modal: buat pengumuman ---------- */
if ($bukaBuat && $ekskulku): $tutupUrl = e(pg_url()); ?>
    <div class="pb-modal-back">
        <div class="pb-modal pb-modal--form" role="dialog" aria-modal="true" aria-label="Buat Pengumuman">
            <form method="post" action="<?= e(pg_url()) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="aksi" value="buat">
                <header><h3>Buat Pengumuman</h3><a href="<?= $tutupUrl ?>" aria-label="Tutup">✕</a></header>
                <div class="pb-modal-body">
                    <?php if ($errBuat): ?>
                        <div class="pb-flash err" role="alert"><?php foreach ($errBuat as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
                    <?php endif; ?>
                    <label class="pb-fl"><span>Tujuan</span>
                        <select name="tujuan" required>
                            <option value="semua" <?= $in['tujuan'] === 'semua' ? 'selected' : '' ?>>Semua Ekskul</option>
                            <?php foreach ($ekskulku as $ek): ?>
                                <option value="<?= (int) $ek['id'] ?>" <?= $in['tujuan'] === (string) $ek['id'] ? 'selected' : '' ?>><?= e($ek['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="pb-fl"><span>Judul</span><input type="text" name="judul" value="<?= e($in['judul']) ?>" maxlength="150" required></label>
                    <label class="pb-fl"><span>Isi</span><textarea name="isi" rows="5" maxlength="2000" required><?= e($in['isi']) ?></textarea></label>
                </div>
                <footer>
                    <a class="pb-close" href="<?= $tutupUrl ?>">Batal</a>
                    <button type="submit" class="pb-save">Kirim</button>
                </footer>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php pembimbing_footer();
