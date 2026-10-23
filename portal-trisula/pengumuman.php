<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/siswa_layout.php';
require __DIR__ . '/includes/ekskul.php';

$session = require_role('siswa');
$pdo     = db();
$user    = load_user($pdo, (int) $session['id']);
$uid     = (int) $user['id'];

// ===== Kirim komentar =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int) ($_POST['pengumuman_id'] ?? 0);
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            throw new RuntimeException('Sesi tidak valid, muat ulang halaman.');
        }
        $isi = trim((string) ($_POST['isi'] ?? ''));
        if ($isi === '')              throw new RuntimeException('Komentar tidak boleh kosong.');
        if (mb_strlen($isi) > 500)    throw new RuntimeException('Komentar maksimal 500 karakter.');

        // Pengumuman harus milik ekskul yang sudah disetujui untuk siswa ini
        $st = $pdo->prepare("SELECT a.id
                             FROM info_pengumuman a
                             JOIN pendaftaran p ON p.ekskul_id = a.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                             WHERE a.id = ?");
        $st->execute([$uid, $pid]);
        if (!$st->fetch()) throw new RuntimeException('Pengumuman tidak ditemukan.');

        $pdo->prepare("INSERT INTO info_komentar (pengumuman_id, user_id, nama, peran, isi) VALUES (?,?,?,'siswa',?)")
            ->execute([$pid, $uid, (string) $user['nama'], $isi]);
        $_SESSION['flash'] = ['ok', 'Komentar terkirim.'];
    } catch (RuntimeException $ex) {
        $_SESSION['flash'] = ['err', $ex->getMessage()];
    } catch (PDOException $ex) {
        $_SESSION['flash'] = ['err', 'Gagal menyimpan komentar, coba lagi.'];
    }
    header('Location: pengumuman.php?buka=' . $pid . '#p-' . $pid);
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$buka = (int) ($_GET['buka'] ?? 0);

// ===== Daftar pengumuman dari ekskul yang diikuti =====
$st = $pdo->prepare("SELECT a.id, a.judul, a.isi, a.created_at,
                            COALESCE(NULLIF(a.penulis, ''), e.pembimbing) AS penulis,
                            e.nama AS ekskul
                     FROM info_pengumuman a
                     JOIN ekskul e ON e.id = a.ekskul_id
                     JOIN pendaftaran p ON p.ekskul_id = a.ekskul_id AND p.siswa_id = ? AND p.status = 'disetujui'
                     ORDER BY a.created_at DESC, a.id DESC");
$st->execute([$uid]);
$daftar = $st->fetchAll();

// ===== Komentar (satu query untuk semua pengumuman) =====
$komen = [];
if ($daftar) {
    $ids = array_map(static fn($r) => (int) $r['id'], $daftar);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = $pdo->prepare("SELECT pengumuman_id, nama, peran, isi, created_at
                          FROM info_komentar WHERE pengumuman_id IN ($in)
                          ORDER BY created_at, id");
    $st->execute($ids);
    foreach ($st->fetchAll() as $c) {
        $komen[(int) $c['pengumuman_id']][] = $c;
    }
}

siswa_header('Pengumuman', 'pengumuman', $user);
?>
<div class="page-title"><h2>Pengumuman</h2><p><?= count($daftar) ?> pengumuman tersedia</p></div>

<?php if ($flash): ?>
    <div class="flash <?= $flash[0] === 'ok' ? 'flash-ok' : 'flash-err' ?>" role="status"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php foreach ($daftar as $a):
    $id       = (int) $a['id'];
    $penulis  = $a['penulis'] ?: 'Pembimbing';
    $cm       = $komen[$id] ?? [];
    $terbuka  = ($buka === $id);
    $tag      = explode(' ', trim((string) $a['ekskul']))[0];
?>
    <article class="pg-card" id="p-<?= $id ?>">
        <div class="pg-main">
            <span class="avatar" style="background:<?= e(avatar_color($penulis)) ?>"><?= e(initials($penulis)) ?></span>
            <div class="pg-body">
                <div class="pg-meta">
                    <b><?= e($penulis) ?></b>
                    <span>· <?= e(time_ago($a['created_at'])) ?></span>
                    <em class="pg-tag"><?= e($tag) ?></em>
                </div>
                <h3><?= e($a['judul']) ?></h3>
                <p><?= nl2br(e($a['isi'])) ?></p>
                <div class="pg-foot">
                    <span class="pg-count">💬 <?= count($cm) ?> komentar</span>
                    <button type="button" class="pg-toggle"><?= $terbuka ? '▲ Tutup' : '▼ Lihat komentar' ?></button>
                </div>
            </div>
        </div>

        <div class="pg-comments"<?= $terbuka ? '' : ' hidden' ?>>
            <?php foreach ($cm as $c): ?>
                <div class="pg-c">
                    <span class="avatar avatar--sm" style="background:<?= $c['peran'] === 'pembimbing' ? e(avatar_color((string) $c['nama'])) : '#004029' ?>"><?= e(initials((string) $c['nama'])) ?></span>
                    <div class="pg-bubble">
                        <b><?= e($c['nama']) ?></b> <small>· <?= e(time_ago($c['created_at'])) ?></small>
                        <p><?= nl2br(e($c['isi'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <form method="post" class="pg-form">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="pengumuman_id" value="<?= $id ?>">
                <input type="text" name="isi" maxlength="500" placeholder="Tulis komentar..." required autocomplete="off">
                <button type="submit">Kirim</button>
            </form>
        </div>
    </article>
<?php endforeach; ?>

<?php if (!$daftar): ?>
    <section class="panel"><p class="empty">Belum ada pengumuman. Pengumuman muncul setelah pendaftaran ekskul Anda disetujui pembimbing.</p></section>
<?php endif; ?>

<script>
document.querySelectorAll('.pg-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var box  = btn.closest('.pg-card').querySelector('.pg-comments');
        var show = box.hasAttribute('hidden');
        if (show) { box.removeAttribute('hidden'); } else { box.setAttribute('hidden', ''); }
        btn.textContent = show ? '▲ Tutup' : '▼ Lihat komentar';
    });
});
</script>
<?php siswa_footer();
