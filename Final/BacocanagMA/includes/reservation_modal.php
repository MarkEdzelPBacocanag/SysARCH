<div class="modal" id="reservationModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>🖥️ Reserve a Computer</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form class="modal-body" method="POST" action="add_reservation.php" id="reservationForm">
            <div class="form-row">
                <div class="field-group">
                    <label for="resLab">Laboratory:</label>
                    <select id="resLab" name="lab" class="course-select" required>
                        <option value="" disabled selected>Select Lab</option>
                        <option value="Lab 543">Lab 543</option>
                        <option value="Lab 544">Lab 544</option>
                    </select>
                </div>
                <div class="field-group">
                    <label for="resPC">PC Number:</label>
                    <select id="resPC" name="pc_number" class="course-select" required>
                        <option value="" disabled selected>Select PC</option>
                        <?php for ($i = 1; $i <= 50; $i++): ?>
                            <option value="PC-<?= $i < 10 ? '0' : '' ?><?= $i ?>">PC-<?= $i < 10 ? '0' : '' ?><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="field-group">
                <label>PC Status:</label>
                <div id="pcStatusBadge" style="padding:8px;border-radius:5px;background:#e9ecef;text-align:center;font-weight:bold;color:#555;">Select Lab & PC to check status</div>
            </div>
            <div class="field-group">
                <label for="resPurpose">Purpose:</label>
                <select id="resPurpose" name="purpose" class="course-select" required>
                    <option value="" disabled selected>Select Purpose</option>
                    <option value="C Programming">C Programming</option>
                    <option value="C#">C#</option>
                    <option value="Java">Java</option>
                    <option value="Php">Php</option>
                </select>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label for="resDate">Date:</label>
                    <input type="date" id="resDate" name="reservation_date" class="course-select" required>
                </div>
            </div>
            <div class="form-row">
                <div class="field-group">
                    <label for="resStartTime">Start Time:</label>
                    <input type="time" id="resStartTime" name="start_time" class="course-select" required>
                </div>
                <div class="field-group">
                    <label for="resEndTime">End Time:</label>
                    <input type="time" id="resEndTime" name="end_time" class="course-select" required>
                </div>
            </div>
            <div class="field-group">
                <label>Remaining Sessions:</label>
                <input type="number" id="resRemaining" readonly style="background:#f0f0f0;">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" id="submitReservation" class="btn btn-primary" disabled>Submit Request</button>
            </div>
        </form>
    </div>
</div>