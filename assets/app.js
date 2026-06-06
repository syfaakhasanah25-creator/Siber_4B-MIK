// ===== TOAST =====
function showToast(msg, type = '') {
    let t = document.getElementById('siber-toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'siber-toast';
        t.className = 'toast';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.className = 'toast ' + type;
    requestAnimationFrame(() => {
        requestAnimationFrame(() => t.classList.add('show'));
    });
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

// ===== KONFIRMASI POP-UP =====
function showConfirm(message, onConfirm) {
    let overlay = document.getElementById('confirmOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'confirmOverlay';
        overlay.innerHTML = `
            <div class="confirm-box">
                <div class="confirm-msg" id="confirmMsg"></div>
                <div class="confirm-actions">
                    <button class="btn btn-outline" id="confirmNo">Batal</button>
                    <button class="btn btn-danger" id="confirmYes">Ya, Lanjutkan</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
    }
    document.getElementById('confirmMsg').textContent = message;
    overlay.classList.add('open');

    const yes = document.getElementById('confirmYes');
    const no  = document.getElementById('confirmNo');

    const cleanup = () => { overlay.classList.remove('open'); yes.onclick = null; no.onclick = null; };
    yes.onclick = () => { cleanup(); onConfirm(); };
    no.onclick  = () => cleanup();
}

// ===== NAVBAR MOBILE =====
const navToggle = document.getElementById('navToggle');
const mobileMenu = document.getElementById('mobileMenu');
if (navToggle && mobileMenu) {
    navToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        mobileMenu.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
        if (mobileMenu.classList.contains('open') && !mobileMenu.contains(e.target) && e.target !== navToggle) {
            mobileMenu.classList.remove('open');
        }
    });
}

// ===== MODAL =====
function openBedModal(bedId) {
    const overlay = document.getElementById('modalOverlay');
    const modal   = document.getElementById('bedModal');
    const body    = document.getElementById('modalBody');
    const title   = document.getElementById('modalTitle');

    overlay.classList.add('open');
    modal.classList.add('open');
    body.innerHTML = '<div class="loading">Memuat data...</div>';

    fetch('api.php?action=bed_detail&bed_id=' + bedId)
        .then(r => r.json())
        .then(data => {
            if (data.error) { body.innerHTML = '<p class="text-muted">' + data.error + '</p>'; return; }
            renderBedModal(data.bed, data.booking, title, body);
        })
        .catch(() => { body.innerHTML = '<p class="text-muted">Gagal memuat data.</p>'; });
}

function renderBedModal(bed, booking, title, body) {
    title.textContent = 'Bed ' + bed.bed_code;

    const statusMap = { available: 'Tersedia', booked: 'Terisi', cleaning: 'Pembersihan', maintenance: 'Perbaikan' };
    const label = statusMap[bed.status] || bed.status;

    // Hitung estimasi checkout & lama rawat
    let checkoutInfo = '';
    if (booking && booking.check_in_date) {
        const checkin  = new Date(booking.check_in_date);
        const today    = new Date();
        const diffMs   = today - checkin;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const lamaRawat = diffDays > 0 ? diffDays + ' hari' : 'Hari ini';

        if (booking.check_out_date) {
            checkoutInfo = `
                <div class="info-row"><span>Est. Check-out</span><strong>${booking.check_out_date}</strong></div>
                <div class="info-row"><span>Lama Rawat</span><strong>${lamaRawat}</strong></div>`;
        } else {
            checkoutInfo = `
                <div class="info-row"><span>Est. Check-out</span><strong class="text-muted">Belum ditentukan</strong></div>
                <div class="info-row"><span>Lama Rawat</span><strong>${lamaRawat}</strong></div>`;
        }
    }

    let html = `
        <div class="modal-bed-info">
            <div class="modal-bed-preview ${bed.status}">
                <div class="bed-code">${bed.bed_code}</div>
                <div class="bed-number">Bed ${bed.bed_number}</div>
                <div class="bed-status-label">${label}</div>
            </div>
            <div class="modal-meta">
                <div class="info-row"><span>Ruangan</span><strong>${bed.room_name}</strong></div>
                <div class="info-row"><span>Kelas</span><strong>${bed.room_type}</strong></div>
                <div class="info-row"><span>Status</span><strong>${label}</strong></div>
                ${booking ? `<div class="info-row"><span>No. RM</span><strong>${booking.no_rm ? '<span class="rm-badge">'+booking.no_rm+'</span>' : '-'}</strong></div>` : ''}
                ${booking ? `<div class="info-row"><span>Pasien</span><strong>${booking.patient_name}</strong></div>` : ''}
                ${booking ? `<div class="info-row"><span>Check-in</span><strong>${booking.check_in_date}</strong></div>` : ''}
                ${checkoutInfo}
            </div>
        </div>
        <div class="modal-actions">
    `;

    const role = typeof USER_ROLE !== 'undefined' ? USER_ROLE : '';

    // ranap / admin: booking jika available
    if (role === 'ranap' && bed.status === 'available') {
        html += `<a href="booking.php?bed_id=${bed.id}" class="btn btn-primary">Booking Bed Ini</a>`;
    }

    // Jika terisi: tombol Selesaikan + Batalkan
    if (bed.status === 'booked' && booking) {
    if (role === 'bangsal') {
            html += `<button class="btn btn-success" onclick="completeBooking(${booking.id}, ${bed.id})">Selesaikan Booking</button>`;
            html += `<button class="btn btn-danger" onclick="cancelBooking(${booking.id}, ${bed.id})">Batalkan Booking</button>`;
        }
    }

    // Bangsal / admin: update status (hanya jika bukan booked, atau tambahan)
   if (role === 'bangsal') {
    const statuses = [
        { val: 'available', label: 'Tandai Tersedia', cls: 'btn-success' },
        { val: 'cleaning', label: 'Tandai Pembersihan', cls: 'btn-warning' }
    ];
          if (role === 'admin') {
    html += `
        <div class="modal-section-title" style="margin-top:0.75rem">
            Update Status
        </div>
        <button class="btn btn-secondary"
                onclick="updateStatus(${bed.id}, 'maintenance')">
            Tandai Perbaikan
        </button>
    `;
}
        const filtered = statuses.filter(s => s.val !== bed.status && !(bed.status === 'booked' && s.val === 'available'));
        if (filtered.length > 0) {
            html += `<div class="modal-section-title" style="margin-top:0.75rem">Update Status</div>`;
            filtered.forEach(s => {
                html += `<button class="btn ${s.cls}" onclick="updateStatus(${bed.id}, '${s.val}')">${s.label}</button>`;
            });
        }
    }

    html += '</div>';
    body.innerHTML = html;
}

function closeModal() {
    document.getElementById('modalOverlay')?.classList.remove('open');
    document.getElementById('bedModal')?.classList.remove('open');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ===== UPDATE STATUS =====
function updateStatus(bedId, newStatus) {
    showConfirm('Ubah status bed ini menjadi "' + ({available:'Tersedia',cleaning:'Pembersihan',maintenance:'Perbaikan'}[newStatus]) + '"?', () => {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('bed_id', bedId);
        fd.append('status', newStatus);

        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updateBedCard(bedId, newStatus, data.label);
                    closeModal();
                    showToast('Status bed diperbarui: ' + data.label, 'success');
                } else {
                    showToast(data.error || 'Gagal update status', 'error');
                }
            });
    });
}

// ===== COMPLETE BOOKING =====
function completeBooking(bookingId, bedId) {
    showConfirm('Selesaikan booking ini? Bed akan berubah ke status Pembersihan.', () => {
        const fd = new FormData();
        fd.append('action', 'complete_booking');
        fd.append('booking_id', bookingId);
        fd.append('bed_id', bedId);

        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updateBedCard(bedId, 'cleaning', 'Pembersihan');
                    closeModal();
                    showToast('Booking diselesaikan, bed masuk Pembersihan', 'success');
                } else {
                    showToast(data.error || 'Gagal menyelesaikan booking', 'error');
                }
            });
    });
}

// ===== CANCEL BOOKING =====
function cancelBooking(bookingId, bedId) {
    showConfirm('Batalkan booking ini? Bed akan kembali ke status Tersedia.', () => {
        const fd = new FormData();
        fd.append('action', 'cancel_booking');
        fd.append('booking_id', bookingId);

        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updateBedCard(bedId, 'available', 'Tersedia');
                    closeModal();
                    showToast('Booking dibatalkan', 'success');
                } else {
                    showToast(data.error || 'Gagal membatalkan', 'error');
                }
            });
    });
}

// ===== UPDATE BED CARD IN DOM =====
function updateBedCard(bedId, newStatus, label) {
    const card = document.querySelector(`.bed-card[data-bed-id="${bedId}"]`);
    if (!card) return;
    const statusClasses = ['available','booked','cleaning','maintenance'];
    card.classList.remove(...statusClasses);
    card.classList.add(newStatus);
    card.dataset.status = newStatus;
    const sl = card.querySelector('.bed-status-label');
    if (sl) sl.textContent = label;
    updateBedSummary();
}

// ===== UPDATE SUMMARY COUNTS =====
function updateBedSummary() {
    const summary = document.getElementById('bedSummary');
    if (!summary) return;
    const cards = document.querySelectorAll('.bed-card');
    const cnt = { available: 0, booked: 0, cleaning: 0, maintenance: 0 };
    cards.forEach(c => { if (cnt[c.dataset.status] !== undefined) cnt[c.dataset.status]++; });
    const dots = summary.querySelectorAll('.dot');
    const labels = ['Tersedia','Terisi','Pembersihan','Perbaikan'];
    const keys   = ['available','booked','cleaning','maintenance'];
    dots.forEach((d, i) => { d.textContent = cnt[keys[i]] + ' ' + labels[i]; });
}

// ===== CRUD BED =====
function openAddBedModal() {
    document.getElementById('crudTitle').textContent = 'Tambah Bed';
    document.getElementById('crudAction').value = 'add_bed';
    document.getElementById('crudBedId').value = '';
    document.getElementById('crudBedCode').value = '';
    document.getElementById('crudBedNumber').value = '';
    document.getElementById('crudStatus').value = 'available';
    document.getElementById('crudOverlay').classList.add('open');
    document.getElementById('crudModal').classList.add('open');
}

function openEditBedModal(bedId, bedCode, status) {
    document.getElementById('crudTitle').textContent = 'Edit Bed';
    document.getElementById('crudAction').value = 'edit_bed';
    document.getElementById('crudBedId').value = bedId;
    document.getElementById('crudBedCode').value = bedCode;
    // Ambil bed_number dari card
    const card = document.querySelector(`.bed-card[data-bed-id="${bedId}"]`);
    const numEl = card ? card.querySelector('.bed-number') : null;
    const num = numEl ? numEl.textContent.replace('Bed ','').trim() : '';
    document.getElementById('crudBedNumber').value = num;
    document.getElementById('crudStatus').value = status;
    document.getElementById('crudOverlay').classList.add('open');
    document.getElementById('crudModal').classList.add('open');
}

function closeCrudModal() {
    document.getElementById('crudOverlay').classList.remove('open');
    document.getElementById('crudModal').classList.remove('open');
}

document.getElementById('crudForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);

    fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.error) { showToast(data.error, 'error'); return; }
            closeCrudModal();
            showToast('Berhasil disimpan', 'success');
            setTimeout(() => location.reload(), 800);
        });
});

function deleteBed(bedId, bedCode) {
    showConfirm('Hapus bed ' + bedCode + '? Tindakan ini tidak bisa dibatalkan.', () => {
        const fd = new FormData();
        fd.append('action', 'delete_bed');
        fd.append('bed_id', bedId);
        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showToast(data.error, 'error'); return; }
                const card = document.querySelector(`.bed-card[data-bed-id="${bedId}"]`);
                if (card) card.remove();
                updateBedSummary();
                showToast('Bed ' + bedCode + ' dihapus', 'success');
            });
    });
}

// ===== INIT CHARTS ON DASHBOARD =====
// Bar chart digantikan oleh CSS .metric-bar di masing-masing card

// ===== AUTO REFRESH BED STATUS (setiap 30 detik) =====
if (typeof CURRENT_ROOM !== 'undefined' && CURRENT_ROOM > 0) {
    setInterval(() => {
        fetch('api.php?action=room_beds&room_id=' + CURRENT_ROOM)
            .then(r => r.json())
            .then(data => {
                if (!data.beds) return;
                data.beds.forEach(bed => {
                    const card = document.querySelector(`.bed-card[data-bed-id="${bed.id}"]`);
                    if (!card) return;
                    const statusMap = { available: 'Tersedia', booked: 'Terisi', cleaning: 'Pembersihan', maintenance: 'Perbaikan' };
                    if (card.dataset.status !== bed.status) {
                        updateBedCard(bed.id, bed.status, statusMap[bed.status]);
                    }
                });
            });
    }, 30000);
}

// ===== MSG PARAM TOAST =====
(function() {
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('msg');
    if (msg === 'booked') showToast('Booking berhasil disimpan', 'success');
})();

