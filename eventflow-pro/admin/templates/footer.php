            </div>
        </div>
    </div>

    <!-- Admin Footer -->
    <footer class="bg-dark text-light py-3 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> Admin Panel</small>
                </div>
                <div class="col-md-6 text-end">
                    <small>Logged in as: <?php echo htmlspecialchars($current_user->name); ?> (<?php echo $current_user->role; ?>)</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>