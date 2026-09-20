    </div>
  </main>
</div>

<!-- Shared delete confirmation popup -->
<div class="modal-backdrop" id="confirmDeleteModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Delete transaction?</h3>
      <button class="modal-close" data-modal-close>✕</button>
    </div>
    <div class="modal-body">
      <p id="confirmDeleteText">Do you really want to delete this transaction? This cannot be undone.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
      <button type="button" class="btn btn-danger" id="confirmDeleteYes">Yes, delete</button>
    </div>
  </div>
</div>

<script src="js/script.js"></script>
<script src="js/profile-menu.js"></script>
<script src="js/theme-toggle.js"></script>
</body>
</html>
