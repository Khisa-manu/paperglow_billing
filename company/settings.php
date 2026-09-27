<?php
/**
 * PaperGlow Billing System - Company Settings & Logo Upload
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$company = getCompanySettings($pdo, $userId);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $companyName = trim($_POST['company_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $taxNumber = trim($_POST['tax_number'] ?? '');
        $currency = trim($_POST['currency'] ?? 'KES');
        $bankName = trim($_POST['bank_name'] ?? '');
        $bankAccountName = trim($_POST['bank_account_name'] ?? '');
        $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
        $bankRouting = trim($_POST['bank_routing'] ?? '');
        $bankSwift = trim($_POST['bank_swift'] ?? '');
        $mobileMoneyName = trim($_POST['mobile_money_name'] ?? '');
        $mobileMoneyNumber = trim($_POST['mobile_money_number'] ?? '');
        $defaultInvoiceTerms = trim($_POST['default_invoice_terms'] ?? '');
        $defaultQuotationTerms = trim($_POST['default_quotation_terms'] ?? '');
        $removeLogo = !empty($_POST['remove_logo']);

        $logoPath = $company['logo'] ?? null;

        // Handle Logo Removal
        if ($removeLogo && !empty($logoPath)) {
            $fullOldPath = dirname(__DIR__) . '/' . ltrim($logoPath, '/');
            if (file_exists($fullOldPath) && is_file($fullOldPath)) {
                @unlink($fullOldPath);
            }
            $logoPath = null;
        }

        // Handle New Logo Upload
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['logo'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'File upload failed with error code: ' . $file['error'];
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = 'Logo file size exceeds the 2MB limit. Please compress or select a smaller image.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowedMimes = [
                    'image/jpeg'    => 'jpg',
                    'image/png'     => 'png',
                    'image/webp'    => 'webp',
                    'image/svg+xml' => 'svg',
                ];

                if (!array_key_exists($mime, $allowedMimes)) {
                    $error = 'Invalid image format. Allowed formats: PNG, JPG/JPEG, WebP, SVG.';
                } else {
                    $ext = $allowedMimes[$mime];
                    $uploadDir = dirname(__DIR__) . '/uploads/logos';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }

                    // Remove previous logo file if exists
                    if (!empty($logoPath)) {
                        $fullOld = dirname(__DIR__) . '/' . ltrim($logoPath, '/');
                        if (file_exists($fullOld) && is_file($fullOld)) {
                            @unlink($fullOld);
                        }
                    }

                    $newFilename = sprintf('logo_%d_%s.%s', $userId, bin2hex(random_bytes(6)), $ext);
                    $destination = $uploadDir . '/' . $newFilename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $logoPath = 'uploads/logos/' . $newFilename;
                    } else {
                        $error = 'Failed to move uploaded logo file to destination folder.';
                    }
                }
            }
        }

        if (empty($companyName)) {
            $error = 'Company name is required.';
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE company_settings SET
                        company_name = ?, logo = ?, address = ?, phone = ?, email = ?, website = ?,
                        tax_number = ?, currency = ?, bank_name = ?, bank_account_name = ?,
                        bank_account_number = ?, bank_routing = ?, bank_swift = ?,
                        mobile_money_name = ?, mobile_money_number = ?,
                        default_invoice_terms = ?, default_quotation_terms = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $companyName, $logoPath, $address, $phone, $email, $website,
                    $taxNumber, $currency, $bankName, $bankAccountName,
                    $bankAccountNumber, $bankRouting, $bankSwift,
                    $mobileMoneyName, $mobileMoneyNumber,
                    $defaultInvoiceTerms, $defaultQuotationTerms,
                    $userId
                ]);

                setFlash('success', 'Company profile and logo updated successfully.');
                header('Location: /company/settings.php');
                exit;
            } catch (Exception $e) {
                $error = 'Failed to save settings: ' . $e->getMessage();
            }
        }
    }
}

// Reload fresh settings
$company = getCompanySettings($pdo, $userId);
$pageTitle = 'Company Settings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <h2 class="h5 fw-bold text-slate-900 mb-1">Company Profile & Preferences</h2>
  <p class="text-muted small mb-0">Configure your business brand, upload your official logo, and set payment remittance details.</p>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>
    <div><?= e($error) ?></div>
  </div>
<?php endif; ?>

<form method="POST" action="/company/settings.php" enctype="multipart/form-data">
  <?= csrfField() ?>

  <!-- Organization Logo & Brand Banner Card -->
  <div class="pg-card mb-4 bg-white">
    <div class="pg-card-header">
      <div>
        <h3 class="h6 fw-bold mb-0 text-slate-900"><i class="bi bi-image me-1 text-primary"></i> Organization Logo</h3>
        <span class="text-muted small">This logo will appear on all issued Invoices, Quotations, and PDF exports</span>
      </div>
    </div>
    <div class="pg-card-body p-4">
      <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-4">
        <!-- Logo Preview Box -->
        <div class="p-3 border rounded-3 text-center bg-light d-flex align-items-center justify-content-center" style="width: 180px; height: 110px; flex-shrink: 0;">
          <?php if (!empty($company['logo']) && file_exists(dirname(__DIR__) . '/' . ltrim($company['logo'], '/'))): ?>
            <img src="/<?= e($company['logo']) ?>?v=<?= time() ?>" alt="<?= e($company['company_name']) ?>" style="max-width: 160px; max-height: 90px; object-fit: contain;">
          <?php else: ?>
            <div class="text-center text-muted">
              <div class="pg-brand-emblem mx-auto mb-1" style="width: 42px; height: 42px; font-size: 1.2rem;">P</div>
              <span class="small d-block" style="font-size: 0.72rem;">Default Emblem</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="flex-grow-1">
          <label class="form-label-pg mb-1" for="logoInput">Upload New Logo Image</label>
          <input type="file" class="form-control form-control-pg" id="logoInput" name="logo" accept="image/png, image/jpeg, image/webp, image/svg+xml">
          <div class="form-text small text-muted mt-1">
            Supported formats: <strong>PNG, JPG, WebP, SVG</strong> &bull; Max file size: <strong>2MB</strong> &bull; Recommended transparent background, 300x100px.
          </div>

          <?php if (!empty($company['logo'])): ?>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogoCheck">
              <label class="form-check-label small text-danger fw-semibold" for="removeLogoCheck">
                <i class="bi bi-trash3 me-1"></i> Remove custom logo and revert to default brand emblem
              </label>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Brand & Legal Info -->
    <div class="col-lg-6">
      <div class="pg-card h-100">
        <div class="pg-card-header">
          <h3 class="h6 fw-bold mb-0 text-slate-900"><i class="bi bi-building me-1 text-primary"></i> Company Details</h3>
        </div>
        <div class="pg-card-body p-4">
          <div class="mb-3">
            <label class="form-label-pg" for="company_name">Company / Organization Name *</label>
            <input type="text" class="form-control form-control-pg fw-semibold" id="company_name" name="company_name" value="<?= e($company['company_name']) ?>" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="email">Billing Email</label>
              <input type="email" class="form-control form-control-pg" id="email" name="email" value="<?= e($company['email']) ?>">
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="phone">Phone Number</label>
              <input type="text" class="form-control form-control-pg" id="phone" name="phone" value="<?= e($company['phone']) ?>">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="website">Website URL</label>
              <input type="text" class="form-control form-control-pg" id="website" name="website" value="<?= e($company['website']) ?>">
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="tax_number">Tax / VAT Registration ID</label>
              <input type="text" class="form-control form-control-pg" id="tax_number" name="tax_number" value="<?= e($company['tax_number']) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-pg" for="currency">Default Billing Currency</label>
            <select name="currency" id="currency" class="form-select form-select-pg">
              <?php foreach (['KES' => 'KES (KSh - Kenyan Shilling)', 'USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'GBP' => 'GBP (£)', 'CAD' => 'CAD (CA$)', 'AUD' => 'AUD (A$)', 'NGN' => 'NGN (₦)', 'GHS' => 'GHS (GH₵)', 'ZAR' => 'ZAR (R)', 'INR' => 'INR (₹)'] as $code => $lbl): ?>
                <option value="<?= $code ?>" <?= (($company['currency'] ?? 'KES') === $code) ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-0">
            <label class="form-label-pg" for="address">Registered Address</label>
            <textarea class="form-control form-control-pg" id="address" name="address" rows="3"><?= e($company['address']) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Banking & Remittance Info -->
    <div class="col-lg-6">
      <div class="pg-card mb-4">
        <div class="pg-card-header">
          <h3 class="h6 fw-bold mb-0 text-slate-900"><i class="bi bi-bank me-1 text-primary"></i> Bank Remittance Information</h3>
        </div>
        <div class="pg-card-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="bank_name">Bank Name</label>
              <input type="text" class="form-control form-control-pg" id="bank_name" name="bank_name" value="<?= e($company['bank_name']) ?>">
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="bank_account_name">Account Beneficiary Name</label>
              <input type="text" class="form-control form-control-pg" id="bank_account_name" name="bank_account_name" value="<?= e($company['bank_account_name']) ?>">
            </div>
          </div>

          <div class="row g-2 mb-0">
            <div class="col-sm-6">
              <label class="form-label-pg" for="bank_account_number">Account Number / IBAN</label>
              <input type="text" class="form-control form-control-pg mono-num" id="bank_account_number" name="bank_account_number" value="<?= e($company['bank_account_number']) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label-pg" for="bank_routing">Routing / Sort</label>
              <input type="text" class="form-control form-control-pg mono-num" id="bank_routing" name="bank_routing" value="<?= e($company['bank_routing']) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label-pg" for="bank_swift">SWIFT / BIC</label>
              <input type="text" class="form-control form-control-pg mono-num" id="bank_swift" name="bank_swift" value="<?= e($company['bank_swift']) ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="pg-card">
        <div class="pg-card-header">
          <h3 class="h6 fw-bold mb-0 text-slate-900"><i class="bi bi-phone me-1 text-success"></i> Mobile Money / Online Channel</h3>
        </div>
        <div class="pg-card-body p-4">
          <div class="row g-2">
            <div class="col-sm-6">
              <label class="form-label-pg" for="mobile_money_name">Provider / Service Name</label>
              <input type="text" class="form-control form-control-pg" id="mobile_money_name" name="mobile_money_name" value="<?= e($company['mobile_money_name']) ?>" placeholder="e.g. PaperGlow Pay / M-Pesa / Stripe">
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="mobile_money_number">Merchant / Pay Number</label>
              <input type="text" class="form-control form-control-pg mono-num" id="mobile_money_number" name="mobile_money_number" value="<?= e($company['mobile_money_number']) ?>" placeholder="+1 (555) 000-0000">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Standard Terms -->
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="default_invoice_terms">Default Invoice Terms & Conditions</label>
        <textarea class="form-control form-control-pg" id="default_invoice_terms" name="default_invoice_terms" rows="3"><?= e($company['default_invoice_terms']) ?></textarea>
      </div>
    </div>
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="default_quotation_terms">Default Quotation Terms & Conditions</label>
        <textarea class="form-control form-control-pg" id="default_quotation_terms" name="default_quotation_terms" rows="3"><?= e($company['default_quotation_terms']) ?></textarea>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-end gap-2 pb-5">
    <button type="submit" class="btn btn-pg-primary px-4">
      <i class="bi bi-check-lg me-1"></i> Update Company Profile & Logo
    </button>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
