/**
 * PaperGlow Billing System - Vanilla JavaScript ES6+
 * Original interactive behaviors for dynamic rows, live financial calculations,
 * AJAX interactions, modals, and responsive navigation.
 */

document.addEventListener('DOMContentLoaded', () => {
  initSidebarToggle();
  initDynamicBillingItems();
  initQuickCustomerModal();
  initDeleteConfirmations();
});

/**
 * Mobile sidebar toggle handler
 */
function initSidebarToggle() {
  const toggleBtn = document.getElementById('pgSidebarToggle');
  const sidebar = document.querySelector('.pg-sidebar');
  let backdrop = document.querySelector('.pg-sidebar-backdrop');

  if (toggleBtn && sidebar) {
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'pg-sidebar-backdrop';
      document.body.appendChild(backdrop);
    }

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });

    backdrop.addEventListener('click', () => {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }
}

/**
 * Dynamic Items Calculation Engine (Invoices & Quotations)
 */
function initDynamicBillingItems() {
  const container = document.getElementById('itemsContainer');
  const addBtn = document.getElementById('addItemBtn');
  if (!container) return;

  const currencySymbol = container.dataset.currencySymbol || '$';

  // Function to calculate a single row
  function calculateRow(row) {
    const qtyInput = row.querySelector('.item-qty');
    const priceInput = row.querySelector('.item-price');
    const discountInput = row.querySelector('.item-discount');
    const taxRateInput = row.querySelector('.item-tax-rate');
    const taxAmountSpan = row.querySelector('.item-tax-amount');
    const lineTotalSpan = row.querySelector('.item-line-total');

    if (!qtyInput || !priceInput) return;

    const qty = Math.max(0, parseFloat(qtyInput.value) || 0);
    const unitPrice = Math.max(0, parseFloat(priceInput.value) || 0);
    const discount = Math.max(0, parseFloat(discountInput ? discountInput.value : 0) || 0);
    const taxRate = Math.max(0, parseFloat(taxRateInput ? taxRateInput.value : 0) || 0);

    const baseAmount = Math.max(0, (qty * unitPrice) - discount);
    const taxAmount = (baseAmount * taxRate) / 100;
    const lineTotal = baseAmount + taxAmount;

    if (taxAmountSpan) {
      taxAmountSpan.textContent = currencySymbol + taxAmount.toFixed(2);
      const hiddenTax = row.querySelector('.item-hidden-tax');
      if (hiddenTax) hiddenTax.value = taxAmount.toFixed(2);
    }

    if (lineTotalSpan) {
      lineTotalSpan.textContent = currencySymbol + lineTotal.toFixed(2);
      const hiddenTotal = row.querySelector('.item-hidden-total');
      if (hiddenTotal) hiddenTotal.value = lineTotal.toFixed(2);
    }

    return {
      subtotalRaw: (qty * unitPrice),
      discount: discount,
      taxAmount: taxAmount,
      lineTotal: lineTotal
    };
  }

  // Recalculate document totals
  function recalculateAll() {
    const rows = container.querySelectorAll('.pg-item-row');
    let subtotal = 0;
    let discountTotal = 0;
    let taxTotal = 0;
    let grandTotal = 0;

    rows.forEach(row => {
      const res = calculateRow(row);
      if (res) {
        subtotal += res.subtotalRaw;
        discountTotal += res.discount;
        taxTotal += res.taxAmount;
        grandTotal += res.lineTotal;
      }
    });

    const subtotalEl = document.getElementById('calcSubtotal');
    const discountEl = document.getElementById('calcDiscount');
    const taxEl = document.getElementById('calcTax');
    const grandTotalEl = document.getElementById('calcGrandTotal');

    if (subtotalEl) subtotalEl.textContent = currencySymbol + subtotal.toFixed(2);
    if (discountEl) discountEl.textContent = currencySymbol + discountTotal.toFixed(2);
    if (taxEl) taxEl.textContent = currencySymbol + taxTotal.toFixed(2);
    if (grandTotalEl) grandTotalEl.textContent = currencySymbol + grandTotal.toFixed(2);
  }

  // Bind input listeners
  container.addEventListener('input', (e) => {
    if (e.target.matches('.item-qty, .item-price, .item-discount, .item-tax-rate')) {
      recalculateAll();
    }
  });

  // Bind remove button
  container.addEventListener('click', (e) => {
    const removeBtn = e.target.closest('.remove-item-btn');
    if (removeBtn) {
      const rows = container.querySelectorAll('.pg-item-row');
      if (rows.length <= 1) {
        showToast('At least one item row is required.', 'warning');
        return;
      }
      const row = removeBtn.closest('.pg-item-row');
      row.remove();
      recalculateAll();
      reindexRows();
    }
  });

  // Bind add button
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const template = document.getElementById('itemRowTemplate');
      let newRow;
      if (template) {
        newRow = template.content.cloneNode(true);
      } else {
        // Fallback row creation
        const div = document.createElement('div');
        div.className = 'pg-item-row';
        div.innerHTML = `
          <div class="row g-2 align-items-center">
            <div class="col-md-4 col-12">
              <label class="form-label-pg d-md-none">Description</label>
              <input type="text" name="items[desc][]" class="form-control form-control-pg" placeholder="Item description or service..." required>
            </div>
            <div class="col-md-2 col-4">
              <label class="form-label-pg d-md-none">Qty</label>
              <input type="number" name="items[qty][]" class="form-control form-control-pg item-qty text-end" value="1.00" step="0.01" min="0.01" required>
            </div>
            <div class="col-md-2 col-4">
              <label class="form-label-pg d-md-none">Price</label>
              <input type="number" name="items[price][]" class="form-control form-control-pg item-price text-end" value="0.00" step="0.01" min="0.00" required>
            </div>
            <div class="col-md-1 col-4">
              <label class="form-label-pg d-md-none">Disc</label>
              <input type="number" name="items[discount][]" class="form-control form-control-pg item-discount text-end" value="0.00" step="0.01" min="0.00">
            </div>
            <div class="col-md-1 col-4">
              <label class="form-label-pg d-md-none">Tax %</label>
              <input type="number" name="items[tax_rate][]" class="form-control form-control-pg item-tax-rate text-end" value="0.00" step="0.01" min="0.00">
            </div>
            <div class="col-md-1 col-4 text-end">
              <label class="form-label-pg d-md-none">Line Total</label>
              <span class="mono-num fw-semibold item-line-total">${currencySymbol}0.00</span>
            </div>
            <div class="col-md-1 col-4 text-end">
              <button type="button" class="btn btn-outline-danger btn-sm border-0 remove-item-btn" title="Remove Item">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </div>
        `;
        newRow = div;
      }
      container.appendChild(newRow);
      recalculateAll();
      reindexRows();
    });
  }

  function reindexRows() {
    // Optional order indexing
  }

  // Initial calculation
  recalculateAll();
}

/**
 * AJAX Quick Customer Modal Creation
 */
function initQuickCustomerModal() {
  const form = document.getElementById('quickCustomerForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      const formData = new FormData(form);
      const res = await fetch('/customers/create.php?ajax=1', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const data = await res.json();

      if (data.success && data.customer) {
        // Append to customer select dropdown
        const select = document.getElementById('customerSelect');
        if (select) {
          const opt = document.createElement('option');
          opt.value = data.customer.id;
          opt.textContent = `${data.customer.name} (${data.customer.company || 'Individual'})`;
          opt.selected = true;
          select.appendChild(opt);
        }

        // Close modal
        const modalEl = document.getElementById('quickCustomerModal');
        if (modalEl && window.bootstrap) {
          const modal = window.bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
        }
        form.reset();
        showToast('Customer created successfully!', 'success');
      } else {
        showToast(data.error || 'Failed to create customer.', 'danger');
      }
    } catch (err) {
      showToast('Network error while creating customer.', 'danger');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}

/**
 * Delete Confirmation Interceptor
 */
function initDeleteConfirmations() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (btn) {
      const msg = btn.dataset.confirm || 'Are you sure you want to delete this item? This action cannot be undone.';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    }
  });
}

/**
 * Original PaperGlow Toast Notification
 */
function showToast(message, type = 'info') {
  let toastContainer = document.getElementById('pgToastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'pgToastContainer';
    toastContainer.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px;';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bgColors = {
    success: '#10b981',
    danger: '#ef4444',
    warning: '#f59e0b',
    info: '#0f172a'
  };

  toast.style.cssText = `
    background: ${bgColors[type] || '#0f172a'};
    color: #ffffff;
    padding: 12px 18px;
    border-radius: 8px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
    font-size: 0.88rem;
    font-weight: 500;
    min-width: 260px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.25s ease;
    transform: translateY(20px);
    opacity: 0;
  `;
  toast.innerHTML = `<span>${message}</span><span style="cursor:pointer; margin-left: 12px; font-weight:bold;">&times;</span>`;

  toastContainer.appendChild(toast);
  requestAnimationFrame(() => {
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
  });

  const dismiss = () => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 250);
  };

  toast.querySelector('span:last-child').onclick = dismiss;
  setTimeout(dismiss, 4000);
}
