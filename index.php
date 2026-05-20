<?php
session_start();

// Conf
$access_password = getenv('ACCESS_PASSWORD') ?: 'changeme';
$merchant_id     = getenv('PAYTR_MERCHANT_ID');
$merchant_key    = getenv('PAYTR_MERCHANT_KEY');
$merchant_salt   = getenv('PAYTR_MERCHANT_SALT');
$test_mode       = getenv('PAYTR_TEST_MODE') ?: '1';
$mock_mode       = getenv('MOCK_MODE') === '1';

$is_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_password'])) {
    if ($_POST['access_password'] === $access_password) {
        $_SESSION['authenticated'] = true;
        $is_authenticated = true;
    } else {
        $login_error = 'Yanlış şifre.';
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

$error_msg = '';
$token = '';

if ($is_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_amount'])) {

    $amount_tl      = floatval($_POST['payment_amount']);
    $customer_name  = htmlspecialchars(trim($_POST['customer_name'] ?? 'Misafir'));
    $customer_email = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $customer_phone = preg_replace('/[^0-9]/', '', $_POST['customer_phone'] ?? '');
    $description    = htmlspecialchars(trim($_POST['description'] ?? 'Ödeme'));

    if (!$customer_email) {
        $error_msg = 'Geçerli bir e-posta adresi giriniz.';
    } elseif ($amount_tl < 1) {
        $error_msg = 'Tutar en az 1 TL olmalıdır.';
    } elseif ($mock_mode) {
        // ── MOCK MODE: skip PayTR, show fake iframe ──────────
        $token        = 'MOCK';
        $merchant_oid = 'PG' . time() . rand(1000, 9999);
        $payment_amount = intval($amount_tl * 100);
        $_SESSION['mock_oid']    = $merchant_oid;
        $_SESSION['mock_amount'] = $payment_amount;
    } elseif (!$merchant_id || !$merchant_key || !$merchant_salt) {
        $error_msg = 'PayTR bilgileri eksik. Lütfen environment variable\'ları kontrol edin.';
    } else {
        $payment_amount = intval($amount_tl * 100);
        $merchant_oid   = 'PG' . time() . rand(1000, 9999);

        $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $user_ip = trim(explode(',', $user_ip)[0]);

        $user_basket = base64_encode(json_encode([
            [$description, $payment_amount, 1]
        ]));

        $no_installment  = 1;
        $max_installment = 0;
        $currency        = 'TL';

        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                    . '://' . $_SERVER['HTTP_HOST'];
        $merchant_ok_url   = $base_url . '/success.php';
        $merchant_fail_url = $base_url . '/fail.php';

        $hash_str = $merchant_id . $user_ip . $merchant_oid . $customer_email . $payment_amount
                   . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
        $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

        $post_vals = [
            'merchant_id'      => $merchant_id,
            'user_ip'          => $user_ip,
            'merchant_oid'     => $merchant_oid,
            'email'            => $customer_email,
            'payment_amount'   => $payment_amount,
            'paytr_token'      => $paytr_token,
            'user_basket'      => $user_basket,
            'debug_on'         => ($test_mode === '1') ? 1 : 0,
            'no_installment'   => $no_installment,
            'max_installment'  => $max_installment,
            'currency'         => $currency,
            'test_mode'        => $test_mode,
            'merchant_ok_url'  => $merchant_ok_url,
            'merchant_fail_url'=> $merchant_fail_url,
            'user_name'        => $customer_name,
            'user_address'     => 'Türkiye',
            'user_phone'       => $customer_phone ?: '05000000000',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.paytr.com/odeme/api/get-token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = 'Bağlantı hatası: ' . curl_error($ch);
        } else {
            $res = json_decode($result, true);
            if ($res && isset($res['status']) && $res['status'] === 'success') {
                $token = $res['token'];
            } else {
                $error_msg = 'PayTR Hata: ' . ($res['reason'] ?? 'Bilinmeyen hata');
            }
        }
        curl_close($ch);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0b;
            --surface: #141416;
            --surface-2: #1c1c1f;
            --border: #2a2a2d;
            --text: #e4e4e7;
            --text-muted: #71717a;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --success: #22c55e;
            --error: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { width: 100%; max-width: 480px; padding: 2rem; }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
        .logo span { color: var(--accent); }
        .logo p { color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem; }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
        }
        .form-group { margin-bottom: 1.25rem; }
        label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9375rem;
            transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: var(--accent); }
        input::placeholder { color: var(--text-muted); }
        .amount-input {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            padding: 1.25rem;
            letter-spacing: -0.02em;
        }
        .currency-label {
            text-align: center;
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-top: 0.375rem;
        }
        button, .btn {
            width: 100%;
            padding: 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
            display: block;
            text-align: center;
            text-decoration: none;
        }
        button:hover, .btn:hover { background: var(--accent-hover); }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--error);
            padding: 0.875rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
        }
        .iframe-container { margin-top: 1.5rem; }
        .iframe-container iframe {
            width: 100%;
            min-height: 400px;
            border: none;
            border-radius: 10px;
        }
        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            margin-top: 1.25rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .secure-badge svg { width: 14px; height: 14px; }
        .divider { height: 1px; background: var(--border); margin: 1.5rem 0; }
        .logout {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--text-muted);
            font-size: 0.8125rem;
            text-decoration: none;
        }
        .logout:hover { color: var(--text); }
        .test-badge {
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.2);
            color: #eab308;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--accent);
            font-size: 0.875rem;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        .version-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border);
        }
        .version-tag {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: monospace;
            letter-spacing: 0.05em;
        }
        .version-mode {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
        }
        .version-mode.mock {
            background: rgba(234,179,8,0.1);
            color: #eab308;
            border: 1px solid rgba(234,179,8,0.2);
        }
        .version-mode.test {
            background: rgba(234,179,8,0.1);
            color: #eab308;
            border: 1px solid rgba(234,179,8,0.2);
        }
        .version-mode.live {
            background: rgba(34,197,94,0.1);
            color: #22c55e;
            border: 1px solid rgba(34,197,94,0.2);
        }
        @media (max-width: 520px) {
            .container { padding: 1rem; }
            .card { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1><span>●</span> Sanal POS</h1>
            <p>Güvenli ödeme sayfası</p>
        </div>

        <div class="card">

<?php if (!$is_authenticated): ?>
            <!-- ── Password Gate ── -->
            <?php if (isset($login_error)): ?>
                <div class="error-msg"><?php echo $login_error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Erişim Şifresi</label>
                    <input type="password" name="access_password" placeholder="Şifrenizi giriniz" required autofocus>
                </div>
                <button type="submit">Giriş Yap →</button>
            </form>

<?php elseif ($token): ?>
            <!-- ── Payment iFrame ── -->
            <a href="/" class="back-link">← Geri dön</a>

            <?php if ($token === 'MOCK'): ?>
            <!-- MOCK MODE UI -->
            <div style="background:rgba(234,179,8,0.08);border:1px solid rgba(234,179,8,0.25);border-radius:12px;padding:1.25rem;margin-bottom:1.5rem;text-align:center;">
                <div style="font-size:1.5rem;margin-bottom:0.5rem;">🧪</div>
                <div style="color:#eab308;font-weight:600;font-size:0.9rem;margin-bottom:0.25rem;">MOCK ÖDEME MODU</div>
                <div style="color:var(--text-muted);font-size:0.8rem;">Gerçek ödeme yapılmayacak. Sonucu seçin:</div>
            </div>
            <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:1.5rem;text-align:center;margin-bottom:1rem;">
                <div style="color:var(--text-muted);font-size:0.8rem;margin-bottom:0.25rem;">Sipariş No</div>
                <div style="font-weight:700;letter-spacing:0.05em;font-size:0.9rem;"><?php echo htmlspecialchars($_SESSION['mock_oid'] ?? ''); ?></div>
                <div style="margin-top:0.75rem;color:var(--text-muted);font-size:0.8rem;">Tutar</div>
                <div style="font-size:1.75rem;font-weight:700;">₺<?php echo number_format(($payment_amount ?? 0) / 100, 2); ?></div>
            </div>
            <form method="POST" action="/mock_payment.php">
                <input type="hidden" name="merchant_oid" value="<?php echo htmlspecialchars($_SESSION['mock_oid'] ?? ''); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($_SESSION['mock_amount'] ?? ''); ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <button type="submit" name="action" value="success"
                        style="background:#16a34a;padding:1rem;border-radius:10px;font-weight:600;font-size:0.95rem;cursor:pointer;border:none;color:white;">
                        ✓ Ödeme Başarılı
                    </button>
                    <button type="submit" name="action" value="fail"
                        style="background:#dc2626;padding:1rem;border-radius:10px;font-weight:600;font-size:0.95rem;cursor:pointer;border:none;color:white;">
                        ✗ Ödeme Başarısız
                    </button>
                </div>
            </form>

            <?php else: ?>
            <!-- REAL PayTR iFrame -->
            <div style="text-align:center; margin-bottom:1rem;">
                <p style="font-size:0.875rem; color:var(--text-muted);">Kart bilgilerinizi girerek ödemenizi tamamlayın</p>
            </div>
            <div class="iframe-container">
                <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                <iframe src="https://www.paytr.com/odeme/guvenli/<?php echo htmlspecialchars($token); ?>" id="paytriframe" frameborder="0" scrolling="no"></iframe>
                <script>iFrameResize({}, '#paytriframe');</script>
            </div>
            <?php endif; ?>

<?php else: ?>
            <!-- ── Payment Form ── -->
            <?php if ($test_mode === '1' && !$mock_mode): ?>
                <div class="test-badge">⚠ Test modu aktif — gerçek ödeme alınmaz</div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="error-msg"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Tutar</label>
                    <input type="number" name="payment_amount" class="amount-input"
                           placeholder="0.00" step="0.01" min="1" required
                           value="<?php echo $_POST['payment_amount'] ?? ''; ?>">
                    <div class="currency-label">Türk Lirası (₺)</div>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label>Ad Soyad</label>
                    <input type="text" name="customer_name" placeholder="Müşteri adı" required
                           value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>E-posta</label>
                    <input type="email" name="customer_email" placeholder="ornek@email.com" required
                           value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Telefon</label>
                    <input type="tel" name="customer_phone" placeholder="05XX XXX XX XX"
                           value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Açıklama</label>
                    <input type="text" name="description" placeholder="Ödeme açıklaması"
                           value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">
                </div>

                <button type="submit">Ödemeye Geç →</button>
            </form>
            <a href="/?logout" class="logout">Çıkış yap</a>

<?php endif; ?>

            <div class="secure-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                256-bit SSL ile korunan güvenli ödeme
            </div>

            <div class="version-bar">
                <span class="version-tag">v1.1.0</span>
                <?php if ($mock_mode): ?>
                    <span class="version-mode mock">🧪 mock</span>
                <?php elseif ($test_mode === '1'): ?>
                    <span class="version-mode test">⚠ test</span>
                <?php else: ?>
                    <span class="version-mode live">● live</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
