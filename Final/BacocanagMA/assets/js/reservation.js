document.addEventListener('DOMContentLoaded', function() {
    const resLab = document.getElementById('resLab');
    const resPC = document.getElementById('resPC');
    const resDate = document.getElementById('resDate');
    const pcStatusBadge = document.getElementById('pcStatusBadge');
    const submitBtn = document.getElementById('submitReservation');

    // Exit if reservation modal doesn't exist on this page
    if (!resLab || !resPC || !resDate) return;

    async function checkPCStatus() {
        const lab = resLab.value;
        const pc = resPC.value;
        const date = resDate.value || new Date().toISOString().split('T')[0];
        
        if (!lab || !pc) {
            pcStatusBadge.textContent = 'Select Lab & PC to check status';
            submitBtn.disabled = true;
            return;
        }

        pcStatusBadge.textContent = 'Checking status...';
        try {
            const res = await fetch(`check_pc_status.php?lab=${encodeURIComponent(lab)}&pc=${encodeURIComponent(pc)}&date=${date}`);
            const data = await res.json();
            
            pcStatusBadge.textContent = data.status;
            pcStatusBadge.style.background = `${data.color}20`;
            pcStatusBadge.style.color = data.color;
            pcStatusBadge.style.border = `2px solid ${data.color}`;
            submitBtn.disabled = data.status !== 'Available';
        } catch (err) {
            pcStatusBadge.textContent = 'Error checking status';
            submitBtn.disabled = true;
        }
    }

    // Auto-fetch remaining sessions & check status when modal opens
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-modal-open="reservationModal"]')) {
            const userId = document.body.dataset.userId || '';
            if (userId) {
                fetch(`get_student.php?id=${encodeURIComponent(userId)}`)
                    .then(r => r.json())
                    .then(d => {
                        const remInput = document.getElementById('resRemaining');
                        if (remInput) remInput.value = d.success ? d.remaining_session : 0;
                    });
            }
            // Slight delay to ensure modal is visible before checking
            setTimeout(checkPCStatus, 50);
        }
    });

    resLab.addEventListener('change', checkPCStatus);
    resPC.addEventListener('change', checkPCStatus);
    resDate.addEventListener('change', checkPCStatus);
});